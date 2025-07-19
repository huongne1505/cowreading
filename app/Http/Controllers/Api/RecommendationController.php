<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    /**
     * Get personalized book recommendations for user
     */
    public function getRecommendations(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->get('limit', 10);

        // Get user's reading history and preferences
        $userCategories = $this->getUserPreferredCategories($user);
        $userAuthors = $this->getUserPreferredAuthors($user);
        $readBookIds = $user->readingProgress()->pluck('book_id')->toArray();

        // Build recommendations based on different algorithms
        $recommendations = collect();

        // 1. Category-based recommendations
        if (!empty($userCategories)) {
            $categoryRecommendations = Book::published()
                ->whereIn('category_id', $userCategories)
                ->whereNotIn('id', $readBookIds)
                ->orderBy('views', 'desc')
                ->limit($limit / 2)
                ->get()
                ->map(function ($book) {
                    $book->recommendation_reason = 'Based on your reading categories';
                    return $book;
                });
            
            $recommendations = $recommendations->merge($categoryRecommendations);
        }

        // 2. Author-based recommendations
        if (!empty($userAuthors)) {
            $authorRecommendations = Book::published()
                ->whereIn('author_id', $userAuthors)
                ->whereNotIn('id', $readBookIds)
                ->whereNotIn('id', $recommendations->pluck('id')->toArray())
                ->orderBy('views', 'desc')
                ->limit($limit / 3)
                ->get()
                ->map(function ($book) {
                    $book->recommendation_reason = 'Based on your favorite authors';
                    return $book;
                });
            
            $recommendations = $recommendations->merge($authorRecommendations);
        }

        // 3. Similar users recommendations (collaborative filtering)
        $similarUserRecommendations = $this->getSimilarUserRecommendations($user, $readBookIds, $limit / 4);
        $recommendations = $recommendations->merge($similarUserRecommendations);

        // 4. Popular books (fallback)
        $popularBooks = Book::published()
            ->whereNotIn('id', $readBookIds)
            ->whereNotIn('id', $recommendations->pluck('id')->toArray())
            ->orderBy('views', 'desc')
            ->limit($limit - $recommendations->count())
            ->get()
            ->map(function ($book) {
                $book->recommendation_reason = 'Popular books';
                return $book;
            });

        $recommendations = $recommendations->merge($popularBooks);

        // Load relationships and limit results
        $recommendations = $recommendations->take($limit);
        $recommendations->load(['author', 'category']);

        return response()->json([
            'success' => true,
            'data' => [
                'recommendations' => $recommendations->values(),
                'total' => $recommendations->count()
            ]
        ]);
    }

    /**
     * Get trending books
     */
    public function getTrendingBooks(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $period = $request->get('period', 7); // days

        // Books with most activity in the last period
        $trendingBooks = Book::published()
            ->select('books.*', DB::raw('COUNT(reading_sessions.id) as recent_activity'))
            ->leftJoin('reading_sessions', 'books.id', '=', 'reading_sessions.book_id')
            ->where('reading_sessions.start_time', '>=', now()->subDays($period))
            ->groupBy('books.id')
            ->orderBy('recent_activity', 'desc')
            ->orderBy('books.views', 'desc')
            ->limit($limit)
            ->with(['author', 'category'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'trending_books' => $trendingBooks,
                'period_days' => $period
            ]
        ]);
    }

    /**
     * Get books similar to a specific book
     */
    public function getSimilarBooks(Request $request, string $slug): JsonResponse
    {
        $book = Book::where('slug', $slug)->first();
        
        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found'
            ], 404);
        }

        $limit = $request->get('limit', 8);

        // Find similar books based on category, author, and tags
        $similarBooks = Book::published()
            ->where('id', '!=', $book->id)
            ->where(function ($query) use ($book) {
                $query->where('category_id', $book->category_id)
                      ->orWhere('author_id', $book->author_id);
            })
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->with(['author', 'category'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'similar_books' => $similarBooks,
                'reference_book' => $book->load(['author', 'category'])
            ]
        ]);
    }

    /**
     * Get new releases
     */
    public function getNewReleases(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $days = $request->get('days', 30);

        $newReleases = Book::published()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->with(['author', 'category'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'new_releases' => $newReleases,
                'period_days' => $days
            ]
        ]);
    }

    /**
     * Get books by genre/category with recommendations
     */
    public function getBooksByGenre(Request $request, int $categoryId): JsonResponse
    {
        $limit = $request->get('limit', 12);
        $user = $request->user();

        $books = Book::published()
            ->where('category_id', $categoryId)
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->with(['author', 'category'])
            ->get();

        // If user is authenticated, mark books they've read
        if ($user) {
            $readBookIds = $user->readingProgress()->pluck('book_id')->toArray();
            $books->each(function ($book) use ($readBookIds) {
                $book->is_read = in_array($book->id, $readBookIds);
            });
        }

        return response()->json([
            'success' => true,
            'data' => [
                'books' => $books,
                'category_id' => $categoryId
            ]
        ]);
    }

    /**
     * Get user's preferred categories based on reading history
     */
    private function getUserPreferredCategories(User $user): array
    {
        return $user->readingProgress()
            ->join('books', 'reading_progress.book_id', '=', 'books.id')
            ->select('books.category_id', DB::raw('COUNT(*) as count'))
            ->groupBy('books.category_id')
            ->orderBy('count', 'desc')
            ->limit(3)
            ->pluck('category_id')
            ->toArray();
    }

    /**
     * Get user's preferred authors based on reading history
     */
    private function getUserPreferredAuthors(User $user): array
    {
        return $user->readingProgress()
            ->join('books', 'reading_progress.book_id', '=', 'books.id')
            ->select('books.author_id', DB::raw('COUNT(*) as count'))
            ->groupBy('books.author_id')
            ->orderBy('count', 'desc')
            ->limit(3)
            ->pluck('author_id')
            ->toArray();
    }

    /**
     * Get recommendations based on similar users (collaborative filtering)
     */
    private function getSimilarUserRecommendations(User $user, array $readBookIds, int $limit): \Illuminate\Support\Collection
    {
        // Find users with similar reading patterns
        $similarUsers = User::select('users.id', DB::raw('COUNT(*) as common_books'))
            ->join('reading_progress', 'users.id', '=', 'reading_progress.user_id')
            ->where('users.id', '!=', $user->id)
            ->whereIn('reading_progress.book_id', $readBookIds)
            ->groupBy('users.id')
            ->having('common_books', '>=', 2)
            ->orderBy('common_books', 'desc')
            ->limit(10)
            ->pluck('id')
            ->toArray();

        if (empty($similarUsers)) {
            return collect();
        }

        // Get books read by similar users but not by current user
        $recommendations = Book::published()
            ->select('books.*')
            ->join('reading_progress', 'books.id', '=', 'reading_progress.book_id')
            ->whereIn('reading_progress.user_id', $similarUsers)
            ->whereNotIn('books.id', $readBookIds)
            ->groupBy('books.id')
            ->orderBy(DB::raw('COUNT(reading_progress.user_id)'), 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($book) {
                $book->recommendation_reason = 'Users like you also read';
                return $book;
            });

        return $recommendations;
    }
}
