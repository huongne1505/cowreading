<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\User;
use App\Models\Author;
use App\Models\Category;
use App\Models\Review;
use App\Models\ReadingSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    /**
     * Get admin dashboard overview
     */
    public function dashboard(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'premium_users' => User::where('is_premium', true)->count(),
            'total_books' => Book::count(),
            'published_books' => Book::published()->count(),
            'draft_books' => Book::where('status', 'draft')->count(),
            'total_authors' => Author::count(),
            'total_categories' => Category::count(),
            'total_reviews' => Review::count(),
            'pending_reviews' => Review::where('status', 'pending')->count(),
            'total_reading_sessions' => ReadingSession::count(),
            'today_sessions' => ReadingSession::whereDate('start_time', today())->count()
        ];

        // Recent activities
        $recentUsers = User::latest()->limit(5)->get(['id', 'name', 'email', 'created_at']);
        $recentBooks = Book::latest()->limit(5)->with('author')->get(['id', 'title', 'author_id', 'status', 'created_at']);
        $recentReviews = Review::latest()->limit(5)->with(['user', 'book'])->get();

        // Growth metrics (last 30 days)
        $growthMetrics = [
            'new_users' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'new_books' => Book::where('created_at', '>=', now()->subDays(30))->count(),
            'new_reviews' => Review::where('created_at', '>=', now()->subDays(30))->count(),
            'reading_sessions' => ReadingSession::where('start_time', '>=', now()->subDays(30))->count()
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_activities' => [
                    'users' => $recentUsers,
                    'books' => $recentBooks,
                    'reviews' => $recentReviews
                ],
                'growth_metrics' => $growthMetrics
            ]
        ]);
    }

    /**
     * Get all users with pagination and filters
     */
    public function getUsers(Request $request): JsonResponse
    {
        $query = User::query();

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('is_active', $status === 'active');
        }

        // Premium filter
        if ($premium = $request->get('premium')) {
            $query->where('is_premium', $premium === 'true');
        }

        // Role filter
        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        $users = $query->withCount(['readingProgress', 'reviews', 'favoriteBooks'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Update user status
     */
    public function updateUserStatus(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'is_active' => 'required|boolean',
            'is_premium' => 'boolean',
            'role' => 'in:user,admin,moderator'
        ]);

        $user = User::findOrFail($userId);
        
        $user->update([
            'is_active' => $request->is_active,
            'is_premium' => $request->is_premium ?? $user->is_premium,
            'role' => $request->role ?? $user->role
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Get all books with admin-specific data
     */
    public function getBooks(Request $request): JsonResponse
    {
        $query = Book::with(['author', 'category']);

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('isbn', 'LIKE', "%{$search}%")
                  ->orWhereHas('author', function ($authorQuery) use ($search) {
                      $authorQuery->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Category filter
        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $books = $query->withCount(['reviews', 'readingProgress'])
            ->withAvg('reviews', 'rating')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $books
        ]);
    }

    /**
     * Update book status
     */
    public function updateBookStatus(Request $request, int $bookId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:draft,published,unpublished'
        ]);

        $book = Book::findOrFail($bookId);
        $book->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Book status updated successfully',
            'data' => $book
        ]);
    }

    /**
     * Get all reviews with moderation capabilities
     */
    public function getReviews(Request $request): JsonResponse
    {
        $query = Review::with(['user', 'book']);

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Rating filter
        if ($rating = $request->get('rating')) {
            $query->where('rating', $rating);
        }

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('book', function ($bookQuery) use ($search) {
                      $bookQuery->where('title', 'LIKE', "%{$search}%");
                  });
            });
        }

        $reviews = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    /**
     * Update review status
     */
    public function updateReviewStatus(Request $request, int $reviewId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected'
        ]);

        $review = Review::findOrFail($reviewId);
        $review->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Review status updated successfully',
            'data' => $review
        ]);
    }

    /**
     * Get system analytics
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $period = $request->get('period', 30); // days

        // User registration trend
        $userGrowth = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays($period))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Book publication trend
        $bookGrowth = Book::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays($period))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Reading activity trend
        $readingActivity = ReadingSession::select(
                DB::raw('DATE(start_time) as date'),
                DB::raw('COUNT(*) as sessions'),
                DB::raw('SUM(duration) as total_duration')
            )
            ->where('start_time', '>=', now()->subDays($period))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top categories by book count
        $topCategories = Category::select('categories.name', DB::raw('COUNT(*) as books_count'))
            ->join('books', 'categories.id', '=', 'books.category_id')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('books_count', 'desc')
            ->limit(10)
            ->get();

        // Top authors by book count
        $topAuthors = Author::select('authors.name', DB::raw('COUNT(*) as books_count'))
            ->join('books', 'authors.id', '=', 'books.author_id')
            ->groupBy('authors.id', 'authors.name')
            ->orderBy('books_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user_growth' => $userGrowth,
                'book_growth' => $bookGrowth,
                'reading_activity' => $readingActivity,
                'top_categories' => $topCategories,
                'top_authors' => $topAuthors
            ]
        ]);
    }

    /**
     * Create a new admin user
     */
    public function createAdmin(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::defaults()],
            'role' => 'required|in:admin,moderator'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
            'email_verified_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin user created successfully',
            'data' => $user
        ]);
    }

    /**
     * Get system settings
     */
    public function getSettings(): JsonResponse
    {
        // This would ideally come from a settings table
        $settings = [
            'site_name' => 'CowReading',
            'allow_registration' => true,
            'require_email_verification' => true,
            'auto_approve_reviews' => false,
            'max_upload_size' => '10MB',
            'supported_formats' => ['pdf', 'epub', 'mobi'],
            'premium_features' => [
                'unlimited_downloads',
                'offline_reading',
                'advanced_bookmarks',
                'reading_analytics'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update system settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'site_name' => 'string|max:255',
            'allow_registration' => 'boolean',
            'require_email_verification' => 'boolean',
            'auto_approve_reviews' => 'boolean',
            'max_upload_size' => 'string',
            'supported_formats' => 'array',
            'premium_features' => 'array'
        ]);

        // In a real application, you would save these to a settings table
        // For now, we'll just return success
        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Get recent activities for admin dashboard
     */
    public function getRecentActivities(): JsonResponse
    {
        $activities = collect();

        // Recent user registrations
        $recentUsers = User::latest()
            ->limit(5)
            ->get(['id', 'name', 'email', 'created_at'])
            ->map(function ($user) {
                return [
                    'type' => 'user_registered',
                    'description' => "New user registered: {$user->name}",
                    'timestamp' => $user->created_at,
                    'data' => $user
                ];
            });

        // Recent book additions
        $recentBooks = Book::latest()
            ->limit(5)
            ->with('author')
            ->get()
            ->map(function ($book) {
                return [
                    'type' => 'book_added',
                    'description' => "New book added: {$book->title} by {$book->author->name}",
                    'timestamp' => $book->created_at,
                    'data' => $book
                ];
            });

        // Recent reviews
        $recentReviews = Review::latest()
            ->limit(5)
            ->with(['user', 'book'])
            ->get()
            ->map(function ($review) {
                return [
                    'type' => 'review_posted',
                    'description' => "{$review->user->name} reviewed {$review->book->title}",
                    'timestamp' => $review->created_at,
                    'data' => $review
                ];
            });

        $activities = $activities
            ->merge($recentUsers)
            ->merge($recentBooks)
            ->merge($recentReviews)
            ->sortByDesc('timestamp')
            ->take(15)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }
}
