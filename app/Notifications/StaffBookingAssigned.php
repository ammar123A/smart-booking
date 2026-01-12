<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffBookingAssigned extends Notification implements ShouldQueue
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
        $this->booking->load(['service' => fn($q) => $q->select('id', 'name'), 'customer', 'servicePrice']);
        
        $startsAt = $this->booking->starts_at?->setTimezone($notifiable->timezone ?? 'UTC');
        
        return (new MailMessage)
            ->subject('New Booking Assignment - ' . $this->booking->servicePrice?->service?->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have been assigned to a new booking.')
            ->line('**Service:** ' . $this->booking->servicePrice?->service?->name)
            ->line('**Customer:** ' . ($this->booking->customer?->name ?? 'N/A'))
            ->line('**Date & Time:** ' . $startsAt?->format('l, F j, Y \a\t g:i A'))
            ->line('**Duration:** ' . ($this->booking->servicePrice?->duration_min ?? 0) . ' minutes')
            ->action('View Booking', route('admin.bookings.show', $this->booking))
            ->line('Please ensure you are available at the scheduled time.');
    }
}
