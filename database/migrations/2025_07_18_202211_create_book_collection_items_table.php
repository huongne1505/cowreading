<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('book_collection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_collection_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->timestamp('added_at');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['book_collection_id', 'book_id']);
            $table->index(['book_collection_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_collection_items');
    }
};
