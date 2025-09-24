<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookLoan;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookLoanSeeder extends Seeder
{
    /**
     * Jalankan database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        if ($users->isEmpty() || $books->isEmpty()) {
            echo "⚠️  Tidak ada users atau books yang ditemukan. Silakan jalankan User dan Book seeders terlebih dahulu.\n";
            return;
        }

        $loansCreated = 0;
        $maxAttempts = 50; // Mencegah infinite loop
        $targetLoans = 25;

        for ($attempt = 0; $attempt < $maxAttempts && $loansCreated < $targetLoans; $attempt++) {
            $user = $users->random();
            $book = $books->random();

            // Cek apakah user ini sudah meminjam buku ini
            $existingActiveLoan = BookLoan::where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->where('status', 'borrowed')
                ->exists();

            // Cek apakah buku memiliki stok yang tersedia
            $availableStock = $book->stock - BookLoan::where('book_id', $book->id)
                ->where('status', 'borrowed')
                ->count();

            if (!$existingActiveLoan && $availableStock > 0) {
                // Secara acak tentukan status pinjaman dan tanggal
                $isReturned = fake()->boolean(60); // 60% kemungkinan sudah dikembalikan

                if ($isReturned) {
                    BookLoan::factory()->returned()->create([
                        'user_id' => $user->id,
                        'book_id' => $book->id,
                    ]);
                } else {
                    BookLoan::factory()->borrowed()->create([
                        'user_id' => $user->id,
                        'book_id' => $book->id,
                    ]);
                }

                $loansCreated++;

                if ($loansCreated % 5 == 0) {
                    echo "   Created {$loansCreated} loans...\n";
                }
            }
        }

        // Buat beberapa skenario pinjaman khusus untuk testing
        $this->createTestScenarios($users, $books);
    }

    /**
     * Buat skenario test khusus
     */
    private function createTestScenarios($users, $books): void
    {
        // Buat beberapa pinjaman yang terlambat untuk testing
        $overdueUser = $users->first();
        $overdueBook = $books->where('stock', '>', 0)->first();

        if ($overdueUser && $overdueBook) {
            $existingLoan = BookLoan::where('user_id', $overdueUser->id)
                ->where('book_id', $overdueBook->id)
                ->where('status', 'borrowed')
                ->exists();

            if (!$existingLoan) {
                BookLoan::create([
                    'user_id' => $overdueUser->id,
                    'book_id' => $overdueBook->id,
                    'loan_date' => now()->subDays(20),
                    'due_date' => now()->subDays(6), // Telat 6 hari
                    'status' => 'borrowed',
                ]);
                echo "   Created overdue loan scenario\n";
            }
        }

        // Buat user dengan beberapa pinjaman untuk testing
        $activeUser = $users->skip(1)->first();
        $availableBooks = $books->where('stock', '>', 2)->take(3);

        foreach ($availableBooks as $book) {
            $existingLoan = BookLoan::where('user_id', $activeUser->id)
                ->where('book_id', $book->id)
                ->where('status', 'borrowed')
                ->exists();

            if (!$existingLoan) {
                BookLoan::factory()->borrowed()->create([
                    'user_id' => $activeUser->id,
                    'book_id' => $book->id,
                ]);
            }
        }
    }
}
