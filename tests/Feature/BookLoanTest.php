<?php

namespace Tests\Feature;

use App\Jobs\SendBookLoanNotification;
use App\Models\Book;
use App\Models\BookLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookLoanTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /**
     * Test user dapat meminjam buku yang tersedia
     */
    public function test_user_can_borrow_available_book(): void
    {
        Queue::fake();

        $book = Book::factory()->create([
            'stock' => 5,
            'title' => 'Test Book',
            'author' => 'Test Author'
        ]);

        $loanData = [
            'book_id' => $book->id,
            'loan_days' => 14
        ];

        $response = $this->postJson('/api/loans', $loanData);

        $response->assertStatus(201)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Book borrowed successfully'
                ])
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'user_id',
                        'book_id',
                        'loan_date',
                        'due_date',
                        'status',
                        'user',
                        'book'
                    ]
                ]);


        $this->assertDatabaseHas('book_loans', [
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'status' => 'borrowed'
        ]);

        // Verifikasi job antrian untuk notifikasi email dipanggil
        Queue::assertPushed(SendBookLoanNotification::class, function ($job) use ($book) {
            return true;
        });
    }

    /**
     * Test user tidak bisa meminjam buku jika stok habis
     */
    public function test_cannot_borrow_book_when_stock_is_zero(): void
    {
        $book = Book::factory()->create([
            'stock' => 1
        ]);


        $otherUser = User::factory()->create();
        BookLoan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'status' => 'borrowed'
        ]);

        $loanData = [
            'book_id' => $book->id,
            'loan_days' => 14
        ];

        $response = $this->postJson('/api/loans', $loanData);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Book is not available for loan'
                ]);

        // Verfikasi tidak ada pinjaman baru yang dibuat
        $this->assertDatabaseMissing('book_loans', [
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'status' => 'borrowed'
        ]);
    }

    /**
     * Test user tidak bisa meminjam buku yang sama dua kali
     */
    public function test_cannot_borrow_same_book_twice(): void
    {
        $book = Book::factory()->create(['stock' => 5]);

        //buku sudah dipinjam user
        BookLoan::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'status' => 'borrowed'
        ]);

        $loanData = [
            'book_id' => $book->id,
            'loan_days' => 14
        ];

        $response = $this->postJson('/api/loans', $loanData);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'You have already borrowed this book and haven\'t returned it yet'
                ]);
    }

    /**
     * Test validasi input saat meminjam buku
     */
    public function test_borrow_book_validation(): void
    {
        $invalidData = [
            'book_id' => 'not-a-number',
            'loan_days' => 50 // Exceeds maximum
        ];

        $response = $this->postJson('/api/loans', $invalidData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'errors'
                ]);
    }

    /**
     * Test meminjam buku dengan book_id yang tidak ada
     */
    public function test_cannot_borrow_nonexistent_book(): void
    {
        $loanData = [
            'book_id' => "asdweq1234512312", // ID buku yang tidak ada
            'loan_days' => 14
        ];

        $response = $this->postJson('/api/loans', $loanData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'errors' => [
                        'book_id'
                    ]
                ]);
    }

    /**
     * Test mengembalikan buku
     */
    public function test_user_can_return_book(): void
    {
        $book = Book::factory()->create(['stock' => 5]);

        $loan = BookLoan::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'status' => 'borrowed'
        ]);

        $response = $this->putJson("/api/loans/{$loan->id}/return");

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Book returned successfully'
                ]);

        // Verify loan status was updated
        $this->assertDatabaseHas('book_loans', [
            'id' => $loan->id,
            'status' => 'returned'
        ]);
    }

    /**
     * Test mendapatkan daftar pinjaman user
     */
    public function test_user_can_get_their_loans(): void
    {
        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();

        // Create loans for the authenticated user
        BookLoan::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $book1->id
        ]);

        BookLoan::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $book2->id
        ]);

        // Create loan for another user (should not appear in results)
        $otherUser = User::factory()->create();
        BookLoan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $book1->id
        ]);

        $response = $this->getJson('/api/loans/my-loans?status=all');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'book_id',
                            'loan_date',
                            'due_date',
                            'status',
                            'book'
                        ]
                    ]
                ]);

        $responseData = $response->json();

        // Should only return loans for the authenticated user
        $this->assertCount(2, $responseData['data']);

        foreach ($responseData['data'] as $loan) {
            $this->assertEquals($this->user->id, $loan['user_id']);
        }
    }

    /**
     * Test filter loans by status
     */
    public function test_can_filter_loans_by_status(): void
    {
        $book = Book::factory()->create();

        // Create borrowed loan
        BookLoan::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'status' => 'borrowed'
        ]);

        // Create returned loan
        BookLoan::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'status' => 'returned'
        ]);

        // Test filter by borrowed status
        $response = $this->getJson('/api/loans/my-loans?status=borrowed');
        $response->assertStatus(200);

        $responseData = $response->json();
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals('borrowed', $responseData['data'][0]['status']);

        // Test filter by returned status
        $response = $this->getJson('/api/loans/my-loans?status=returned');
        $response->assertStatus(200);

        $responseData = $response->json();
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals('returned', $responseData['data'][0]['status']);
    }
}
