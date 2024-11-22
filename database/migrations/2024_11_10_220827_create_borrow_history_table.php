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
        Schema::create('borrowed_books_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_copy_id')->constrained('book_copies')->onDelete('cascade');
            $table->uuid('student_id');
            $table->timestamp('borrowed_at');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->integer('duration');
            $table->string('book_name');
            $table->timestamps();

            // Foreign key constraint for student_id
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('borrowed_books_history');
    }
};
