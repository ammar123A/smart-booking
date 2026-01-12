# Smart Booking (Services) + Stripe

Production-style service booking system focusing on:
- Auto-assign any available staff member
- Prevent double booking (DB-enforced)
- Payment verification (Stripe Checkout + webhook + queue)
- Booking expiry (auto-cancel if unpaid in 10 minutes)
- Role-based access (Spatie Permission)

## Stack
- Backend: Laravel + Jetstream (Inertia + Vue)
- DB: PostgreSQL
- Roles: Spatie Permission

## Local setup (Windows PowerShell)
This repo uses `npm.cmd` because PowerShell may block `npm.ps1` by execution policy.

```powershell
Set-Location "C:\Users\Public\Documents\smart-booking"

composer install

Copy-Item .env.example .env -Force
php artisan key:generate

# Edit .env with your Postgres credentials, then:
php artisan migrate:fresh --seed

npm.cmd install
npm.cmd run dev

php artisan serve
```

## Default local admin
- Email: `test@example.com`
- Password: `password`

## Availability endpoint (example)
After logging in, call:
- `GET /services/{service}/availability?service_price_id={id}&date=YYYY-MM-DD&timezone=UTC`

## Create booking (auto-assign)
After logging in, call:
- `POST /bookings`

Example JSON body:
```json
{
	"service_price_id": 1,
	"starts_at": "2026-01-05T09:00:00Z",
	"timezone": "UTC"
}
```

## Start Stripe payment
After logging in, call:
- `POST /payments/stripe/{booking}`

This returns JSON with `payment_url` (Stripe Checkout URL) when `Accept: application/json` is set, or redirects to Stripe Checkout otherwise.

## Stripe webhook
Configure Stripe to send webhooks to:
- `POST /api/payments/stripe/webhook`

Supported events:
- `checkout.session.completed` - When payment is successful

Notes:
- Webhook signature verification uses `STRIPE_WEBHOOK_SECRET`.
- When a successful webhook is received, the app dispatches `FinalizeBookingPayment` to confirm the booking.

## Payment return URLs
After payment completion or cancellation, users are redirected to:
- Success: `GET /payments/stripe/success?session_id={CHECKOUT_SESSION_ID}`
- Cancel: `GET /payments/stripe/cancel?booking_id={booking_id}`

## Run the scheduler locally
The booking expiry command is scheduled every minute. In local dev you can run:
```powershell
php artisan schedule:work
```
