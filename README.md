# Test Adhivasindo

## Pendahuluan
Proyek ini adalah aplikasi berbasis Laravel yang menyediakan fitur manajemen buku dan peminjaman buku.

## Prasyarat
Sebelum menjalankan proyek ini, pastikan Anda telah menginstal:
- PHP >= 8.0
- Composer
- Node.js dan npm
- Database SQLite (atau database lain yang didukung Laravel)

## Langkah-langkah Instalasi

1. Clone repositori ini:
   ```bash
   git clone <repository-url>
   cd test-adhivasindo
   ```

2. Instal dependensi PHP menggunakan Composer:
   ```bash
   composer install
   ```

3. Salin file `.env.example` menjadi `.env` dan sesuaikan konfigurasi sesuai kebutuhan:
   ```bash
   cp .env.example .env
   ```

4. Generate application key:
   ```bash
   php artisan key:generate
   ```

5. Jalankan migrasi database dan seed data:
   ```bash
   php artisan migrate:fresh --seed
   ```

6. Jalankan queue job:
   ```bash
   php artisan queue:work
   ```

7. Jalankan server pengembangan:
   ```bash
   php artisan serve
   ```

8. Buka aplikasi di browser pada `http://localhost:8000`.

## Skrip dan Informasi Tambahan
- Untuk menjalankan pengujian:
  ```bash
  php artisan test
  ```

- Untuk Route admin bisa menggunakan akun:
email : admin@example.com
password : password
