<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'title',
        'author',
        'published_year',
        'isbn',
        'stock',
    ];

    protected $casts = [
        'published_year' => 'integer',
        'stock' => 'integer',
    ];

    /**
     * Relasi dengan users melalui tabel pivot book_loans
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'book_loans')
                    ->withPivot(['loan_date', 'due_date', 'return_date', 'status'])
                    ->withTimestamps();
    }
    /**
     * Relasi dengan BookLoan
     */
    public function bookLoans(): HasMany
    {
        return $this->hasMany(BookLoan::class);
    }

    /**
     * Mendapatkan peminjaman aktif (sedang dipinjam) untuk buku ini
     */
    public function activeLoans(): HasMany
    {
        return $this->hasMany(BookLoan::class)->where('status', 'borrowed');
    }

    /**
     * Mengecek apakah buku tersedia untuk dipinjam
     */
    public function isAvailable(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Mendapatkan stok yang tersedia (total stok minus yang sedang dipinjam)
     */
    public function getAvailableStockAttribute(): int
    {
        $borrowedCount = $this->activeLoans()->count();

        return max(0, $this->stock - $borrowedCount);
    }

    /**
     * Scope untuk pencarian buku
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('author', 'LIKE', "%{$search}%")
                  ->orWhere('isbn', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Scope untuk filter berdasarkan penulis
     */
    public function scopeByAuthor($query, $author)
    {
        if ($author) {
            return $query->where('author', 'LIKE', "%{$author}%");
        }

        return $query;
    }

    /**
     * Scope untuk filter berdasarkan tahun terbit
     */
    public function scopeByYear($query, $year)
    {
        if ($year) {
            return $query->where('published_year', $year);
        }

        return $query;
    }
}
