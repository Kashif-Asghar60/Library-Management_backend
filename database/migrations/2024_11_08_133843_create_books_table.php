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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->string('isbn')->unique();
            $table->string('publisher');
            $table->date('publication_date');
            $table->string('genre');
            $table->string('language');
            $table->text('description');
            $table->string('cover_image_url')->nullable();
            $table->string('edition');
            $table->integer('page_count');
            $table->enum('availability_status', ['Available', 'Borrowed', 'Reserved'])->default('Available');
            $table->integer('quantity')->default(1);  // Number of available copies
            $table->float('rating', 2, 1)->nullable();
            $table->json('tags')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->string('location');
            $table->timestamp('date_added')->useCurrent();
            $table->string('book_format');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('books');
    }
};
