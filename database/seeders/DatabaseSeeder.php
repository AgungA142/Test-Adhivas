<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed database aplikasi.
     */
    public function run(): void
    {
        // Buat admin user terlebih dahulu
        $admin = User::factory()->create([
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
        ]);

        // Buat regular users
        $users = User::factory(12)->create();

        // User khusus untuk testing API
        $testUser = User::factory()->create([
            'name' => 'API Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'), // Password yang dikenal untuk testing
        ]);

        $this->call([
            BookSeeder::class,
            BookLoanSeeder::class,
        ]);
    }
}
