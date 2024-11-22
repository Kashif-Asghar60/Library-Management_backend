<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');  // Link to books table
            $table->uuid('student_id')->nullable();  // UUID for student_id instead of integer
            $table->enum('status', ['Available', 'Borrowed', 'Reserved'])->default('Available');
            $table->timestamp('borrowed_at')->nullable();  // Track when the book was borrowed
            $table->timestamp('due_date')->nullable();  // Track when the book is due to be returned
            $table->timestamps();

            // Foreign key constraint for student_id
            $table->foreign('student_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('book_copies');
    }
};
