<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Book;
use Laravel\Sanctum\Sanctum;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Jangan jalankan seeder, buat data manual untuk test
    }

    public function test_non_admin_cannot_access_admin_routes()
    {
        // Buat user biasa (bukan admin)
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'name' => 'Regular User'
        ]);

        Sanctum::actingAs($user);

        // Coba akses route admin untuk menambah buku
        $response = $this->postJson('/api/admin/books', [
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '1234567890123',
            'stock' => 5
        ]);

        // Harus gagal karena bukan admin
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Admin access required'
        ]);
    }

    public function test_admin_can_access_admin_routes()
    {
        // Buat user admin
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'name' => 'Admin User'
        ]);

        Sanctum::actingAs($admin);

        // Coba akses route admin untuk menambah buku
        $response = $this->postJson('/api/admin/books', [
            'title' => 'Admin Test Book',
            'author' => 'Admin Test Author',
            'isbn' => '9876543210987',
            'published_year' => 2024,
            'stock' => 3
        ]);

        // Harus berhasil karena admin
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'title',
                'author',
                'isbn',
                'stock'
            ]
        ]);
    }

    public function test_unauthenticated_user_cannot_access_admin_routes()
    {
        // Tanpa autentikasi sama sekali
        $response = $this->postJson('/api/admin/books', [
            'title' => 'Unauthorized Book',
            'author' => 'Unauthorized Author',
            'isbn' => '1111111111111',
            'stock' => 1
        ]);

        // Harus gagal karena tidak terautentikasi
        $response->assertStatus(401);
    }

    public function test_regular_user_can_access_user_routes()
    {
        // Buat user biasa
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'name' => 'Regular User'
        ]);

        // Buat beberapa buku untuk test
        $book = Book::factory()->create([
            'title' => 'Test Book',
            'stock' => 5
        ]);

        Sanctum::actingAs($user);

        // User biasa masih bisa mengakses route untuk melihat buku
        $response = $this->getJson('/api/books');
        $response->assertStatus(200);

        // User biasa masih bisa meminjam buku
        $response = $this->postJson('/api/loans', [
            'book_id' => $book->id
        ]);

        // Harus berhasil karena buku tersedia
        $response->assertStatus(201);
    }
}
