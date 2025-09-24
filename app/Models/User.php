<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use HasUuid;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Atribut yang harus disembunyikan untuk serialisasi.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Mendapatkan atribut yang harus di-cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi dengan books melalui tabel pivot book_loans
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_loans')
                    ->withPivot(['loan_date', 'due_date', 'return_date', 'status'])
                    ->withTimestamps();
    }

    /**
     * Mendapatkan semua record peminjaman untuk user ini
     */
    public function bookLoans(): HasMany
    {
        return $this->hasMany(BookLoan::class);
    }

    /**
     * Mendapatkan peminjaman aktif (sedang dipinjam) untuk user ini
     */
    public function activeLoans(): HasMany
    {
        return $this->hasMany(BookLoan::class)->where('status', 'borrowed');
    }

    /**
     * Mendapatkan peminjaman yang sudah dikembalikan untuk user ini
     */
    public function returnedLoans(): HasMany
    {
        return $this->hasMany(BookLoan::class)->where('status', 'returned');
    }

    /**
     * Mengecek apakah user sudah meminjam buku tertentu dan belum mengembalikannya
     */
    public function hasBorrowedBook($bookId): bool
    {
        return $this->activeLoans()->where('book_id', $bookId)->exists();
    }
}
