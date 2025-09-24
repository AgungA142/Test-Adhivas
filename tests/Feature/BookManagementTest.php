<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat admin user dan autentikasi
        $this->user = User::factory()->create([
            'email' => 'admin@example.com' // Admin user
        ]);
        Sanctum::actingAs($this->user);
    }

    /**
     * Test menambah buku baru (Create Book)
     */
    public function test_user_can_create_book(): void
    {
        $bookData = [
            'title' => 'Test Book Title',
            'author' => 'Test Author',
            'isbn' => '978-1234567890',
            'published_year' => 2023,
            'stock' => 5
        ];

        $response = $this->postJson('/api/admin/books', $bookData);

        $response->assertStatus(201)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Book created successfully'
                ])
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'title',
                        'author',
                        'isbn',
                        'published_year',
                        'stock',
                        'created_at',
                        'updated_at'
                    ]
                ]);

        // Verifikasi data di database
        $this->assertDatabaseHas('books', [
            'title' => 'Test Book Title',
            'author' => 'Test Author',
            'isbn' => '978-1234567890',
            'stock' => 5
        ]);
    }

    /**
     * Test validasi saat menambah buku dengan data tidak valid
     */
    public function test_create_book_validation_errors(): void
    {
        $invalidData = [
            'title' => '', // Empty title
            'author' => '', // Empty author
            'isbn' => 'invalid-isbn', // Invalid ISBN format
            'published_year' => 'not-a-year', // Invalid year
            'stock' => -1 // Negative stock
        ];

        $response = $this->postJson('/api/admin/books', $invalidData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'errors'
                ]);
    }

    /**
     * Test mencegah duplikasi ISBN
     */
    public function test_cannot_create_book_with_duplicate_isbn(): void
    {
        // Create a book first
        Book::factory()->create([
            'isbn' => '978-1234567890'
        ]);

        $bookData = [
            'title' => 'Another Book',
            'author' => 'Another Author',
            'isbn' => '978-1234567890', // Same ISBN
            'published_year' => 2023,
            'stock' => 3
        ];

        $response = $this->postJson('/api/admin/books', $bookData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'errors' => [
                        'isbn'
                    ]
                ]);
    }

    /**
     * Test mendapatkan daftar buku dengan pagination
     */
    public function test_can_get_books_list(): void
    {
        // Create multiple books
        Book::factory()->count(15)->create();

        $response = $this->getJson('/api/books');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'title',
                                'author',
                                'isbn',
                                'published_year',
                                'stock'
                            ]
                        ],
                        'current_page',
                        'per_page',
                        'total',
                        'last_page'
                    ]
                ]);
    }

    /**
     * Test search functionality
     */
    public function test_can_search_books(): void
    {
        Book::factory()->create([
            'title' => 'Laravel Guide',
            'author' => 'John Doe'
        ]);

        Book::factory()->create([
            'title' => 'PHP Basics',
            'author' => 'Jane Smith'
        ]);

        // Search by title
        $response = $this->getJson('/api/books?search=Laravel');
        $response->assertStatus(200);

        $responseData = $response->json();
        $this->assertCount(1, $responseData['data']['data']);
        $this->assertStringContainsString('Laravel', $responseData['data']['data'][0]['title']);
    }

    /**
     * Test filter by author
     */
    public function test_can_filter_books_by_author(): void
    {
        Book::factory()->create(['author' => 'John Doe']);
        Book::factory()->create(['author' => 'Jane Smith']);

        $response = $this->getJson('/api/books?author=John Doe');
        $response->assertStatus(200);

        $responseData = $response->json();
        $this->assertCount(1, $responseData['data']['data']);
        $this->assertEquals('John Doe', $responseData['data']['data'][0]['author']);
    }
}
