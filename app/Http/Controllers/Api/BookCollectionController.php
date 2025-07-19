<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookCollection;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BookCollectionController extends Controller
{
    /**
     * Display a listing of collections
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $collections = BookCollection::with(['books' => function ($query) {
            $query->take(5);
        }])
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $collections
        ]);
    }

    /**
     * Display public collections
     */
    public function public(Request $request): JsonResponse
    {
        $collections = BookCollection::with(['user:id,name,avatar', 'books' => function ($query) {
            $query->take(5);
        }])
        ->public()
        ->orderBy('created_at', 'desc')
        ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $collections
        ]);
    }

    /**
     * Display featured collections
     */
    public function featured(Request $request): JsonResponse
    {
        $collections = BookCollection::with(['user:id,name,avatar', 'books' => function ($query) {
            $query->take(5);
        }])
        ->featured()
        ->public()
        ->orderBy('created_at', 'desc')
        ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $collections
        ]);
    }

    /**
     * Store a newly created collection
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|string',
            'is_public' => 'boolean',
            'book_ids' => 'array',
            'book_ids.*' => 'exists:books,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        
        $collection = BookCollection::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'cover_image' => $request->cover_image,
            'is_public' => $request->boolean('is_public', false),
        ]);

        // Add books to collection
        if ($request->filled('book_ids')) {
            foreach ($request->book_ids as $index => $bookId) {
                $collection->addBook(Book::find($bookId), $index);
            }
        }

        $collection->load('books');

        return response()->json([
            'success' => true,
            'message' => 'Collection created successfully',
            'data' => $collection
        ], 201);
    }

    /**
     * Display the specified collection
     */
    public function show(int $id): JsonResponse
    {
        $collection = BookCollection::with(['user:id,name,avatar', 'books.author', 'books.category'])
            ->find($id);

        if (!$collection) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found'
            ], 404);
        }

        // Check if collection is public or belongs to authenticated user
        $user = auth()->user();
        if (!$collection->is_public && (!$user || $collection->user_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $collection
        ]);
    }

    /**
     * Update the specified collection
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $collection = BookCollection::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$collection) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|string',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $collection->update($request->only([
            'name', 'description', 'cover_image', 'is_public'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Collection updated successfully',
            'data' => $collection
        ]);
    }

    /**
     * Remove the specified collection
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $collection = BookCollection::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$collection) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found'
            ], 404);
        }

        $collection->delete();

        return response()->json([
            'success' => true,
            'message' => 'Collection deleted successfully'
        ]);
    }

    /**
     * Add book to collection
     */
    public function addBook(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $collection = BookCollection::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$collection) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'book_id' => 'required|exists:books,id',
            'sort_order' => 'integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $book = Book::find($request->book_id);
        
        if ($collection->hasBook($book)) {
            return response()->json([
                'success' => false,
                'message' => 'Book already in collection'
            ], 400);
        }

        $collection->addBook($book, $request->get('sort_order', 0));

        return response()->json([
            'success' => true,
            'message' => 'Book added to collection successfully'
        ]);
    }

    /**
     * Remove book from collection
     */
    public function removeBook(Request $request, int $id, int $bookId): JsonResponse
    {
        $user = $request->user();
        $collection = BookCollection::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$collection) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found'
            ], 404);
        }

        $book = Book::find($bookId);
        
        if (!$book || !$collection->hasBook($book)) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found in collection'
            ], 404);
        }

        $collection->removeBook($book);

        return response()->json([
            'success' => true,
            'message' => 'Book removed from collection successfully'
        ]);
    }
}
