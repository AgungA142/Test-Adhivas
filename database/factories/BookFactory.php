<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Definisikan state default model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $isbnCounter = 1000000000000;

        $bookTitles = [
            'The Art of Clean Code',
            'Modern Web Development',
            'Database Design Patterns',
            'Machine Learning Fundamentals',
            'JavaScript: The Complete Guide',
            'Python for Data Science',
            'Laravel Best Practices',
            'System Architecture Design',
            'Cybersecurity Essentials',
            'Cloud Computing Principles',
            'DevOps Handbook',
            'Microservices Architecture',
            'Introduction to AI',
            'Software Engineering Ethics',
            'Agile Development Methods'
        ];

        $authors = [
            'Robert C. Martin',
            'Eric Evans',
            'Martin Fowler',
            'Kent Beck',
            'Uncle Bob',
            'Grady Booch',
            'Gang of Four',
            'Andrew Hunt',
            'Dave Thomas',
            'Bjarne Stroustrup',
            'Donald Knuth',
            'Linus Torvalds',
            'Tim Berners-Lee',
            'John Carmack',
            'Brendan Eich'
        ];

        return [
            'title' => $this->faker->randomElement($bookTitles) . ' ' . $this->faker->randomElement(['Vol 1', 'Vol 2', 'Advanced', 'Beginner', '2024 Edition', 'Complete Guide']),
            'author' => $this->faker->randomElement($authors),
            'published_year' => $this->faker->numberBetween(2015, 2024),
            'isbn' => '978-' . $isbnCounter++,
            'stock' => $this->faker->numberBetween(3, 15),
        ];
    }

    /**
     * buku pemrograman
     */
    public function programming(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $this->faker->randomElement([
                'Clean Code: A Handbook of Agile Software Craftsmanship',
                'The Pragmatic Programmer',
                'Design Patterns: Elements of Reusable Object-Oriented Software',
                'Refactoring: Improving the Design of Existing Code',
                'Code Complete: A Practical Handbook of Software Construction'
            ]),
            'author' => $this->faker->randomElement([
                'Robert C. Martin',
                'Andrew Hunt',
                'Gang of Four',
                'Martin Fowler',
                'Steve McConnell'
            ])
        ]);
    }

    /**
     * stok tinggi (10-20)
     */
    public function highStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $this->faker->numberBetween(10, 20),
        ]);
    }

    /**
     * stok rendah (1-3)
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $this->faker->numberBetween(1, 3),
        ]);
    }
}
