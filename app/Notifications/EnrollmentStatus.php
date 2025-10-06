<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EnrollmentStatus extends Notification implements ShouldQueue
{
    use Queueable;

    public $status;
    public $program;

    /**
     * Create a new notification instance.
     */
    public function __construct($status, $program)
    {
        $this->status = $status;
        $this->program = $program;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Enrollment Status Update")
            ->view('emails.enrollment_status', [
                'user' => $notifiable,
                'status' => $this->status,
                'program' => $this->program,
            ]);
    }
}
