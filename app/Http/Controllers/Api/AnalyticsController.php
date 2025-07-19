<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\User;
use App\Models\ReadingSession;
use App\Models\Review;
use App\Models\Category;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Get user reading statistics
     */
    public function userStats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Basic stats
        $stats = [
            'total_books_started' => $user->readingProgress()->count(),
            'total_books_completed' => $user->readingProgress()->where('progress_percentage', '>=', 100)->count(),
            'currently_reading' => $user->readingProgress()->where('progress_percentage', '<', 100)->count(),
            'favorite_books' => $user->favoriteBooks()->count(),
            'reviews_written' => $user->reviews()->count(),
            'bookmarks_created' => $user->bookmarks()->count(),
            'collections_created' => $user->bookCollections()->count(),
            'total_reading_time' => $user->readingSessions()->sum('duration') // in minutes
        ];

        // Reading sessions by date (last 30 days)
        $readingSessions = $user->readingSessions()
            ->select(DB::raw('DATE(start_time) as date'), DB::raw('SUM(duration) as total_duration'))
            ->where('start_time', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Most read categories
        $categories = Category::select('categories.name', DB::raw('COUNT(*) as books_count'))
            ->join('books', 'categories.id', '=', 'books.category_id')
            ->join('reading_progress', 'books.id', '=', 'reading_progress.book_id')
            ->where('reading_progress.user_id', $user->id)
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('books_count', 'desc')
            ->limit(5)
            ->get();

        // Reading streaks
        $currentStreak = $this->calculateCurrentStreak($user);
        $longestStreak = $this->calculateLongestStreak($user);

        return response()->json([
            'success' => true,
            'data' => [
                'basic_stats' => $stats,
                'reading_sessions' => $readingSessions,
                'favorite_categories' => $categories,
                'reading_streaks' => [
                    'current_streak' => $currentStreak,
                    'longest_streak' => $longestStreak
                ]
            ]
        ]);
    }

    /**
     * Get book statistics
     */
    public function bookStats(Request $request, string $slug): JsonResponse
    {
        $book = Book::with(['author', 'category'])
            ->where('slug', $slug)
            ->first();

        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found'
            ], 404);
        }

        $stats = [
            'total_views' => $book->views,
            'total_downloads' => $book->downloads,
            'total_readers' => $book->readingProgress()->count(),
            'completed_readers' => $book->readingProgress()->where('progress_percentage', '>=', 100)->count(),
            'average_rating' => $book->reviews()->avg('rating'),
            'total_reviews' => $book->reviews()->count(),
            'total_bookmarks' => $book->bookmarks()->count(),
            'total_favorites' => $book->favoritedBy()->count(),
            'total_reading_time' => $book->readingSessions()->sum('duration') // in minutes
        ];

        // Reading progress distribution
        $progressDistribution = $book->readingProgress()
            ->select(
                DB::raw('
                    CASE 
                        WHEN progress_percentage = 0 THEN "Not Started"
                        WHEN progress_percentage < 25 THEN "0-25%"
                        WHEN progress_percentage < 50 THEN "25-50%"
                        WHEN progress_percentage < 75 THEN "50-75%"
                        WHEN progress_percentage < 100 THEN "75-100%"
                        ELSE "Completed"
                    END as range
                '),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('range')
            ->get();

        // Rating distribution
        $ratingDistribution = $book->reviews()
            ->select('rating', DB::raw('COUNT(*) as count'))
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get();

        // Daily views (last 30 days)
        $dailyViews = $book->readingSessions()
            ->select(DB::raw('DATE(start_time) as date'), DB::raw('COUNT(*) as views'))
            ->where('start_time', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'book' => $book,
                'basic_stats' => $stats,
                'progress_distribution' => $progressDistribution,
                'rating_distribution' => $ratingDistribution,
                'daily_views' => $dailyViews
            ]
        ]);
    }

    /**
     * Get system overview statistics
     */
    public function systemStats(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'premium_users' => User::where('is_premium', true)->count(),
            'total_books' => Book::count(),
            'published_books' => Book::published()->count(),
            'total_authors' => Author::count(),
            'total_categories' => Category::count(),
            'total_reviews' => Review::count(),
            'total_reading_sessions' => ReadingSession::count(),
            'total_reading_time' => ReadingSession::sum('duration') // in minutes
        ];

        // New users by month (last 12 months)
        $newUsers = User::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Most popular books
        $popularBooks = Book::select('id', 'title', 'views', 'downloads')
            ->orderBy('views', 'desc')
            ->limit(10)
            ->get();

        // Most active categories
        $activeCategories = Category::select('categories.name', DB::raw('COUNT(*) as books_count'))
            ->join('books', 'categories.id', '=', 'books.category_id')
            ->where('books.status', 'published')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('books_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'basic_stats' => $stats,
                'new_users_trend' => $newUsers,
                'popular_books' => $popularBooks,
                'active_categories' => $activeCategories
            ]
        ]);
    }

    /**
     * Get reading trends
     */
    public function readingTrends(Request $request): JsonResponse
    {
        $period = $request->get('period', '30'); // days

        // Reading sessions trend
        $sessionsTrend = ReadingSession::select(
                DB::raw('DATE(start_time) as date'),
                DB::raw('COUNT(*) as sessions_count'),
                DB::raw('SUM(duration) as total_duration')
            )
            ->where('start_time', '>=', now()->subDays($period))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Most read books
        $mostReadBooks = Book::select('books.id', 'books.title', 'books.slug', DB::raw('COUNT(*) as readers_count'))
            ->join('reading_sessions', 'books.id', '=', 'reading_sessions.book_id')
            ->where('reading_sessions.start_time', '>=', now()->subDays($period))
            ->groupBy('books.id', 'books.title', 'books.slug')
            ->orderBy('readers_count', 'desc')
            ->limit(10)
            ->get();

        // Peak reading hours
        $peakHours = ReadingSession::select(
                DB::raw('HOUR(start_time) as hour'),
                DB::raw('COUNT(*) as sessions_count')
            )
            ->where('start_time', '>=', now()->subDays($period))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'sessions_trend' => $sessionsTrend,
                'most_read_books' => $mostReadBooks,
                'peak_hours' => $peakHours
            ]
        ]);
    }

    /**
     * Calculate current reading streak for user
     */
    private function calculateCurrentStreak(User $user): int
    {
        $sessions = $user->readingSessions()
            ->select(DB::raw('DATE(start_time) as date'))
            ->distinct()
            ->orderBy('date', 'desc')
            ->get();

        $streak = 0;
        $currentDate = now()->startOfDay();

        foreach ($sessions as $session) {
            $sessionDate = Carbon::parse($session->date)->startOfDay();
            
            if ($sessionDate->eq($currentDate) || $sessionDate->eq($currentDate->subDay())) {
                $streak++;
                $currentDate = $sessionDate;
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Calculate longest reading streak for user
     */
    private function calculateLongestStreak(User $user): int
    {
        $sessions = $user->readingSessions()
            ->select(DB::raw('DATE(start_time) as date'))
            ->distinct()
            ->orderBy('date')
            ->get();

        $longestStreak = 0;
        $currentStreak = 0;
        $previousDate = null;

        foreach ($sessions as $session) {
            $sessionDate = Carbon::parse($session->date);
            
            if ($previousDate && $sessionDate->diffInDays($previousDate) === 1) {
                $currentStreak++;
            } else {
                $currentStreak = 1;
            }
            
            $longestStreak = max($longestStreak, $currentStreak);
            $previousDate = $sessionDate;
        }

        return $longestStreak;
    }
}
