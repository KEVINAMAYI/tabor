<?php

namespace App\Notifications;

use App\Models\CourseApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCourseApplicationSubmitted extends Notification
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
        return (new MailMessage)
            ->subject('New Course Application Submission')
            ->greeting('Hello Office Team,')
            ->line('A new course application has been submitted.')

            ->line('--- Application Details ---')
            ->line('Applicant Name: **' . $this->application->first_name . ' ' . $this->application->last_name . '**')
            ->line('Phone Number: **' . $this->application->phone_number . '**')
            ->line('Email: **' . $this->application->email . '**')
            ->line('Course: **' . ($this->application->course->title ?? 'N/A') . '**')
            ->line('Preferred Intake: **' . ($this->application->preferredTrimester?->start_date?->format('M Y') ?? 'Not specified') . '**')
            ->line('Submitted At: **' . $this->application->created_at->format('d/m/Y H:i') . '**')
            ->line('')

            ->line('Please review the application at the admin panel and take appropriate action.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
