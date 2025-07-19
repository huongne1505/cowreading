<?php

namespace App\Services;

use App\Models\Book;
use App\Models\ReadingProgress;
use App\Models\User;
use App\Models\Chapter;

class ReadingService
{
    /**
     * Start reading a book
     */
    public function startReading(User $user, Book $book): ReadingProgress
    {
        return ReadingProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'book_id' => $book->id,
            ],
            [
                'progress_percentage' => 0,
                'last_position' => 0,
                'last_read_at' => now(),
            ]
        );
    }

    /**
     * Update reading progress
     */
    public function updateProgress(
        User $user,
        Book $book,
        Chapter $chapter,
        float $progressPercentage,
        int $position,
        int $readingTime
    ): ReadingProgress {
        $progress = ReadingProgress::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        if (!$progress) {
            $progress = $this->startReading($user, $book);
        }

        $progress->updateProgress($chapter->id, $progressPercentage, $position, $readingTime);

        return $progress;
    }

    /**
     * Get user's reading progress for a book
     */
    public function getProgress(User $user, Book $book): ?ReadingProgress
    {
        return ReadingProgress::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->with(['chapter'])
            ->first();
    }

    /**
     * Get currently reading books for user
     */
    public function getCurrentlyReading(User $user, int $limit = 10)
    {
        return ReadingProgress::where('user_id', $user->id)
            ->where('progress_percentage', '<', 100)
            ->with(['book.author', 'book.category', 'chapter'])
            ->orderBy('last_read_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get completed books for user
     */
    public function getCompletedBooks(User $user, int $limit = 10)
    {
        return ReadingProgress::where('user_id', $user->id)
            ->where('progress_percentage', '>=', 100)
            ->with(['book.author', 'book.category'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark book as completed
     */
    public function markAsCompleted(User $user, Book $book): ReadingProgress
    {
        $progress = ReadingProgress::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        if (!$progress) {
            $progress = $this->startReading($user, $book);
        }

        $progress->update([
            'progress_percentage' => 100,
            'last_read_at' => now(),
        ]);

        return $progress;
    }

    /**
     * Get reading statistics for user
     */
    public function getReadingStats(User $user): array
    {
        $totalBooks = ReadingProgress::where('user_id', $user->id)->count();
        $completedBooks = ReadingProgress::where('user_id', $user->id)
            ->where('progress_percentage', '>=', 100)
            ->count();
        $currentlyReading = ReadingProgress::where('user_id', $user->id)
            ->where('progress_percentage', '<', 100)
            ->count();
        $totalReadingTime = ReadingProgress::where('user_id', $user->id)
            ->sum('total_reading_time');

        return [
            'total_books' => $totalBooks,
            'completed_books' => $completedBooks,
            'currently_reading' => $currentlyReading,
            'total_reading_time' => $totalReadingTime,
            'average_reading_time' => $totalBooks > 0 ? round($totalReadingTime / $totalBooks, 2) : 0,
            'completion_rate' => $totalBooks > 0 ? round(($completedBooks / $totalBooks) * 100, 2) : 0,
        ];
    }
}
