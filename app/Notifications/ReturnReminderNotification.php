<?php


// app/Notifications/ReturnReminderNotification.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReturnReminderNotification extends Notification
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
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "The book '{$this->bookTitle}' is due on {$this->dueDate}. Please return it on time to avoid penalties."
        ];
    }
}