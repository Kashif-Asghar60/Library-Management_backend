<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\UserController;


Route::get('/message', function () {
    return response()->json(['message' => 'This is a test message']);
});
// routes/api.php
Route::get('/users', [UserController::class, 'index']);

// Authentication Routes
Route::post('register', [RegisteredUserController::class, 'store']);
Route::post('login', [AuthenticatedSessionController::class, 'store']);
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum');

// Book Management Routes (Protected by auth)

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return response()->json($request->user());
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::middleware(['role:admin'])->group(function () {
        // Admin routes for managing books and copies
        Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{id}', [BookController::class, 'update']);
        Route::delete('/books/{id}', [BookController::class, 'destroy']);
        Route::post('/books/{id}/assign', [BookController::class, 'assignCopyToStudent']);
        Route::get('/book-copies', [BookController::class, 'getAllCopies']);
        Route::get('/books/{bookId}/available-copies', [BookController::class, 'getAvailableCopies']);
        Route::get('/books/search', [BookController::class, 'searchBooks']);

        // Lease Management Routes
        Route::get('/books/borrowed', [BookController::class, 'viewBorrowedBooks']); // View borrowed books
        Route::put('/books/copies/{bookCopyId}/return', [BookController::class, 'markAsReturned']); // Mark book as returned
        Route::put('/books/copies/{bookCopyId}/due-date', [BookController::class, 'setReturnDeadline']); // Set return deadline

        // Notification Routes
        Route::post('/notifications/overdue', [BookController::class, 'sendOverdueNotifications']);
        Route::post('/notifications/return-reminders', [BookController::class, 'sendReturnReminders']);

        // Reports
        Route::get('/reports/borrowing-history', [BookController::class, 'borrowingHistory']);


        Route::get('/reports/popular-books', [BookController::class, 'popularBooksReport']);
        Route::get('/reports/overdue-books', [BookController::class, 'overdueBooksReport']);
        Route::get('/reports/student-activity', [BookController::class, 'studentActivityReport']);


    });

    Route::post('/notifications/return-reminders', [BookController::class, 'sendReturnReminders']);
    // Route to fetch user notifications
    Route::get('/notifications', [BookController::class, 'getUserNotifications']);
    Route::patch('/notifications/{id}/read', [BookController::class, 'markNotificationAsRead']);
    // Route for fetching notifications for the authenticated user
    // Route::get('/notifications', [UserController::class, 'fetchNotifications']);

    // Route for fetching notifications for all users (admin or test)
    Route::get('/notifications/all', [UserController::class, 'fetchAllNotifications']);

    // Routes for students
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{id}', [BookController::class, 'show']);
    Route::get('/books/borrowed/{userId}', [BookController::class, 'viewBorrowedBooksByUser']);
    Route::get('/reports/student-activity/{userId}', [BookController::class, 'studentActivityReportByUser']);

});
