<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification implements ShouldQueue
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
        $endsAt = $this->booking->ends_at?->setTimezone($notifiable->timezone ?? 'UTC');
        
        return (new MailMessage)
            ->subject('Booking Confirmation - ' . $this->booking->servicePrice?->service?->name)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your booking has been confirmed.')
            ->line('**Service:** ' . $this->booking->servicePrice?->service?->name)
            ->line('**Date & Time:** ' . $startsAt?->format('l, F j, Y \a\t g:i A'))
            ->line('**Duration:** ' . ($this->booking->servicePrice?->duration_min ?? 0) . ' minutes')
            ->line('**Staff:** ' . ($this->booking->staff?->name ?? 'TBA'))
            ->line('**Amount:** ' . number_format($this->booking->total_amount / 100, 2) . ' ' . $this->booking->currency)
            ->action('View Booking', route('my-bookings'))
            ->line('We look forward to seeing you!');
    }
}
