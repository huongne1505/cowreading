<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Display a listing of all categories with book counts
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $categories = Category::withCount('books')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'total' => $categories->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified category with book count
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = Category::withCount('books')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get all books in a specific category
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function getBooks(int $id, Request $request): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);
            
            $query = $category->books()->with(['authors', 'category']);
            
            // Add sorting
            $sortBy = $request->get('sort_by', 'title');
            $sortDirection = $request->get('sort_direction', 'asc');
            
            if (in_array($sortBy, ['title', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortDirection);
            }
            
            // Add pagination
            $perPage = min($request->get('per_page', 12), 50);
            $books = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $books,
                'category' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching books for category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}