<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookCopy extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'student_id',
        'status',
        'borrowed_at',
        'due_date'
    ];

    // Relationship to Book
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    // Relationship to Student (User)
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
