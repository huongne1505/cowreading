<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ReadingProgressController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\BookCollectionController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\AdminController;
use Illuminate\Support\Facades\Route;

// Test routes
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!', 'timestamp' => now()]);
});

Route::post('/test-post', function () {
    return response()->json(['message' => 'POST request is working!', 'data' => request()->all()]);
});

// Auth routes with prefix
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    
    // OAuth routes
    Route::get('/facebook', [AuthController::class, 'redirectToFacebook']);
    Route::get('/facebook/callback', [AuthController::class, 'handleFacebookCallback']);
    Route::get('/google', [AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

// Public book routes
Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{slug}', [BookController::class, 'show']);
Route::get('/books/{slug}/chapters', [ChapterController::class, 'getBookChapters']);
Route::get('/books/{slug}/reviews', [ReviewController::class, 'getBookReviews']);

// Public author routes
Route::get('/authors', [AuthorController::class, 'index']);
Route::get('/authors/{id}', [AuthorController::class, 'show']);

// Public category routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/categories/{id}/books', [CategoryController::class, 'getBooks']);

// Public search routes
Route::get('/search', [SearchController::class, 'globalSearch']);
Route::get('/search/advanced', [SearchController::class, 'advancedSearch']);
Route::get('/search/suggestions', [SearchController::class, 'searchSuggestions']);
Route::get('/search/popular', [SearchController::class, 'getPopularSearches']);
Route::get('/search/filters', [SearchController::class, 'getSearchFilters']);
Route::get('/search/books/{slug}', [SearchController::class, 'searchInBook']);

// Public recommendation routes
Route::get('/recommendations/trending', [RecommendationController::class, 'getTrendingBooks']);
Route::get('/recommendations/new-releases', [RecommendationController::class, 'getNewReleases']);
Route::get('/recommendations/similar/{slug}', [RecommendationController::class, 'getSimilarBooks']);
Route::get('/recommendations/genre/{categoryId}', [RecommendationController::class, 'getBooksByGenre']);

// Public collection routes
Route::get('/collections/public', [BookCollectionController::class, 'getPublicCollections']);
Route::get('/collections/featured', [BookCollectionController::class, 'getFeaturedCollections']);
Route::get('/collections/{id}', [BookCollectionController::class, 'show']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);
    Route::post('/user/avatar', [AuthController::class, 'uploadAvatar']);
    Route::get('/user/reading-history', [AuthController::class, 'readingHistory']);
    Route::get('/user/favorites', [AuthController::class, 'favorites']);
    
    // Book interaction routes
    Route::post('/books/{slug}/view', [BookController::class, 'recordView']);
    Route::post('/books/{slug}/download', [BookController::class, 'download']);
    
    // Chapter routes
    Route::get('/chapters/{id}', [ChapterController::class, 'show']);
    
    // Review routes
    Route::post('/books/{slug}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
    Route::get('/user/reviews', [ReviewController::class, 'getUserReviews']);
    
    // Favorite routes
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites/{bookId}', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{bookId}', [FavoriteController::class, 'destroy']);
    Route::get('/favorites/check/{bookId}', [FavoriteController::class, 'checkFavorite']);
    
    // Reading progress routes
    Route::get('/reading-progress', [ReadingProgressController::class, 'index']);
    Route::post('/reading-progress', [ReadingProgressController::class, 'store']);
    Route::put('/reading-progress/{id}', [ReadingProgressController::class, 'update']);
    Route::delete('/reading-progress/{id}', [ReadingProgressController::class, 'destroy']);
    Route::get('/reading-progress/book/{bookId}', [ReadingProgressController::class, 'getBookProgress']);
    
    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    
    // Collection routes
    Route::get('/collections', [BookCollectionController::class, 'index']);
    Route::post('/collections', [BookCollectionController::class, 'store']);
    Route::put('/collections/{id}', [BookCollectionController::class, 'update']);
    Route::delete('/collections/{id}', [BookCollectionController::class, 'destroy']);
    Route::post('/collections/{id}/books/{bookId}', [BookCollectionController::class, 'addBook']);
    Route::delete('/collections/{id}/books/{bookId}', [BookCollectionController::class, 'removeBook']);
    Route::get('/user/collections', [BookCollectionController::class, 'getUserCollections']);
    
    // Analytics routes
    Route::get('/analytics/user', [AnalyticsController::class, 'userStats']);
    Route::get('/analytics/reading-trends', [AnalyticsController::class, 'readingTrends']);
    
    // Personalized recommendation routes
    Route::get('/recommendations', [RecommendationController::class, 'getRecommendations']);
});

// Public analytics routes
Route::get('/analytics/book/{slug}', [AnalyticsController::class, 'bookStats']);
Route::get('/analytics/system', [AnalyticsController::class, 'systemStats']);

// Admin routes (require admin authentication)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/analytics', [AdminController::class, 'getAnalytics']);
    Route::get('/activities', [AdminController::class, 'getRecentActivities']);
    
    // User management
    Route::get('/users', [AdminController::class, 'getUsers']);
    Route::put('/users/{id}/status', [AdminController::class, 'updateUserStatus']);
    Route::post('/users/admin', [AdminController::class, 'createAdmin']);
    
    // Book management
    Route::get('/books', [AdminController::class, 'getBooks']);
    Route::put('/books/{id}/status', [AdminController::class, 'updateBookStatus']);
    Route::post('/books', [BookController::class, 'store']);
    Route::put('/books/{id}', [BookController::class, 'update']);
    Route::delete('/books/{id}', [BookController::class, 'destroy']);
    
    // Author management
    Route::post('/authors', [AuthorController::class, 'store']);
    Route::put('/authors/{id}', [AuthorController::class, 'update']);
    Route::delete('/authors/{id}', [AuthorController::class, 'destroy']);
    
    // Category management
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    
    // Chapter management
    Route::post('/chapters', [ChapterController::class, 'store']);
    Route::put('/chapters/{id}', [ChapterController::class, 'update']);
    Route::delete('/chapters/{id}', [ChapterController::class, 'destroy']);
    
    // Review management
    Route::get('/reviews', [AdminController::class, 'getReviews']);
    Route::put('/reviews/{id}/status', [AdminController::class, 'updateReviewStatus']);
    
    // System settings
    Route::get('/settings', [AdminController::class, 'getSettings']);
    Route::put('/settings', [AdminController::class, 'updateSettings']);
});
