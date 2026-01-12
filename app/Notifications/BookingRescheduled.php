<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRescheduled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public ?string $oldStartsAt = null,
        public ?string $oldStaffName = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->booking->load(['service' => fn($q) => $q->select('id', 'name'), 'staff', 'servicePrice']);
        
        $startsAt = $this->booking->starts_at?->setTimezone($notifiable->timezone ?? 'UTC');
        
        $message = (new MailMessage)
            ->subject('Booking Rescheduled - ' . $this->booking->servicePrice?->service?->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your booking has been rescheduled.');
        
        if ($this->oldStartsAt) {
            $message->line('**Previous Date & Time:** ' . $this->oldStartsAt);
        }
        
        $message->line('**New Date & Time:** ' . $startsAt?->format('l, F j, Y \a\t g:i A'))
            ->line('**Duration:** ' . ($this->booking->servicePrice?->duration_min ?? 0) . ' minutes')
            ->line('**Staff:** ' . ($this->booking->staff?->name ?? 'TBA'))
            ->action('View Booking', route('my-bookings'))
            ->line('See you at your new appointment time!');
        
        return $message;
    }
}
