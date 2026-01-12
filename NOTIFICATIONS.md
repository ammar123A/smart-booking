# Automated Notifications System

The smart booking system now includes automated email notifications for customers and staff.

## Notification Types

### 1. Booking Confirmed (`BookingConfirmed`)
**Sent to:** Customer  
**Triggered when:**
- Admin creates a booking directly (status: confirmed)
- Customer completes payment (status changes from pending_payment to confirmed)

**Contains:**
- Service name and details
- Date, time, and duration
- Assigned staff member
- Total amount paid
- Link to view booking

---

### 2. Booking Cancelled (`BookingCancelled`)
**Sent to:** Customer  
**Triggered when:** Admin changes booking status to "cancelled"

**Contains:**
- Service name
- Original appointment date and time
- Assigned staff member
- Notice about cancellation

---

### 3. Booking Rescheduled (`BookingRescheduled`)
**Sent to:** Customer  
**Triggered when:** Admin changes the appointment date/time or reassigns staff

**Contains:**
- Previous date and time (if available)
- New date and time
- Assigned staff member
- Service details

---

### 4. Booking Reminder (`BookingReminder`)
**Sent to:** Customer  
**Triggered when:** Automated job runs hourly and finds bookings starting in 24 hours

**Contains:**
- Service name and details
- Appointment date and time
- Hours until appointment
- Reminder to arrive early

---

### 5. Staff Booking Assigned (`StaffBookingAssigned`)
**Sent to:** Staff member  
**Triggered when:**
- New booking is assigned to them
- They are reassigned to an existing booking

**Contains:**
- Customer name
- Service name
- Appointment date and time
- Duration
- Link to view booking details

---

## Mail Configuration

The system uses Laravel's mail system. By default, emails are logged to `storage/logs/laravel.log` for development.

### Development (Log Driver - Default)
Emails are written to the log file. Check `storage/logs/laravel.log` to see email content.

### Production (SMTP)
Update your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Using Mailgun, SendGrid, or other services
Follow Laravel's documentation for specific provider setup.

---

## Queue System

All notifications implement `ShouldQueue`, meaning they will be queued for background processing if queues are enabled.

### Development (Sync Queue - Default)
Notifications are sent immediately in the same request.

### Production (Redis/Database Queue)
1. Update `.env`:
```env
QUEUE_CONNECTION=redis  # or database
```

2. Run queue worker:
```bash
php artisan queue:work
```

3. Use supervisor to keep queue worker running in production.

---

## Scheduled Tasks

### Booking Reminders
The system automatically sends reminders 24 hours before appointments.

**Command:** `php artisan bookings:send-reminders`  
**Schedule:** Runs hourly (configured in `routes/console.php`)

### Running the Scheduler

#### Development
```bash
php artisan schedule:work
```

#### Production
Add to crontab:
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Testing Notifications

### View Logged Emails
When using the `log` driver, check:
```bash
tail -f storage/logs/laravel.log
```

### Test Specific Notification
```php
// In tinker (php artisan tinker)
$booking = \App\Models\Booking::find(1);
$booking->load('customer');
$booking->customer->notify(new \App\Notifications\BookingConfirmed($booking));
```

### Manual Reminder Test
```bash
php artisan bookings:send-reminders
```

---

## Customizing Email Templates

Laravel uses Markdown for email templates by default. The notification classes use the `MailMessage` builder which automatically generates nice-looking HTML emails.

### Publish Vendor Views (Optional)
To customize the email layout:
```bash
php artisan vendor:publish --tag=laravel-mail
```

Templates will be in `resources/views/vendor/mail/`.

---

## Adding More Notifications

1. Create notification class:
```bash
php artisan make:notification CustomerFeedbackRequest
```

2. Implement the notification in `app/Notifications/`

3. Send the notification:
```php
$user->notify(new CustomerFeedbackRequest($booking));
```

---

## Staff Email Requirements

Staff members must have an email address to receive notifications. The email field is stored in the `staff` table.

When creating or editing staff members, ensure their email is set.

---

## Troubleshooting

### Notifications not sending
1. Check queue is running: `php artisan queue:work`
2. Verify mail configuration in `.env`
3. Check logs: `storage/logs/laravel.log`

### Reminders not working
1. Ensure scheduler is running: `php artisan schedule:work` (dev) or cron job (production)
2. Check command manually: `php artisan bookings:send-reminders`
3. Verify bookings exist 24 hours from now with status "confirmed"

### Staff not receiving emails
1. Verify staff has email address in database
2. Check staff table has `email` column (migration ran successfully)
3. Verify email isn't being filtered as spam

---

## Future Enhancements

Potential additions to the notification system:

- SMS notifications via Twilio/Nexmo
- Push notifications for mobile apps
- Customer preferences (opt-in/opt-out)
- Multi-language support
- Custom email templates per service type
- WhatsApp notifications
- Slack/Teams notifications for staff
- Email verification for staff
- Notification history/audit log
