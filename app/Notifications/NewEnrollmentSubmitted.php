<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEnrollmentSubmitted extends Notification
{
     use Queueable;

    public $enrollment;

    /**
     * Create a new notification instance.
     */
    public function __construct($enrollment)
    {
        $this->enrollment = $enrollment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Enrollment Submission')
            ->greeting('Hello Office Team,')
            ->line('A new student enrollment has been submitted.')

            ->line('--- Enrollment Details ---')
            ->line('Student Name: **' . $this->enrollment->student->first_name . ' ' . $this->enrollment->student->last_name . '**')
            ->line('Phone Number: **' . $this->enrollment->student->phone. '**')
            ->line('Email: **' . $this->enrollment->student->email . '**')
            ->line('Course: **' . ($this->enrollment->course->title ?? 'N/A') . '**')
            ->line('Intake: **' . $this->enrollment->intake->name . '**')
            ->line('Submitted At: **' . $this->enrollment->created_at->format('d/m/Y H:i') . '**')
            ->line('')

            ->line('Please review the details at the admin panel and take appropriate action.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
