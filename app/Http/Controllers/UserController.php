<?php
// app/Http/Controllers/UserController.php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        // Return all users or apply pagination as needed
        return response()->json(User::all());
    }

    public function fetchNotifications(Request $request)
    {
        // Retrieve notifications for the authenticated user, including the 'sent_at' timestamp
        $notifications = $request->user()->notifications()
            ->select('id', 'type', 'data', 'read_at', 'sent_at', 'created_at', 'updated_at')
            ->get();

        // If no notifications are found, return a message
        if ($notifications->isEmpty()) {
            return response()->json(['message' => 'No notifications found'], 200);
        }

        // Return the notifications as a JSON response
        return response()->json($notifications, 200);
    }


    public function fetchAllNotifications()
    {
        // Fetch all users
        $users = User::all();

        $allNotifications = [];

        foreach ($users as $user) {
            // For each user, fetch their notifications including 'sent_at' field
            $notifications = $user->notifications()
                ->select('id', 'type', 'data', 'read_at', 'sent_at', 'created_at', 'updated_at')
                ->get();

            if ($notifications->isNotEmpty()) {
                // Add the user ID along with the user name and notifications in the response
                $allNotifications[] = [
                    'user_id' => $user->id,       // Adding the unique user ID
                    'user_name' => $user->name,   // Keeping the user name
                    'notifications' => $notifications
                ];
            }
        }

        // Return all notifications for all users
        return response()->json($allNotifications, 200);
    }

}
