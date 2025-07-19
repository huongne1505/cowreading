<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'bio',
        'birth_date',
        'phone',
        'is_premium',
        'premium_expires_at',
        'last_login_at',
        'is_active',
        'facebook_id',
        'google_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'is_premium' => 'boolean',
            'premium_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean'
        ];
    }

    /**
     * Get all reviews by this user
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get all favorite books
     */
    public function favoriteBooks(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'favorites')
            ->withTimestamps();
    }

    /**
     * Get all reading progress
     */
    public function readingProgress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }

    /**
     * Get all notifications
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get all book collections
     */
    public function bookCollections(): HasMany
    {
        return $this->hasMany(BookCollection::class);
    }

    /**
     * Get all bookmarks
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    /**
     * Get all reading sessions
     */
    public function readingSessions(): HasMany
    {
        return $this->hasMany(ReadingSession::class);
    }

    /**
     * Get currently reading books
     */
    public function currentlyReadingBooks(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'reading_progress')
            ->wherePivot('progress_percentage', '<', 100)
            ->withPivot(['progress_percentage', 'last_read_at'])
            ->withTimestamps();
    }

    /**
     * Get completed books
     */
    public function completedBooks(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'reading_progress')
            ->wherePivot('progress_percentage', '>=', 100)
            ->withPivot(['progress_percentage', 'last_read_at'])
            ->withTimestamps();
    }

    /**
     * Check if user has favorited a book
     */
    public function hasFavorited(Book $book): bool
    {
        return $this->favoriteBooks()->where('book_id', $book->id)->exists();
    }

    /**
     * Check if user is premium
     */
    public function isPremium(): bool
    {
        return $this->is_premium && 
               ($this->premium_expires_at === null || $this->premium_expires_at->isFuture());
    }

    /**
     * Get reading statistics
     */
    public function getReadingStatsAttribute(): array
    {
        return [
            'total_books_read' => $this->completedBooks()->count(),
            'currently_reading' => $this->currentlyReadingBooks()->count(),
            'total_reading_time' => $this->readingProgress()->sum('total_reading_time'),
            'favorite_books' => $this->favoriteBooks()->count(),
            'reviews_written' => $this->reviews()->count()
        ];
    }
}
