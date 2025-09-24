<?php

namespace App\Jobs;

use App\Models\BookLoan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookLoanNotification implements ShouldQueue
{
    use Queueable;

    protected $bookLoan;

    /**
     * Membuat instance job baru.
     */
    public function __construct(BookLoan $bookLoan)
    {
        $this->bookLoan = $bookLoan;
    }

    /**
     * Menjalankan job.
     */
    public function handle(): void
    {
        Log::info('SendBookLoanNotification job started', ['loan_id' => $this->bookLoan->id]);

        $user = $this->bookLoan->user;
        $book = $this->bookLoan->book;

        $message = sprintf(
            'Book Loan Notification: User %s (%s) has borrowed "%s" by %s. Due date: %s. Loan ID: %d',
            $user->name,
            $user->email,
            $book->title,
            $book->author,
            $this->bookLoan->due_date->format('Y-m-d'),
            $this->bookLoan->id
        );

        // Log sebagai simulasi pengiriman email
        Log::channel('single')->info('EMAIL_NOTIFICATION', [
            'type' => 'book_loan',
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'book_id' => $book->id,
            'book_title' => $book->title,
            'book_author' => $book->author,
            'loan_id' => $this->bookLoan->id,
            'loan_date' => $this->bookLoan->loan_date->format('Y-m-d H:i:s'),
            'due_date' => $this->bookLoan->due_date->format('Y-m-d'),
            'message' => $message
        ]);

        Log::info('SendBookLoanNotification job completed', ['loan_id' => $this->bookLoan->id]);
    }
}
