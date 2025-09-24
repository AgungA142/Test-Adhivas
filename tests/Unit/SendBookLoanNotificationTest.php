<?php

namespace Tests\Unit;

use App\Jobs\SendBookLoanNotification;
use App\Models\Book;
use App\Models\BookLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendBookLoanNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test job dapat diinstansiasi
     */
    public function test_job_can_be_instantiated(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $loan = BookLoan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id
        ]);

        $job = new SendBookLoanNotification($loan);

        $this->assertInstanceOf(SendBookLoanNotification::class, $job);
    }

    /**
     * Test job mencatat notifikasi email dengan benar
     */
    public function test_job_logs_notification(): void
    {
        // Mock logger untuk expect calls (event juga akan trigger logs)
        Log::shouldReceive('channel')
            ->with('single')
            ->atLeast()
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('EMAIL_NOTIFICATION', \Mockery::type('array'))
            ->atLeast()
            ->once();

        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $book = Book::factory()->create([
            'title' => 'Test Book',
            'author' => 'Test Author'
        ]);

        $loan = BookLoan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id
        ]);

        // Load relationships
        $loan->load(['user', 'book']);

        $job = new SendBookLoanNotification($loan);
        $job->handle();
    }

    /**
     * Test job contains correct notification data
     */
    public function test_job_contains_correct_notification_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ]);

        $book = Book::factory()->create([
            'title' => 'Laravel Guide',
            'author' => 'Taylor Otwell'
        ]);

        $loan = BookLoan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id
        ]);

        // Capture log data
        $logData = null;
        Log::shouldReceive('channel')
            ->with('single')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('EMAIL_NOTIFICATION', \Mockery::on(function ($data) use (&$logData) {
                $logData = $data;
                return true;
            }))
            ->once();

        $job = new SendBookLoanNotification($loan);
        $job->handle();

        // Verify log data structure
        $this->assertArrayHasKey('type', $logData);
        $this->assertEquals('book_loan', $logData['type']);

        $this->assertArrayHasKey('user_name', $logData);
        $this->assertEquals('Jane Smith', $logData['user_name']);

        $this->assertArrayHasKey('user_email', $logData);
        $this->assertEquals('jane@example.com', $logData['user_email']);

        $this->assertArrayHasKey('book_title', $logData);
        $this->assertEquals('Laravel Guide', $logData['book_title']);

        $this->assertArrayHasKey('book_author', $logData);
        $this->assertEquals('Taylor Otwell', $logData['book_author']);

        $this->assertArrayHasKey('loan_id', $logData);
        $this->assertEquals($loan->id, $logData['loan_id']);
    }

    /**
     * Test job handles missing relationships gracefully
     */
    public function test_job_handles_missing_relationships(): void
    {
        $loan = new BookLoan();
        $loan->id = 1;
        $loan->user_id = 999; // Non-existent user
        $loan->book_id = 999; // Non-existent book
        $loan->loan_date = now();
        $loan->due_date = now()->addDays(14);

        // Mock user and book relationships
        $loan->setRelation('user', (object)[
            'id' => 999,
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $loan->setRelation('book', (object)[
            'id' => 999,
            'title' => 'Test Book',
            'author' => 'Test Author'
        ]);

        Log::shouldReceive('channel')
            ->with('single')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('EMAIL_NOTIFICATION', \Mockery::type('array'))
            ->once();

        $job = new SendBookLoanNotification($loan);

        // Should not throw exception
        $this->assertNull($job->handle());
    }

    /**
     * Test job message format
     */
    public function test_job_message_format(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $book = Book::factory()->create([
            'title' => 'Sample Book',
            'author' => 'Sample Author'
        ]);

        $loan = BookLoan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'due_date' => now()->addDays(14)
        ]);

        $expectedMessagePattern = sprintf(
            'Book Loan Notification: User %s (%s) has borrowed "%s" by %s. Due date: %s. Loan ID: %d',
            $user->name,
            $user->email,
            $book->title,
            $book->author,
            $loan->due_date->format('Y-m-d'),
            $loan->id
        );

        Log::shouldReceive('channel')
            ->with('single')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('EMAIL_NOTIFICATION', \Mockery::on(function ($data) use ($expectedMessagePattern) {
                return $data['message'] === $expectedMessagePattern;
            }))
            ->once();

        $job = new SendBookLoanNotification($loan);
        $job->handle();
    }
}
