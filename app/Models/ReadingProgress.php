<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'chapter_id',
        'progress_percentage',
        'last_position',
        'total_reading_time',
        'last_read_at'
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'progress_percentage' => 'decimal:2'
    ];

    /**
     * Get the user who is reading
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the book being read
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the current chapter
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Update reading progress
     */
    public function updateProgress(int $chapterId, float $percentage, int $position, int $readingTime): void
    {
        $this->update([
            'chapter_id' => $chapterId,
            'progress_percentage' => $percentage,
            'last_position' => $position,
            'total_reading_time' => $this->total_reading_time + $readingTime,
            'last_read_at' => now()
        ]);
    }

    /**
     * Get formatted reading time
     */
    public function getFormattedReadingTimeAttribute(): string
    {
        $minutes = $this->total_reading_time;
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$remainingMinutes}m";
        }

        return "{$remainingMinutes}m";
    }
}
