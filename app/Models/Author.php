<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'bio',
        'birth_date',
        'death_date',
        'avatar',
        'nationality',
        'website',
        'is_active'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
        'is_active' => 'boolean'
    ];

    /**
     * Get all books by this author
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /**
     * Get published books by this author
     */
    public function publishedBooks(): HasMany
    {
        return $this->books()->where('status', 'published');
    }

    /**
     * Get the author's full name with birth/death years
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->name;
        if ($this->birth_date) {
            $name .= ' (' . $this->birth_date->format('Y');
            if ($this->death_date) {
                $name .= ' - ' . $this->death_date->format('Y');
            }
            $name .= ')';
        }
        return $name;
    }
}
