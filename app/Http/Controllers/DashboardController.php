<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Staff;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin');

        $data = [];

        if ($isAdmin) {
            // Admin dashboard with quick stats
            $today = CarbonImmutable::today();
            $thisMonth = CarbonImmutable::now()->startOfMonth();

            // Today's stats
            $todayBookings = Booking::query()
                ->whereDate('starts_at', $today)
                ->count();

            $todayRevenue = Payment::query()
                ->where('status', Payment::STATUS_PAID)
                ->whereDate('paid_at', $today)
                ->sum('amount');

            // This month's stats
            $monthBookings = Booking::query()
                ->where('status', Booking::STATUS_CONFIRMED)
                ->where('starts_at', '>=', $thisMonth)
                ->count();

            $monthRevenue = Payment::query()
                ->where('status', Payment::STATUS_PAID)
                ->where('paid_at', '>=', $thisMonth)
                ->sum('amount');

            // Pending bookings
            $pendingBookings = Booking::query()
                ->where('status', Booking::STATUS_PENDING_PAYMENT)
                ->where('expires_at', '>', CarbonImmutable::now())
                ->count();

            // Upcoming bookings today
            $upcomingToday = Booking::query()
                ->where('status', Booking::STATUS_CONFIRMED)
                ->whereDate('starts_at', $today)
                ->where('ends_at', '>', CarbonImmutable::now())
                ->with(['customer:id,name,email', 'staff:id,name', 'servicePrice.service'])
                ->orderBy('starts_at')
                ->limit(5)
                ->get()
                ->map(fn($b) => [
                    'id' => $b->id,
                    'starts_at' => $b->starts_at?->toIso8601String(),
                    'customer_name' => $b->customer?->name,
                    'service_name' => $b->servicePrice?->service?->name,
                    'staff_name' => $b->staff?->name,
                ]);

            // Recent bookings
            $recentBookings = Booking::query()
                ->with(['customer:id,name,email', 'staff:id,name', 'servicePrice.service'])
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(fn($b) => [
                    'id' => $b->id,
                    'status' => $b->status,
                    'starts_at' => $b->starts_at?->toIso8601String(),
                    'created_at' => $b->created_at?->toIso8601String(),
                    'customer_name' => $b->customer?->name,
                    'service_name' => $b->servicePrice?->service?->name,
                ]);

            $data = [
                'stats' => [
                    'today_bookings' => $todayBookings,
                    'today_revenue' => (int) $todayRevenue,
                    'month_bookings' => $monthBookings,
                    'month_revenue' => (int) $monthRevenue,
                    'pending_bookings' => $pendingBookings,
                    'total_services' => Service::query()->where('active', true)->count(),
                    'total_staff' => Staff::query()->where('active', true)->count(),
                ],
                'upcoming_today' => $upcomingToday,
                'recent_bookings' => $recentBookings,
            ];
        } else {
            // Customer dashboard
            $upcomingBookings = Booking::query()
                ->where('customer_id', $user->id)
                ->where('status', Booking::STATUS_CONFIRMED)
                ->where('ends_at', '>', CarbonImmutable::now())
                ->with(['staff:id,name', 'servicePrice.service'])
                ->orderBy('starts_at')
                ->limit(3)
                ->get()
                ->map(fn($b) => [
                    'id' => $b->id,
                    'starts_at' => $b->starts_at?->toIso8601String(),
                    'ends_at' => $b->ends_at?->toIso8601String(),
                    'service_name' => $b->servicePrice?->service?->name,
                    'staff_name' => $b->staff?->name,
                ]);

            $totalBookings = Booking::query()
                ->where('customer_id', $user->id)
                ->count();

            $data = [
                'upcoming_bookings' => $upcomingBookings,
                'total_bookings' => $totalBookings,
            ];
        }

        return Inertia::render('Dashboard', $data);
    }
}
