<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendBookLoanNotification;
use App\Models\Book;
use App\Models\BookLoan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookLoanController extends Controller
{
    /**
     * User meminjam buku.
     *
     * @OA\Post(
     *     path="/loans",
     *     tags={"Book Loans"},
     *     summary="Borrow a book",
     *     description="User meminjam buku dengan validasi stock",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BorrowBookRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Book borrowed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Book borrowed successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/BookLoan")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Book not available or already borrowed by user",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Book is not available for loan")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public function borrowBook(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'book_id' => 'required|string|exists:books,id',
                'loan_days' => 'sometimes|integer|min:1|max:30', // Maksimal 30 hari pinjaman
            ]);

            $user = auth()->user();
            $book = Book::find($validated['book_id']);
            $loanDays = $validated['loan_days'] ?? 14; // Default 14 hari

            // Periksa apakah ada pinjaman lama dengan kombinasi user_id dan book_id
            $existingLoan = BookLoan::where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->first();

            if ($existingLoan && $existingLoan->status === 'borrowed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have already borrowed this book and haven\'t returned it yet',
                ], 400);
            }

            // Jika ada pinjaman lama dengan status "returned", hapus entri lama
            if ($existingLoan && $existingLoan->status === 'returned') {
                $existingLoan->delete();
            }

            // Cek apakah buku tersedia (stok > yang sedang dipinjam)
            if ($book->available_stock <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Book is not available for loan',
                ], 400);
            }

            // Buat record pinjaman baru
            $loan = DB::transaction(function () use ($user, $book, $loanDays) {
                return BookLoan::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'loan_date' => Carbon::now(),
                    'due_date' => Carbon::now()->addDays($loanDays),
                    'status' => 'borrowed',
                ]);
            });

            // Load relasi untuk response
            $loan->load(['user:id,name,email', 'book:id,title,author,isbn']);

            // Dispatch job untuk mengirim email notifikasi
            SendBookLoanNotification::dispatch($loan);

            return response()->json([
                'status' => 'success',
                'data' => $loan,
                'message' => 'Book borrowed successfully',
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
     * Get current user's loans.
     *
     * @OA\Get(
     *     path="/loans/my-loans",
     *     tags={"Book Loans"},
     *     summary="Get current user's loans",
     *     description="Get all loans for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by loan status (borrowed, returned, overdue, all)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"borrowed", "returned", "overdue", "all"}, default="borrowed")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User loans retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Your loans retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/BookLoan"))
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public function getMyLoans(Request $request): JsonResponse
    {
        $user = auth()->user();
        $status = $request->get('status', 'borrowed');

        $query = $user->bookLoans()->with(['book:id,title,author,isbn,stock']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $loans = $query->orderBy('loan_date', 'desc')->get();

        // Add additional information
        $loans = $loans->map(function ($loan) {
            $loanData = $loan->toArray();
            $loanData['is_overdue'] = $loan->isOverdue();
            $loanData['days_until_due'] = $loan->days_until_due;

            return $loanData;
        });

        return response()->json([
            'status' => 'success',
            'data' => $loans,
            'message' => 'Your loans retrieved successfully',
        ]);
    }

    /**
     * Mendapatkan buku yang dipinjam user.
     *
     * @OA\Get(
     *     path="/admin/loans/{user_id}",
     *     tags={"Book Loans"},
     *     summary="Get user's borrowed books by user ID",
     *     description="Daftar buku yang sedang dipinjam oleh user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by loan status (borrowed, returned, or all)",
     *         @OA\Schema(type="string", enum={"borrowed", "returned", "all"}, example="borrowed")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User loans retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="User loans retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/BookLoan"))
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public function getUserLoans(Request $request, string $userId): JsonResponse
    {
        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        // Cek apakah user saat ini dapat mengakses data ini (admin atau data sendiri)
        $currentUser = auth()->user();
        if ($currentUser->id != $userId && ! $this->isAdmin($currentUser)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to access this user\'s loans',
            ], 403);
        }

        $status = $request->get('status', 'borrowed');

        $query = $user->bookLoans()->with(['book:id,title,author,isbn,stock']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $loans = $query->orderBy('loan_date', 'desc')->get();

        // Add additional information
        $loans = $loans->map(function ($loan) {
            $loanData = $loan->toArray();
            $loanData['is_overdue'] = $loan->isOverdue();
            $loanData['days_until_due'] = $loan->days_until_due;

            return $loanData;
        });

        return response()->json([
            'status' => 'success',
            'data' => $loans,
            'message' => 'User loans retrieved successfully',
        ]);
    }

    /**
     * Mengembalikan buku yang dipinjam.
     *
     * @OA\Put(
     *     path="/loans/{loan_id}/return",
     *     tags={"Book Loans"},
     *     summary="Return a borrowed book",
     *     description="User mengembalikan buku yang dipinjam",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loan_id",
     *         in="path",
     *         required=true,
     *         description="Loan ID",
     *         @OA\Schema(type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book returned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Book returned successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/BookLoan")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Book already returned or invalid loan",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Book has already been returned")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public function returnBook(string $loanId): JsonResponse
    {
        $bookLoan = BookLoan::find($loanId);

        if (! $bookLoan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Loan not found',
            ], 404);
        }

        // Validasi untuk memastikan tidak melanggar constraint unik
        $existingLoan = BookLoan::where('user_id', $bookLoan->user_id)
            ->where('book_id', $bookLoan->book_id)
            ->where('status', 'returned')
            ->first();

        if ($existingLoan) {
            return response()->json([
                'status' => 'error',
                'message' => 'This loan has already been returned.',
            ], 400);
        }

        $bookLoan->update([
            'return_date' => now(),
            'status' => 'returned',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Book returned successfully',
            'data' => $bookLoan,
        ]);
    }

    /**
     * Get all loans (admin only).
     *
     * @OA\Get(
     *     path="/admin/loans",
     *     tags={"Book Loans"},
     *     summary="Get all loans (admin only)",
     *     description="Daftar semua peminjaman buku (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by loan status",
     *         @OA\Schema(type="string", enum={"borrowed", "returned", "overdue", "all"})
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loans retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Loans retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Admin access required",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Admin access required")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public function getAllLoans(Request $request): JsonResponse
    {
        $currentUser = auth()->user();

        // Cek akses admin (Anda dapat mengimplementasikan logika pengecekan admin sendiri)
        if (! $this->isAdmin($currentUser)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin access required',
            ], 403);
        }

        $status = $request->get('status', 'all');

        $query = BookLoan::with(['user:id,name,email', 'book:id,title,author,isbn']);

        switch ($status) {
            case 'borrowed':
                $query->active();

                break;
            case 'returned':
                $query->returned();

                break;
            case 'overdue':
                $query->overdue();

                break;
            default:
                // Tampilkan semua
                break;
        }

        $loans = $query->orderBy('loan_date', 'desc')->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $loans,
            'message' => 'Loans retrieved successfully',
        ]);
    }

    /**
     * Simple admin check - you can implement your own logic
     */
    private function isAdmin($user): bool
    {
        // Untuk keperluan demo, anggap user dengan email 'admin@example.com' sebagai admin
        // Dalam aplikasi nyata, Anda akan memiliki sistem roles/permissions
        return $user->email === 'admin@example.com';
    }
}
