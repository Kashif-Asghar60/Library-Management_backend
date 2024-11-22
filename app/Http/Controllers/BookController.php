<?php
// app/Http/Controllers/BookController.php
namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\User;
use App\Models\Notification;

use Illuminate\Http\Request;
use App\Notifications\OverdueBookNotification;
use App\Notifications\ReturnReminderNotification;
use App\Models\BorrowedBookHistory;

use Carbon\Carbon;

class BookController extends Controller
{
    // Store a new book
    public function store(Request $request)
    {
        try {
            // Validate incoming data
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'author' => 'required|string|max:255',
                'isbn' => 'required|string|unique:books,isbn',
                'publisher' => 'required|string|max:255',
                'publication_date' => 'required|date',
                'genre' => 'required|string',
                'language' => 'required|string',
                'description' => 'required|string',
                'cover_image_url' => 'nullable|url',
                'edition' => 'required|string',
                'page_count' => 'required|integer',
                'quantity' => 'required|integer',
                'availability_status' => 'required|in:Available,Borrowed,Reserved',
                'rating' => 'nullable|numeric|min:0|max:5',
                'tags' => 'nullable|array',
                'price' => 'nullable|numeric',
                'location' => 'required|string|max:255',
                'book_format' => 'required|string',
            ]);

            // Create the book
            $book = Book::create($validatedData);

            // Create book copies based on the specified quantity
            for ($i = 0; $i < $validatedData['quantity']; $i++) {
                $book->copies()->create(['status' => 'Available']);
            }

            return response()->json($book, 201);  // Success response

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation error
            return response()->json([
                'error' => 'Validation Error',
                'message' => $e->errors(),
            ], 422);  // Return 422 Unprocessable Entity for validation error

        } catch (\Exception $e) {
            // Handle other exceptions
            return response()->json([
                'error' => 'Unexpected Error',
                'message' => $e->getMessage(),
            ], 500);  // Return 500 Internal Server Error for general exceptions
        }
    }

    // Assign a copy of the book to a student
    public function assignCopyToStudent(Request $request, $bookId)
    {
        try {
            // Check if the user is authenticated
            if (!auth()->check()) {
                return response()->json(['error' => 'Token expired or invalid. Please log in again.'], 401);
            }

            $request->validate([
                'student_id' => 'required|exists:users,id',
                'due_date' => 'required|date',
            ]);

            $book = Book::find($bookId);

            if (!$book) {
                return response()->json(['error' => 'Book not found'], 404);
            }

            $availableCopy = $book->copies()->where('status', 'Available')->first();

            if (!$availableCopy) {
                return response()->json(['error' => 'No available copies for this book'], 400);
            }

            // Assign the copy to the student
            $availableCopy->update([
                'student_id' => $request->student_id,
                'status' => 'Borrowed',
                'borrowed_at' => now(),
                'due_date' => $request->due_date,
            ]);

            return response()->json(['message' => 'Book assigned to student successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Catch and return validation errors
            return response()->json(['error' => 'Validation error', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Catch any other error
            \Log::error("Error assigning book copy: {$e->getMessage()}");  // Log the error
            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }



    public function viewBorrowedBooks()
    {
        // Fetch all borrowed books along with the student who borrowed them
        $borrowedBooks = BookCopy::where('status', 'Borrowed')
            ->with('book', 'student')
            ->get();

        return response()->json($borrowedBooks);
    }

    public function viewBorrowedBooksByUser($userId)
    {
        // Fetch borrowed books for a specific student (user)
        $borrowedBooks = BookCopy::where('status', 'Borrowed')
            ->where('student_id', $userId) // Filter by userId (student_id)
            ->with('book') // Include the related book information
            ->get();

        return response()->json($borrowedBooks);
    }

    public function markAsReturned(Request $request, $bookCopyId)
    {
        // Find the book copy by ID
        $bookCopy = BookCopy::find($bookCopyId);

        if (!$bookCopy) {
            return response()->json(['error' => 'Book copy not found'], 404);
        }

        // Check if the book is borrowed, if not return an error
        if ($bookCopy->status !== 'Borrowed') {
            return response()->json(['error' => 'This book is not currently borrowed'], 400);
        }

        // Calculate the borrow duration
        $borrowedAt = Carbon::parse($bookCopy->borrowed_at);
        $dueDate = Carbon::parse($bookCopy->due_date);
        $returnedAt = Carbon::now();  // Current time is when the book is returned
        $duration = $borrowedAt->diffInDays($returnedAt);  // Calculate the borrow duration in days

        // Create a new entry in the borrowed_books_history table using the BorrowedBookHistory model
        BorrowedBookHistory::create([
            'book_copy_id' => $bookCopy->id,
            'student_id' => $bookCopy->student_id,
            'borrowed_at' => $bookCopy->borrowed_at,
            'due_date' => $bookCopy->due_date,
            'returned_at' => $returnedAt,
            'duration' => $duration,
            'book_name' => $bookCopy->book->title,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Mark the book copy as returned
        $bookCopy->update([
            'status' => 'Available',
            'student_id' => null, // Clear the student ID
            'borrowed_at' => null,
            'due_date' => null,
        ]);

        return response()->json(['message' => 'Book marked as returned successfully']);
    }
    public function setReturnDeadline(Request $request, $bookCopyId)
    {
        $request->validate([
            'due_date' => 'required|date|after:today',
        ]);

        $bookCopy = BookCopy::find($bookCopyId);

        if (!$bookCopy) {
            return response()->json(['error' => 'Book copy not found'], 404);
        }
        // Check if the book is borrowed
        if ($bookCopy->status !== 'Borrowed') {
            return response()->json(['error' => 'This book is not currently borrowed'], 400);
        }

        // Update the due date for the borrowed book
        $bookCopy->update([
            'due_date' => $request->due_date,
        ]);

        return response()->json(['message' => 'Return deadline updated successfully']);
    }


    // Display a listing of books
    public function index()
    {
        return response()->json(Book::all());
    }
    public function getAllCopies()
    {
        // Fetch all book copies with their associated book and student details
        $bookCopies = BookCopy::with('book', 'student')->get();

        return response()->json($bookCopies);
    }

    public function getAvailableCopies($bookId)
    {
        // Find the book
        // Find the book and the first available copy
        $book = Book::find($bookId);

        if (!$book) {
            return response()->json(['error' => 'Book copy  not found'], 404);
        }
        // Get all available copies of this book
        $availableCopies = $book->copies()->where('status', 'Available')->get();

        return response()->json([
            'available_copies' => $availableCopies
        ]);
    }
    // Search book by title, author, genre, or ISBN
    public function searchBooks(Request $request)
    {
        $request->validate([
            'query' => 'required|string'
        ]);

        $query = $request->input('query');

        $books = Book::where('title', 'LIKE', "%{$query}%")
            ->orWhere('author', 'LIKE', "%{$query}%")
            ->orWhere('genre', 'LIKE', "%{$query}%")
            ->orWhere('isbn', 'LIKE', "%{$query}%")
            ->paginate(10);  // Change the number as needed

        return response()->json($books);
    }
    // Display the specified book by its ID
    public function show($id)
    {
        $book = Book::findOrFail($id);
        return response()->json($book);
    }

    // Update an existing book
    public function update(Request $request, $id)
    {
        $book = Book::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'required|string|unique:books,isbn,' . $book->id,
            'publisher' => 'required|string|max:255',
            'publication_date' => 'required|date',
            'genre' => 'required|string',
            'language' => 'required|string',
            'description' => 'required|string',
            'cover_image_url' => 'nullable|url',
            'edition' => 'required|string',
            'page_count' => 'required|integer',
            'quantity' => 'required|integer',
            'availability_status' => 'required|in:Available,Borrowed,Reserved',
            'rating' => 'nullable|numeric|min:0|max:5',
            'tags' => 'nullable|array',
            'price' => 'nullable|numeric',
            'location' => 'required|string|max:255',
            'book_format' => 'required|string',
        ]);

        $book->update($request->all());

        return response()->json($book);
    }

    // Remove the specified book
    public function destroy($id)
    {
        // Find the book by ID or return a 404 if not found
        $book = Book::findOrFail($id);

        // Delete the book and its copies
        $book->delete();

        return response()->json(['message' => 'Book deleted successfully']);
    }





    // Method to fetch overdue books
    public function getOverdueBooks()
    {
        return BookCopy::where('status', 'Borrowed')
            ->where('due_date', '<', Carbon::now())
            ->get();
    }







    // Dashboard is here

    public function borrowingHistory()
    {
        $borrowHistory = BorrowedBookHistory::with(['bookCopy.book', 'student'])->get();

        return response()->json($borrowHistory);


    }

    public function popularBooksReport()
    {
        $popularBooks = Book::withCount([
            'copies as borrow_count' => function ($query) {
                $query->where('status', 'Borrowed');
            }
        ])->orderByDesc('borrow_count')->get();

        return response()->json($popularBooks);
    }
    public function overdueBooksReport()
    {
        $overdueBooks = BookCopy::with('book', 'student') // Assuming 'student' is the relationship method on BookCopy
            ->where('status', 'Borrowed')
            ->where('due_date', '<', Carbon::now())
            ->get();

        return response()->json($overdueBooks);
    }
    public function studentActivityReport()
    {
        $students = User::withCount([
            'borrowedCopies as books_borrowed' => function ($query) {
                $query->where('status', 'Borrowed');
            }
        ])->orderByDesc('books_borrowed')->get();

        return response()->json($students);
    }
    public function studentActivityReportByUser($userId)
    {
        $student = User::withCount([
            'borrowedCopies as books_borrowed' => function ($query) {
                $query->where('status', 'Borrowed');
            }
        ])
            ->where('id', $userId)
            ->first();

        if (!$student) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $borrowedBooksHistory = BorrowedBookHistory::where('student_id', $userId)
            ->with('bookCopy.book')
            ->get();

        return response()->json([
            'student' => $student,
            'borrowed_books_history' => $borrowedBooksHistory
        ]);
    }


    // Notifications Book Controller
    public function sendOverdueNotifications()
    {
        try {
            // Ensure the user has the right permissions (for example, admin check)
            if (!auth()->user() || auth()->user()->role !== 'admin') {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Get overdue books
            $overdueBooks = BookCopy::where('status', 'Borrowed')
                ->where('due_date', '<', Carbon::now())
                ->get();

            if ($overdueBooks->isEmpty()) {
                return response()->json(['message' => 'No overdue books found.'], 404);
            }

            $sentNotifications = 0;

            foreach ($overdueBooks as $bookCopy) {
                $user = $bookCopy->student;

                // Check if a notification was sent in the last 24 hours (no need to check read_at)
                $lastNotification = Notification::where('user_id', $user->id)
                    ->where('type', 'Overdue')
                    ->where('sent_at', '>', Carbon::now()->subDay())
                    ->first();

                // If no notification was sent in the last 24 hours, send a new one
                if (!$lastNotification) {
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'Overdue',
                        'message' => "Your borrowed book '{$bookCopy->book->title}' is overdue.",
                        'sent_at' => Carbon::now(),
                    ]);
                    $sentNotifications++; // Increment the counter
                }
            }

            // Return status response
            return response()->json([
                'message' => $sentNotifications ? "{$sentNotifications} overdue notifications sent." : 'No overdue notifications sent.',
            ]);

        } catch (\Exception $e) {
            // Handle any errors and return a response
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function sendReturnReminders()
    {
        try {
            // Ensure the user has the right permissions (for example, admin check)
            if (!auth()->user() || auth()->user()->role !== 'admin') {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Get books that are due for return within the next 3 days
            $booksToReturn = BookCopy::where('status', 'Borrowed')
                ->where('due_date', '>', Carbon::now())
                ->where('due_date', '<', Carbon::now()->addDays(3))
                ->get();

            // If no books are due for return soon
            if ($booksToReturn->isEmpty()) {
                return response()->json(['message' => 'No books due for return in the next 3 days. No reminders to send.'], 200);
            }

            $sentNotifications = 0;

            foreach ($booksToReturn as $bookCopy) {
                $user = $bookCopy->student;

                // Check if a return reminder was sent in the last 24 hours
                $lastNotification = Notification::where('user_id', $user->id)
                    ->where('type', 'Return Reminder')
                    ->where('sent_at', '>', Carbon::now()->subDay())
                    ->first();

                if (!$lastNotification) {
                    // Send notification if not sent in the last 24 hours
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'Return Reminder',
                        'message' => "Your borrowed book '{$bookCopy->book->title}' is due for return soon. Please return it by {$bookCopy->due_date}.",
                        'sent_at' => Carbon::now(),
                    ]);
                    $sentNotifications++; // Increment the counter
                }
            }

            // Return status response
            return response()->json([
                'message' => $sentNotifications ? "{$sentNotifications} return reminder notifications sent." : 'No return reminders sent.',
            ]);

        } catch (\Exception $e) {
            // Handle any errors and return a response
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }




    public function getUserNotifications(Request $request)
    {
        try {
            // Ensure the user is authenticated
            $user = auth()->user();  // Get the logged-in user
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Get the optional 'type' query parameter (for Overdue or Return Reminder)
            $type = $request->query('type'); // 'Overdue' or 'Return Reminder'

            // Fetch notifications for the logged-in user, filtered by type if provided
            $notificationsQuery = Notification::where('user_id', $user->id);

            if ($type) {
                $notificationsQuery->where('type', $type);
            }

            $notifications = $notificationsQuery->orderBy('sent_at', 'desc')->get();

            // If there are no notifications
            if ($notifications->isEmpty()) {
                return response()->json(['message' => 'No notifications found for this user.'], 404);
            }

            // Return notifications in the response
            return response()->json([
                'message' => 'Notifications fetched successfully.',
                'data' => $notifications,
            ]);

        } catch (\Exception $e) {
            // Handle any errors and return a response
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function markNotificationAsRead($notificationId)
    {
        try {
            $notification = Notification::find($notificationId);

            if (!$notification) {
                return response()->json(['error' => 'Notification not found'], 404);
            }

            // Update the notification's read_at timestamp
            $notification->update([
                'read_at' => Carbon::now(),
            ]);

            return response()->json(['message' => 'Notification marked as read']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }



}


