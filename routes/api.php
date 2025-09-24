<?php

use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BookLoanController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Di sinilah Anda dapat mendaftarkan route API untuk aplikasi Anda. Route-route
| ini dimuat oleh RouteServiceProvider dalam grup yang
| diberikan middleware grup "api". Selamat membangun API Anda!
|
*/

// Route publik
Route::group([], function () {
    // Endpoint health check
    /**
     * @OA\Get(
     *     path="/health",
     *     tags={"General"},
     *     summary="Health check",
     *     description="Check API status",
     *     @OA\Response(
     *         response=200,
     *         description="API is running",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="message", type="string", example="API is running"),
     *             @OA\Property(property="timestamp", type="string", example="2023-01-01T00:00:00.000000Z"),
     *             @OA\Property(property="version", type="string", example="1.0.0")
     *         )
     *     )
     * )
     */
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'message' => 'API is running',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
        ]);
    });

    // Route autentikasi (publik)
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthenticationController::class, 'login']);
        Route::post('/register', [AuthenticationController::class, 'register']);
    });
});

// Route yang dilindungi (memerlukan autentikasi)
Route::middleware('auth:sanctum')->group(function () {
    // Route autentikasi (dilindungi)
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthenticationController::class, 'logout']);
        Route::get('/me', [AuthenticationController::class, 'me']);
        Route::post('/refresh', [AuthenticationController::class, 'refresh']);
    });

    // Route untuk user biasa - hanya bisa melihat book dan melakukan peminjaman
    Route::prefix('books')->group(function () {
        Route::get('/', [BookController::class, 'index']); // Lihat daftar buku
        Route::get('/{id}', [BookController::class, 'show']); // Lihat detail buku
    });

    // Route pinjaman book - user biasa bisa meminjam dan mengembalikan
    Route::prefix('loans')->group(function () {
        Route::post('/', [BookLoanController::class, 'borrowBook']); // Pinjam buku
        Route::get('/my-loans', [BookLoanController::class, 'getMyLoans']); // Lihat pinjaman sendiri
        Route::put('/{loan_id}/return', [BookLoanController::class, 'returnBook']); // Kembalikan buku
    });

    // Route admin - memerlukan middleware admin
    Route::middleware('admin')->prefix('admin')->group(function () {
        // Manajemen buku - hanya admin
        Route::prefix('books')->group(function () {
            Route::post('/', [BookController::class, 'store']); // Tambah buku
            Route::put('/{id}', [BookController::class, 'update']); // Update buku
            Route::delete('/{id}', [BookController::class, 'destroy']); // Hapus buku
        });

        // Manajemen user - hanya admin yang bisa lihat daftar user
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']); // Lihat semua user
            Route::post('/', [UserController::class, 'store']); // Tambah user baru - hanya admin
            Route::get('/{id}', [UserController::class, 'show']); // Lihat detail user
            Route::put('/{id}', [UserController::class, 'update']); // Update user
            Route::delete('/{id}', [UserController::class, 'destroy']); // Hapus user
        });

        // Manajemen pinjaman - admin bisa lihat semua pinjaman
        Route::prefix('loans')->group(function () {
            Route::get('/', [BookLoanController::class, 'getAllLoans']); // Lihat semua pinjaman
            Route::get('/user/{user_id}', [BookLoanController::class, 'getUserLoans']); // Lihat pinjaman user tertentu
        });
    });

    // Route untuk mendapatkan user yang terautentikasi
    Route::get('/user', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'data' => $request->user(),
            'message' => 'Authenticated user retrieved successfully',
        ]);
    });
});
