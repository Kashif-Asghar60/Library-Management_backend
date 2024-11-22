<?php

// app/Notifications/OverdueBookNotification.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OverdueBookNotification extends Notification
{
    use Queueable;

    protected $bookTitle;
    protected $dueDate;

    public function __construct($bookTitle, $dueDate)
    {
        $this->bookTitle = $bookTitle;
        $this->dueDate = $dueDate;
    }

    public function via($notifiable)
    {
        return ['database'];  // Specify that it's a database notification
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "The book '{$this->bookTitle}' is overdue. It was due on {$this->dueDate}. Please return it as soon as possible."
        ];
    }
}
