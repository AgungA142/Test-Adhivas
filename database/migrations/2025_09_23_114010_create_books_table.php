<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Jalankan migrations.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('author');
            $table->year('published_year');
            $table->string('isbn')->unique();
            $table->integer('stock')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Balikkan migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
