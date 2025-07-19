<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Global search across books, authors, and categories
     */
    public function globalSearch(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all'); // all, books, authors, categories
        $limit = $request->get('limit', 10);

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required'
            ], 400);
        }

        $results = [];

        // Search books
        if ($type === 'all' || $type === 'books') {
            $books = Book::published()
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%")
                      ->orWhere('isbn', 'LIKE', "%{$query}%");
                })
                ->with(['author', 'category'])
                ->limit($limit)
                ->get();

            $results['books'] = $books;
        }

        // Search authors
        if ($type === 'all' || $type === 'authors') {
            $authors = Author::where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('bio', 'LIKE', "%{$query}%");
                })
                ->withCount('books')
                ->limit($limit)
                ->get();

            $results['authors'] = $authors;
        }

        // Search categories
        if ($type === 'all' || $type === 'categories') {
            $categories = Category::where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%");
                })
                ->withCount('books')
                ->limit($limit)
                ->get();

            $results['categories'] = $categories;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'query' => $query,
                'results' => $results,
                'total_results' => collect($results)->sum(function ($items) {
                    return $items->count();
                })
            ]
        ]);
    }

    /**
     * Advanced book search with filters
     */
    public function advancedSearch(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $categoryId = $request->get('category_id');
        $authorId = $request->get('author_id');
        $minRating = $request->get('min_rating');
        $maxRating = $request->get('max_rating');
        $sortBy = $request->get('sort_by', 'relevance'); // relevance, title, rating, views, created_at
        $sortDirection = $request->get('sort_direction', 'desc');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 12);

        $books = Book::published()
            ->with(['author', 'category'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        // Text search
        if (!empty($query)) {
            $books->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhere('isbn', 'LIKE', "%{$query}%")
                  ->orWhereHas('author', function ($authorQuery) use ($query) {
                      $authorQuery->where('name', 'LIKE', "%{$query}%");
                  });
            });
        }

        // Category filter
        if ($categoryId) {
            $books->where('category_id', $categoryId);
        }

        // Author filter
        if ($authorId) {
            $books->where('author_id', $authorId);
        }

        // Rating filter
        if ($minRating || $maxRating) {
            $books->having('reviews_avg_rating', '>=', $minRating ?: 0);
            if ($maxRating) {
                $books->having('reviews_avg_rating', '<=', $maxRating);
            }
        }

        // Sorting
        switch ($sortBy) {
            case 'title':
                $books->orderBy('title', $sortDirection);
                break;
            case 'rating':
                $books->orderBy('reviews_avg_rating', $sortDirection);
                break;
            case 'views':
                $books->orderBy('views', $sortDirection);
                break;
            case 'created_at':
                $books->orderBy('created_at', $sortDirection);
                break;
            default: // relevance
                if (!empty($query)) {
                    $books->orderByRaw("
                        CASE 
                            WHEN title LIKE '%{$query}%' THEN 1
                            WHEN description LIKE '%{$query}%' THEN 2
                            ELSE 3
                        END
                    ");
                }
                $books->orderBy('views', 'desc');
                break;
        }

        $results = $books->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => [
                'books' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem()
                ],
                'filters' => [
                    'query' => $query,
                    'category_id' => $categoryId,
                    'author_id' => $authorId,
                    'min_rating' => $minRating,
                    'max_rating' => $maxRating,
                    'sort_by' => $sortBy,
                    'sort_direction' => $sortDirection
                ]
            ]
        ]);
    }

    /**
     * Search suggestions (autocomplete)
     */
    public function searchSuggestions(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 5);

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [
                    'suggestions' => []
                ]
            ]);
        }

        $suggestions = [];

        // Book title suggestions
        $bookTitles = Book::published()
            ->where('title', 'LIKE', "{$query}%")
            ->limit($limit)
            ->pluck('title')
            ->map(function ($title) {
                return [
                    'type' => 'book',
                    'text' => $title,
                    'category' => 'Books'
                ];
            });

        // Author name suggestions
        $authorNames = Author::where('name', 'LIKE', "{$query}%")
            ->limit($limit)
            ->pluck('name')
            ->map(function ($name) {
                return [
                    'type' => 'author',
                    'text' => $name,
                    'category' => 'Authors'
                ];
            });

        // Category name suggestions
        $categoryNames = Category::where('name', 'LIKE', "{$query}%")
            ->limit($limit)
            ->pluck('name')
            ->map(function ($name) {
                return [
                    'type' => 'category',
                    'text' => $name,
                    'category' => 'Categories'
                ];
            });

        $suggestions = collect()
            ->merge($bookTitles)
            ->merge($authorNames)
            ->merge($categoryNames)
            ->take($limit * 3);

        return response()->json([
            'success' => true,
            'data' => [
                'suggestions' => $suggestions->values(),
                'query' => $query
            ]
        ]);
    }

    /**
     * Get popular search terms
     */
    public function getPopularSearches(): JsonResponse
    {
        // This would ideally come from a search_logs table
        // For now, return popular books and authors
        $popularBooks = Book::published()
            ->orderBy('views', 'desc')
            ->limit(10)
            ->pluck('title');

        $popularAuthors = Author::withCount('books')
            ->orderBy('books_count', 'desc')
            ->limit(5)
            ->pluck('name');

        $popularCategories = Category::withCount('books')
            ->orderBy('books_count', 'desc')
            ->limit(5)
            ->pluck('name');

        return response()->json([
            'success' => true,
            'data' => [
                'popular_books' => $popularBooks,
                'popular_authors' => $popularAuthors,
                'popular_categories' => $popularCategories
            ]
        ]);
    }

    /**
     * Search within a specific book's content
     */
    public function searchInBook(Request $request, string $slug): JsonResponse
    {
        $book = Book::where('slug', $slug)->first();
        
        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found'
            ], 404);
        }

        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required'
            ], 400);
        }

        // Search in chapters
        $chapters = $book->chapters()
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('content', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(function ($chapter) use ($query) {
                // Extract context around the search term
                $content = $chapter->content;
                $position = stripos($content, $query);
                
                if ($position !== false) {
                    $start = max(0, $position - 100);
                    $end = min(strlen($content), $position + strlen($query) + 100);
                    $context = substr($content, $start, $end - $start);
                    
                    // Highlight the search term
                    $context = str_ireplace($query, "<mark>{$query}</mark>", $context);
                    $chapter->search_context = $context;
                }
                
                return $chapter;
            });

        return response()->json([
            'success' => true,
            'data' => [
                'book' => $book,
                'query' => $query,
                'chapters' => $chapters,
                'total_results' => $chapters->count()
            ]
        ]);
    }

    /**
     * Get search filters data
     */
    public function getSearchFilters(): JsonResponse
    {
        $categories = Category::withCount('books')
            ->orderBy('name')
            ->get(['id', 'name', 'books_count']);

        $authors = Author::withCount('books')
            ->orderBy('name')
            ->get(['id', 'name', 'books_count']);

        $ratingRanges = [
            ['min' => 4, 'max' => 5, 'label' => '4+ Stars'],
            ['min' => 3, 'max' => 5, 'label' => '3+ Stars'],
            ['min' => 2, 'max' => 5, 'label' => '2+ Stars'],
            ['min' => 1, 'max' => 5, 'label' => '1+ Stars'],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'authors' => $authors,
                'rating_ranges' => $ratingRanges,
                'sort_options' => [
                    ['value' => 'relevance', 'label' => 'Relevance'],
                    ['value' => 'title', 'label' => 'Title'],
                    ['value' => 'rating', 'label' => 'Rating'],
                    ['value' => 'views', 'label' => 'Popularity'],
                    ['value' => 'created_at', 'label' => 'Newest'],
                ]
            ]
        ]);
    }
}
