<?php
// app/Models/Book.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'isbn',
        'publisher',
        'publication_date',
        'genre',
        'language',
        'description',
        'cover_image_url',
        'edition',
        'page_count',
        'availability_status',
        'quantity',
        'rating',
        'tags',
        'price',
        'location',
        'date_added',
        'book_format'
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    // Relationship to BookCopy (multiple copies of a book)
    public function copies()
    {
        return $this->hasMany(BookCopy::class);
    }

    // Get the count of available copies of the book
    public function availableCopies()
    {
        return $this->copies()->where('status', 'Available')->count();
    }
}
