<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelled extends Notification implements ShouldQueue
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
        
        return (new MailMessage)
            ->subject('Booking Cancelled - ' . $this->booking->servicePrice?->service?->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your booking has been cancelled.')
            ->line('**Service:** ' . $this->booking->servicePrice?->service?->name)
            ->line('**Original Date & Time:** ' . $startsAt?->format('l, F j, Y \a\t g:i A'))
            ->line('**Staff:** ' . ($this->booking->staff?->name ?? 'TBA'))
            ->line('If you did not request this cancellation, please contact us immediately.')
            ->action('Book Again', route('checkout'))
            ->line('Thank you for your understanding.');
    }
}
