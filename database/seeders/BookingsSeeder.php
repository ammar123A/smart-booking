<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use App\Models\ServicePrice;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BookingsSeeder extends Seeder
{
    public function run(): void
    {
        $alice   = User::where('email', 'alice@example.com')->first();
        $bob     = User::where('email', 'bob@example.com')->first();
        $charlie = User::where('email', 'charlie@example.com')->first();
        $diana   = User::where('email', 'diana@example.com')->first();

        if (! $alice || ! $bob || ! $charlie || ! $diana) {
            return; // customers not seeded yet
        }

        // Fetch service prices by name for clarity
        $prices = ServicePrice::with('service')->get()->keyBy(fn ($p) => $p->service->name . '|' . $p->name);

        // Fetch staff
        $staffAlice   = Staff::where('name', 'Alice')->first();
        $staffBob     = Staff::where('name', 'Bob')->first();
        $staffCharlie = Staff::where('name', 'Charlie')->first();
        $staffDiana   = Staff::where('name', 'Diana')->first();
        $staffEthan   = Staff::where('name', 'Ethan')->first();
        $staffFiona   = Staff::where('name', 'Fiona')->first();

        $bookings = [
            // ── Past confirmed bookings ──
            [
                'customer'      => $alice,
                'staff'         => $staffAlice,
                'price'         => $prices['Consultation|30 minutes'] ?? null,
                'starts_at'     => now()->subDays(30)->setTime(10, 0),
                'status'        => Booking::STATUS_CONFIRMED,
                'paid'          => true,
                'review'        => ['rating' => 5, 'comment' => 'Very professional and helpful!'],
            ],
            [
                'customer'      => $alice,
                'staff'         => $staffBob,
                'price'         => $prices['Massage Therapy|60 minutes'] ?? null,
                'starts_at'     => now()->subDays(20)->setTime(14, 0),
                'status'        => Booking::STATUS_CONFIRMED,
                'paid'          => true,
                'review'        => ['rating' => 4, 'comment' => 'Very relaxing session. Will come again.'],
            ],
            [
                'customer'      => $alice,
                'staff'         => $staffDiana,
                'price'         => $prices['Haircut|Premium'] ?? null,
                'starts_at'     => now()->subDays(10)->setTime(11, 0),
                'status'        => Booking::STATUS_CONFIRMED,
                'paid'          => true,
                'review'        => ['rating' => 5, 'comment' => 'Amazing style, very happy!'],
            ],
            [
                'customer'      => $bob,
                'staff'         => $staffCharlie,
                'price'         => $prices['Personal Training|Single Session'] ?? null,
                'starts_at'     => now()->subDays(25)->setTime(8, 0),
                'status'        => Booking::STATUS_CONFIRMED,
                'paid'          => true,
                'review'        => ['rating' => 4, 'comment' => 'Great trainer, good workout plan.'],
            ],
            [
                'customer'      => $bob,
                'staff'         => $staffAlice,
                'price'         => $prices['Dental Checkup|Standard Checkup'] ?? null,
                'starts_at'     => now()->subDays(15)->setTime(9, 30),
                'status'        => Booking::STATUS_CONFIRMED,
                'paid'          => true,
                'review'        => null,
            ],
            [
                'customer'      => $charlie,
                'staff'         => $staffFiona,
                'price'         => $prices['Haircut|Standard'] ?? null,
                'starts_at'     => now()->subDays(7)->setTime(10, 30),
                'status'        => Booking::STATUS_CONFIRMED,
                'paid'          => true,
                'review'        => ['rating' => 3, 'comment' => 'Decent service, could be better.'],
            ],
            [
                'customer'      => $diana,
                'staff'         => $staffBob,
                'price'         => $prices['Consultation|60 minutes'] ?? null,
                'starts_at'     => now()->subDays(45)->setTime(15, 0),
                'status'        => Booking::STATUS_CONFIRMED,
                'paid'          => true,
                'review'        => ['rating' => 5, 'comment' => 'Thorough and insightful consultation.'],
            ],
            [
                'customer'      => $diana,
                'staff'         => $staffEthan,
                'price'         => $prices['Massage Therapy|90 minutes'] ?? null,
                'starts_at'     => now()->subDays(35)->setTime(13, 0),
                'status'        => Booking::STATUS_CONFIRMED,
                'paid'          => true,
                'review'        => ['rating' => 5, 'comment' => 'Best massage ever, totally refreshed!'],
            ],

            // ── Upcoming confirmed bookings ──
            [
                'customer'      => $alice,
                'staff'         => $staffEthan,
                'price'         => $prices['Personal Training|Single Session'] ?? null,
                'starts_at'     => now()->addDays(3)->setTime(9, 0),
                'status'        => Booking::STATUS_CONFIRMED,
                'paid'          => true,
                'review'        => null,
            ],
            [
                'customer'      => $bob,
                'staff'         => $staffFiona,
                'price'         => $prices['Haircut|Premium'] ?? null,
                'starts_at'     => now()->addDays(5)->setTime(11, 0),
                'status'        => Booking::STATUS_CONFIRMED,
                'paid'          => true,
                'review'        => null,
            ],
            [
                'customer'      => $diana,
                'staff'         => $staffCharlie,
                'price'         => $prices['Personal Training|Single Session'] ?? null,
                'starts_at'     => now()->addDays(7)->setTime(10, 0),
                'status'        => Booking::STATUS_CONFIRMED,
                'paid'          => true,
                'review'        => null,
            ],

            // ── Pending payment ──
            [
                'customer'      => $charlie,
                'staff'         => $staffBob,
                'price'         => $prices['Consultation|30 minutes'] ?? null,
                'starts_at'     => now()->addDays(2)->setTime(14, 0),
                'status'        => Booking::STATUS_PENDING_PAYMENT,
                'paid'          => false,
                'review'        => null,
            ],

            // ── Cancelled ──
            [
                'customer'      => $bob,
                'staff'         => $staffAlice,
                'price'         => $prices['Consultation|60 minutes'] ?? null,
                'starts_at'     => now()->subDays(5)->setTime(10, 0),
                'status'        => Booking::STATUS_CANCELLED,
                'paid'          => false,
                'review'        => null,
            ],
        ];

        foreach ($bookings as $data) {
            if (! $data['price'] || ! $data['staff']) {
                continue;
            }

            $price     = $data['price'];
            $startsAt  = Carbon::instance($data['starts_at']);
            $endsAt    = $startsAt->copy()->addMinutes($price->duration_min);

            // Skip if booking already exists for this staff/time slot
            $exists = Booking::where('staff_id', $data['staff']->id)
                ->where('starts_at', $startsAt)
                ->exists();

            if ($exists) {
                continue;
            }

            $booking = Booking::create([
                'customer_id'      => $data['customer']->id,
                'staff_id'         => $data['staff']->id,
                'service_price_id' => $price->id,
                'status'           => $data['status'],
                'starts_at'        => $startsAt,
                'ends_at'          => $endsAt,
                'expires_at'       => $data['status'] === Booking::STATUS_PENDING_PAYMENT
                                        ? $startsAt->copy()->addHours(1)
                                        : null,
                'total_amount'     => $price->amount,
                'currency'         => $price->currency ?? 'MYR',
            ]);

            // Create a payment record for paid bookings
            if ($data['paid']) {
                Payment::create([
                    'booking_id'   => $booking->id,
                    'provider'     => Payment::PROVIDER_STRIPE,
                    'provider_ref' => 'pi_seed_' . str_pad($booking->id, 6, '0', STR_PAD_LEFT),
                    'status'       => Payment::STATUS_PAID,
                    'amount'       => $price->amount,
                    'currency'     => $price->currency ?? 'MYR',
                    'paid_at'      => $startsAt->copy()->subHours(2),
                    'meta'         => ['seeded' => true],
                ]);
            }

            // Create a review for past confirmed bookings that have review data
            if ($data['review'] && $data['status'] === Booking::STATUS_CONFIRMED && $startsAt->isPast()) {
                Review::create([
                    'booking_id'  => $booking->id,
                    'customer_id' => $data['customer']->id,
                    'staff_id'    => $data['staff']->id,
                    'service_id'  => $price->service_id,
                    'rating'      => $data['review']['rating'],
                    'comment'     => $data['review']['comment'],
                ]);
            }
        }
    }
}
