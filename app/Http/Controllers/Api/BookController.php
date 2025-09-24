<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookController extends Controller
{
    /**
     * Menampilkan daftar resource dengan pencarian dan filter.
     *
     * @OA\Get(
     *     path="/books",
     *     tags={"Books"},
     *     summary="Get all books with pagination and filters",
     *     description="Retrieve books dengan pagination, search dan filter berdasarkan author, year, atau title",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page (max 100)",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search keyword for title, author, or ISBN",
     *         @OA\Schema(type="string", example="Laravel")
     *     ),
     *     @OA\Parameter(
     *         name="author",
     *         in="query",
     *         description="Filter by author name",
     *         @OA\Schema(type="string", example="John Doe")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Filter by published year",
     *         @OA\Schema(type="integer", example=2023)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Books retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/BooksPaginatedResponse")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100); // Maksimal 100 item per halaman

        $books = Book::query()
            ->search($request->get('search'))
            ->byAuthor($request->get('author'))
            ->byYear($request->get('year'))
            ->orderBy('title')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $books,
            'message' => 'Books retrieved successfully',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/admin/books",
     *     tags={"Books"},
     *     summary="Create new book (admin only)",
     *     description="Create book baru dengan validasi",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CreateBookRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Book created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Book created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Book")
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'author' => 'required|string|max:255',
                'published_year' => 'required|integer|digits:4|min:1000|max:' . date('Y'),
                'isbn' => 'required|string|unique:books,isbn|max:20',
                'stock' => 'required|integer|min:0',
            ]);

            $book = Book::create($validated);

            return response()->json([
                'status' => 'success',
                'data' => $book,
                'message' => 'Book created successfully',
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Menampilkan resource yang ditentukan.
     *
     * @OA\Get(
     *     path="/books/{id}",
     *     tags={"Books"},
     *     summary="Get book by ID",
     *     description="Retrieve book berdasarkan ID dengan informasi stock tersedia",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book ID",
     *         @OA\Schema(type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Book retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Book")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $book = Book::find($id);

        if (! $book) {
            return response()->json([
                'status' => 'error',
                'message' => 'Book not found',
            ], 404);
        }

        // Tambahkan informasi stok yang tersedia
        $bookData = $book->toArray();
        $bookData['available_stock'] = $book->available_stock;

        return response()->json([
            'status' => 'success',
            'data' => $bookData,
            'message' => 'Book retrieved successfully',
        ]);
    }

    /**
     * Update resource yang ditentukan di storage.
     *
     * @OA\Put(
     *     path="/admin/books/{id}",
     *     tags={"Books"},
     *     summary="Update book by ID (admin only)",
     *     description="Update book berdasarkan ID dengan validasi",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book ID",
     *         @OA\Schema(type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateBookRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Book updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Book")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $book = Book::find($id);

        if (! $book) {
            return response()->json([
                'status' => 'error',
                'message' => 'Book not found',
            ], 404);
        }

        try {
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'author' => 'sometimes|string|max:255',
                'published_year' => 'sometimes|integer|digits:4|min:1000|max:' . date('Y'),
                'isbn' => [
                    'sometimes',
                    'string',
                    'max:20',
                    Rule::unique('books', 'isbn')->ignore($id),
                ],
                'stock' => 'sometimes|integer|min:0',
            ]);

            $book->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $book->fresh(),
                'message' => 'Book updated successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Hapus resource yang ditentukan dari storage.
     *
     * @OA\Delete(
     *     path="/admin/books/{id}",
     *     tags={"Books"},
     *     summary="Delete book by ID (admin only)",
     *     description="Delete book berdasarkan ID (tidak bisa dihapus jika sedang dipinjam)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book ID",
     *         @OA\Schema(type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Book deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(
     *         response=409,
     *         description="Cannot delete book with active loans",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Cannot delete book with active loans")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $book = Book::find($id);

        if (! $book) {
            return response()->json([
                'status' => 'error',
                'message' => 'Book not found',
            ], 404);
        }

        // Cek apakah buku memiliki pinjaman aktif
        if ($book->activeLoans()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete book with active loans',
            ], 409);
        }

        $book->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Book deleted successfully',
        ]);
    }
}
