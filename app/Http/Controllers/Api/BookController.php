<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BookController extends Controller
{
    /**
     * Display a listing of books
     */
    public function index(Request $request): JsonResponse
    {
        $query = Book::with(['author', 'category'])
            ->published();

        // Search by title, author, or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('author', function ($author) use ($search) {
                      $author->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by author
        if ($request->filled('author_id')) {
            $query->where('author_id', $request->author_id);
        }

        // Filter by premium/free
        if ($request->filled('is_premium')) {
            $query->where('is_premium', $request->boolean('is_premium'));
        }

        // Filter by featured
        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // Filter by language
        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $validSortFields = ['title', 'created_at', 'published_at', 'views', 'downloads'];
        if (in_array($sortBy, $validSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $books = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $books
        ]);
    }

    /**
     * Display featured books
     */
    public function featured(Request $request): JsonResponse
    {
        $books = Book::with(['author', 'category'])
            ->published()
            ->featured()
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $books
        ]);
    }

    /**
     * Display popular books
     */
    public function popular(Request $request): JsonResponse
    {
        $books = Book::with(['author', 'category'])
            ->published()
            ->orderBy('views', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $books
        ]);
    }

    /**
     * Display latest books
     */
    public function latest(Request $request): JsonResponse
    {
        $books = Book::with(['author', 'category'])
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $books
        ]);
    }

    /**
     * Display the specified book
     */
    public function show(string $slug): JsonResponse
    {
        $book = Book::with(['author', 'category', 'chapters' => function ($query) {
            $query->published()->orderBy('chapter_number');
        }])
        ->where('slug', $slug)
        ->published()
        ->first();

        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found'
            ], 404);
        }

        // Increment views
        $book->incrementViews();

        // Get average rating and reviews count
        $book->loadCount('reviews');
        $book->setAttribute('average_rating', $book->average_rating);
        $book->setAttribute('total_reviews', $book->total_reviews);

        return response()->json([
            'success' => true,
            'data' => $book
        ]);
    }

    /**
     * Get book reviews
     */
    public function reviews(string $slug, Request $request): JsonResponse
    {
        $book = Book::where('slug', $slug)->published()->first();

        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found'
            ], 404);
        }

        $reviews = $book->reviews()
            ->with('user:id,name,avatar')
            ->approved()
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    /**
     * Download book
     */
    public function download(string $slug, Request $request): JsonResponse
    {
        $book = Book::where('slug', $slug)->published()->first();

        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found'
            ], 404);
        }

        // Check if user has permission to download premium books
        if ($book->is_premium && !$request->user()?->isPremium()) {
            return response()->json([
                'success' => false,
                'message' => 'Premium membership required'
            ], 403);
        }

        if (!$book->file_path) {
            return response()->json([
                'success' => false,
                'message' => 'Download not available'
            ], 404);
        }

        // Increment downloads
        $book->incrementDownloads();

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => asset('storage/' . $book->file_path),
                'file_size' => $book->formatted_file_size,
                'file_name' => $book->title . '.pdf'
            ]
        ]);
    }

    /**
     * Search books
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2',
            'category_id' => 'nullable|exists:categories,id',
            'author_id' => 'nullable|exists:authors,id',
            'language' => 'nullable|string',
            'is_premium' => 'nullable|boolean',
            'sort_by' => 'nullable|in:title,created_at,published_at,views,downloads',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Book::with(['author', 'category'])
            ->published();

        // Search in title, description, and author name
        $searchTerm = $request->q;
        $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
              ->orWhere('description', 'like', "%{$searchTerm}%")
              ->orWhereHas('author', function ($author) use ($searchTerm) {
                  $author->where('name', 'like', "%{$searchTerm}%");
              });
        });

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('author_id')) {
            $query->where('author_id', $request->author_id);
        }

        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        if ($request->filled('is_premium')) {
            $query->where('is_premium', $request->boolean('is_premium'));
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $books = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $books,
            'search_term' => $searchTerm
        ]);
    }

    /**
     * Get books by category
     */
    public function byCategory(int $categoryId, Request $request): JsonResponse
    {
        $books = Book::with(['author', 'category'])
            ->where('category_id', $categoryId)
            ->published()
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $books
        ]);
    }

    /**
     * Get books by author
     */
    public function byAuthor(int $authorId, Request $request): JsonResponse
    {
        $books = Book::with(['author', 'category'])
            ->where('author_id', $authorId)
            ->published()
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $books
        ]);
    }
}
