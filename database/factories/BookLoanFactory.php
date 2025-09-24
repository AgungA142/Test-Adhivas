<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookLoan>
 */
class BookLoanFactory extends Factory
{
    /**
     * Definisikan state default model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate tanggal peminjaman yang realistis
        $loanDate = $this->faker->dateTimeBetween('-60 days', '-1 day');
        $dueDate = clone $loanDate;
        $dueDate->modify('+14 days'); // Periode peminjaman standar 2 minggu

        // Status acak dengan probabilitas berbobot
        $status = $this->faker->randomElement([
            'borrowed', 'borrowed', 'borrowed', // 60% dipinjam
            'returned', 'returned'              // 40% dikembalikan
        ]);

        $returnDate = null;

        // Jika dikembalikan, set tanggal kembali antara tanggal pinjam dan jatuh tempo (atau sedikit setelah untuk terlambat)
        if ($status === 'returned') {
            $maxReturnDate = $this->faker->boolean(80) ? $dueDate : (clone $dueDate)->modify('+7 days');
            $returnDate = $this->faker->dateTimeBetween($loanDate, $maxReturnDate);
        }

        return [
            'user_id' => User::factory(), // Akan di-override ketika membuat dengan user tertentu
            'book_id' => Book::factory(), // Akan di-override ketika membuat dengan buku tertentu
            'loan_date' => $loanDate,
            'due_date' => $dueDate,
            'return_date' => $returnDate,
            'status' => $status,
        ];
    }

    /**
     * State untuk buku yang dipinjam (peminjaman aktif)
     */
    public function borrowed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'borrowed',
            'return_date' => null,
        ]);
    }

    /**
     * State untuk buku yang dikembalikan
     */
    public function returned(): static
    {
        return $this->state(function (array $attributes) {
            // Pastikan tanggal kembali setelah tanggal pinjam tapi realistis
            $loanDate = is_string($attributes['loan_date']) ?
                new \DateTime($attributes['loan_date']) :
                $attributes['loan_date'];

            $dueDate = is_string($attributes['due_date']) ?
                new \DateTime($attributes['due_date']) :
                $attributes['due_date'];

            // 80% dikembalikan tepat waktu, 20% dikembalikan terlambat (tapi tidak terlalu lama)
            $returnOnTime = $this->faker->boolean(80);
            $maxReturnDate = $returnOnTime ? $dueDate : (clone $dueDate)->modify('+5 days');

            $returnDate = $this->faker->dateTimeBetween($loanDate, $maxReturnDate);

            return [
                'status' => 'returned',
                'return_date' => $returnDate,
            ];
        });
    }

    /**
     * State untuk peminjaman yang terlambat
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            $loanDate = $this->faker->dateTimeBetween('-30 days', '-15 days');
            $dueDate = clone $loanDate;
            $dueDate->modify('+14 days');

            return [
                'loan_date' => $loanDate,
                'due_date' => $dueDate,
                'status' => 'borrowed',
                'return_date' => null,
            ];
        });
    }

    /**
     * State untuk peminjaman terbaru
     */
    public function recent(): static
    {
        return $this->state(function (array $attributes) {
            $loanDate = $this->faker->dateTimeBetween('-7 days', 'now');
            $dueDate = clone $loanDate;
            $dueDate->modify('+14 days');

            return [
                'loan_date' => $loanDate,
                'due_date' => $dueDate,
                'status' => 'borrowed',
                'return_date' => null,
            ];
        });
    }
}
