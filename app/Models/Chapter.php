<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'title',
        'content',
        'chapter_number',
        'word_count',
        'is_published'
    ];

    protected $casts = [
        'is_published' => 'boolean'
    ];

    /**
     * Get the book this chapter belongs to
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the next chapter
     */
    public function nextChapter()
    {
        return $this->where('book_id', $this->book_id)
            ->where('chapter_number', '>', $this->chapter_number)
            ->where('is_published', true)
            ->orderBy('chapter_number')
            ->first();
    }

    /**
     * Get the previous chapter
     */
    public function previousChapter()
    {
        return $this->where('book_id', $this->book_id)
            ->where('chapter_number', '<', $this->chapter_number)
            ->where('is_published', true)
            ->orderBy('chapter_number', 'desc')
            ->first();
    }

    /**
     * Get published chapters only
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Get estimated reading time in minutes
     */
    public function getEstimatedReadingTimeAttribute(): int
    {
        // Average reading speed: 200 words per minute
        return ceil(($this->word_count ?? 0) / 200);
    }
}
