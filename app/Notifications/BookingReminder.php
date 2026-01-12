<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->booking->load(['service' => fn($q) => $q->select('id', 'name'), 'staff', 'servicePrice']);
        
        $startsAt = $this->booking->starts_at?->setTimezone($notifiable->timezone ?? 'UTC');
        $hoursUntil = now()->diffInHours($this->booking->starts_at, false);
        
        return (new MailMessage)
            ->subject('Reminder: Upcoming Appointment - ' . $this->booking->servicePrice?->service?->name)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('This is a friendly reminder about your upcoming appointment.')
            ->line('**Service:** ' . $this->booking->servicePrice?->service?->name)
            ->line('**Date & Time:** ' . $startsAt?->format('l, F j, Y \a\t g:i A'))
            ->line('**Duration:** ' . ($this->booking->servicePrice?->duration_min ?? 0) . ' minutes')
            ->line('**Staff:** ' . ($this->booking->staff?->name ?? 'TBA'))
            ->line('**In:** ' . round($hoursUntil) . ' hours')
            ->action('View Details', route('my-bookings'))
            ->line('Please arrive 5-10 minutes early. We look forward to seeing you!');
    }
}
