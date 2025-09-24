<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Jobs\SendBookLoanNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookLoan extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'user_id',
        'book_id',
        'loan_date',
        'due_date',
        'return_date',
        'status',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'due_date' => 'date',
        'return_date' => 'date',
    ];

    /**
     * Method "booted" dari model.
     */
    protected static function booted(): void
    {
        static::created(function (BookLoan $loan) {
            // Kirim job notifikasi email ketika peminjaman baru dibuat
            SendBookLoanNotification::dispatch($loan);
        });
    }

    /**
     * Relasi dengan User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi dengan Book
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Mengecek apakah peminjaman sudah terlambat
     */
    public function isOverdue(): bool
    {
        return $this->status === 'borrowed' &&
               $this->due_date &&
               Carbon::now()->isAfter($this->due_date);
    }

    /**
     * Mendapatkan hari hingga jatuh tempo atau hari terlambat
     */
    public function getDaysUntilDueAttribute(): int
    {
        if ($this->status === 'returned' || ! $this->due_date) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->due_date, false);
    }

    /**
     * Scope untuk peminjaman aktif (sedang dipinjam)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'borrowed');
    }

    /**
     * Scope untuk peminjaman yang sudah dikembalikan
     */
    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    /**
     * Scope untuk peminjaman yang terlambat
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'borrowed')
                    ->where('due_date', '<', Carbon::now());
    }
}
