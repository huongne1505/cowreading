<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use App\Models\Favorite;

class FavoriteService
{
    /**
     * Add book to favorites
     */
    public function addToFavorites(User $user, Book $book): Favorite
    {
        return Favorite::firstOrCreate([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    /**
     * Remove book from favorites
     */
    public function removeFromFavorites(User $user, Book $book): bool
    {
        return Favorite::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->delete() > 0;
    }

    /**
     * Check if user has favorited a book
     */
    public function isFavorited(User $user, Book $book): bool
    {
        return Favorite::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->exists();
    }

    /**
     * Get user's favorite books
     */
    public function getFavoriteBooks(User $user, int $perPage = 15)
    {
        return $user->favoriteBooks()
            ->with(['author', 'category'])
            ->orderBy('favorites.created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get favorite books count for user
     */
    public function getFavoriteCount(User $user): int
    {
        return Favorite::where('user_id', $user->id)->count();
    }

    /**
     * Toggle favorite status
     */
    public function toggleFavorite(User $user, Book $book): array
    {
        $favorite = Favorite::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return [
                'is_favorited' => false,
                'message' => 'Book removed from favorites'
            ];
        } else {
            Favorite::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]);
            return [
                'is_favorited' => true,
                'message' => 'Book added to favorites'
            ];
        }
    }
}
