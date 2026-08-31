<?php

namespace App\Notifications;

use App\Models\CourseApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseApplicationApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public CourseApplication $application;

    public string $admissionDate;

    public function __construct(CourseApplication $application, string $admissionDate)
    {
        $this->application = $application;
        $this->admissionDate = $admissionDate;
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
        return (new MailMessage)
            ->subject('Your Course Application Has Been Approved')
            ->greeting('Congratulations, ' . $this->application->first_name . '!')
            ->line('Your application to join **' . ($this->application->course->title ?? 'your selected course') . '** has been approved.')
            ->line('Admission Date: **' . \Carbon\Carbon::parse($this->admissionDate)->format('d/m/Y') . '**')
            ->line('Our office will be in touch shortly with further details on fees, onboarding, and next steps.')
            ->line('Welcome to Tabor Training Institute!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
