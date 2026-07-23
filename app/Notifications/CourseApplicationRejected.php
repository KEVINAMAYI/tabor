<?php

namespace App\Notifications;

use App\Models\CourseApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseApplicationRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public CourseApplication $application;

    public function __construct(CourseApplication $application)
    {
        $this->application = $application;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Update on Your Course Application')
            ->greeting('Dear ' . $this->application->first_name . ',')
            ->line('Thank you for your interest in **' . ($this->application->course->title ?? 'our course') . '** at Tabor Training Institute.')
            ->line('After reviewing your application, we regret to inform you that we are unable to offer you admission at this time.');

        if (!empty($this->application->rejection_reason)) {
            $mail->line('Reason: ' . $this->application->rejection_reason);
        }

        return $mail->line('We encourage you to apply again in the future or reach out to our office if you have any questions.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
