<?php
// app/Models/BorrowedBookHistory.php// app/Models/BorrowedBookHistory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BorrowedBookHistory extends Model
{
    use HasFactory;

    protected $table = 'borrowed_books_history';

    protected $fillable = [
        'book_copy_id',
        'student_id',
        'borrowed_at',
        'due_date',
        'returned_at',
        'duration',
        'book_name',
    ];

    // Relationship with BookCopy
    public function bookCopy()
    {
        return $this->belongsTo(BookCopy::class, 'book_copy_id');
    }

    // Relationship with User (Student)
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // Relationship with Book through BookCopy
    public function book()
    {
        return $this->bookCopy->belongsTo(Book::class);
    }
}
