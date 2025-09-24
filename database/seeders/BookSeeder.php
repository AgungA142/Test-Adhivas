<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Jalankan database seeds.
     */
    public function run(): void
    {
        // Buat beberapa buku pemrograman populer yang spesifik
        $popularBooks = [
            [
                'title' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
                'author' => 'Robert C. Martin',
                'published_year' => 2008,
                'isbn' => '978-0132350884',
                'stock' => 15
            ],
            [
                'title' => 'The Pragmatic Programmer',
                'author' => 'Andrew Hunt, David Thomas',
                'published_year' => 2019,
                'isbn' => '978-0135957059',
                'stock' => 12
            ],
            [
                'title' => 'Design Patterns: Elements of Reusable Object-Oriented Software',
                'author' => 'Gang of Four',
                'published_year' => 1994,
                'isbn' => '978-0201633612',
                'stock' => 8
            ],
            [
                'title' => 'Laravel: Up & Running',
                'author' => 'Matt Stauffer',
                'published_year' => 2023,
                'isbn' => '978-1492041207',
                'stock' => 10
            ],
            [
                'title' => 'JavaScript: The Definitive Guide',
                'author' => 'David Flanagan',
                'published_year' => 2020,
                'isbn' => '978-1491952023',
                'stock' => 7
            ],
            [
                'title' => 'Python Crash Course',
                'author' => 'Eric Matthes',
                'published_year' => 2023,
                'isbn' => '978-1718502703',
                'stock' => 14
            ],
            [
                'title' => 'System Design Interview',
                'author' => 'Alex Xu',
                'published_year' => 2020,
                'isbn' => '978-1736049112',
                'stock' => 5
            ],
            [
                'title' => 'Docker Deep Dive',
                'author' => 'Nigel Poulton',
                'published_year' => 2023,
                'isbn' => '978-1916585256',
                'stock' => 6
            ],
            [
                'title' => 'Kubernetes in Action',
                'author' => 'Marko Luksa',
                'published_year' => 2017,
                'isbn' => '978-1617293726',
                'stock' => 4
            ],
            [
                'title' => 'Building Microservices',
                'author' => 'Sam Newman',
                'published_year' => 2021,
                'isbn' => '978-1492034025',
                'stock' => 9
            ]
        ];

        // Buat buku-buku populer
        foreach ($popularBooks as $bookData) {
            Book::create($bookData);
        }

        // Buat buku tambahan menggunakan factory
        // Buku dengan stok tinggi (ketersediaan bagus)
        Book::factory(10)->highStock()->create();

        // Buku dengan stok normal
        Book::factory(15)->create();

        // Buku dengan stok rendah (ketersediaan terbatas)
        Book::factory(5)->lowStock()->create();

        // Buku khusus pemrograman
        Book::factory(5)->programming()->create();
    }
}
