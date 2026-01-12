<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Staff;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get revenue statistics for a date range
     */
    public function getRevenueStats(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $totalRevenue = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('amount');

        $previousPeriodDays = $startDate->diffInDays($endDate);
        $previousStart = $startDate->subDays($previousPeriodDays);
        $previousEnd = $startDate;

        $previousRevenue = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->whereBetween('paid_at', [$previousStart, $previousEnd])
            ->sum('amount');

        $growth = $previousRevenue > 0 
            ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;

        return [
            'total' => (int) $totalRevenue,
            'previous_period' => (int) $previousRevenue,
            'growth_percentage' => round($growth, 2),
        ];
    }

    /**
     * Get revenue breakdown by day
     */
    public function getDailyRevenue(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $data = Payment::query()
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as revenue, COUNT(*) as count')
            ->where('status', Payment::STATUS_PAID)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $data->map(fn($item) => [
            'date' => $item->date,
            'revenue' => (int) $item->revenue,
            'count' => (int) $item->count,
        ])->toArray();
    }

    /**
     * Get booking statistics
     */
    public function getBookingStats(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $total = Booking::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $confirmed = Booking::query()
            ->where('status', Booking::STATUS_CONFIRMED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $cancelled = Booking::query()
            ->where('status', Booking::STATUS_CANCELLED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $pending = Booking::query()
            ->where('status', Booking::STATUS_PENDING_PAYMENT)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $expired = Booking::query()
            ->where('status', Booking::STATUS_EXPIRED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $refunded = Booking::query()
            ->where('status', Booking::STATUS_REFUNDED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'cancelled' => $cancelled,
            'pending' => $pending,
            'expired' => $expired,
            'refunded' => $refunded,
            'conversion_rate' => $total > 0 ? round(($confirmed / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get most popular services
     */
    public function getPopularServices(CarbonImmutable $startDate, CarbonImmutable $endDate, int $limit = 10): array
    {
        $data = Booking::query()
            ->selectRaw('service_prices.service_id, services.name, COUNT(*) as booking_count, SUM(bookings.total_amount) as revenue')
            ->join('service_prices', 'bookings.service_price_id', '=', 'service_prices.id')
            ->join('services', 'service_prices.service_id', '=', 'services.id')
            ->where('bookings.status', Booking::STATUS_CONFIRMED)
            ->whereBetween('bookings.starts_at', [$startDate, $endDate])
            ->groupBy('service_prices.service_id', 'services.name')
            ->orderByDesc('booking_count')
            ->limit($limit)
            ->get();

        return $data->map(fn($item) => [
            'service_id' => $item->service_id,
            'name' => $item->name,
            'booking_count' => (int) $item->booking_count,
            'revenue' => (int) $item->revenue,
        ])->toArray();
    }

    /**
     * Get staff performance metrics
     */
    public function getStaffPerformance(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $data = Booking::query()
            ->selectRaw('staff_id, staff.name, COUNT(*) as booking_count, SUM(bookings.total_amount) as revenue')
            ->join('staff', 'bookings.staff_id', '=', 'staff.id')
            ->where('bookings.status', Booking::STATUS_CONFIRMED)
            ->whereBetween('bookings.starts_at', [$startDate, $endDate])
            ->groupBy('staff_id', 'staff.name')
            ->orderByDesc('booking_count')
            ->get();

        return $data->map(fn($item) => [
            'staff_id' => $item->staff_id,
            'name' => $item->name,
            'booking_count' => (int) $item->booking_count,
            'revenue' => (int) $item->revenue,
        ])->toArray();
    }

    /**
     * Get popular time slots (by hour of day)
     */
    public function getPopularTimeSlots(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $data = Booking::query()
            ->selectRaw('HOUR(starts_at) as hour, COUNT(*) as booking_count')
            ->where('status', Booking::STATUS_CONFIRMED)
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return $data->map(fn($item) => [
            'hour' => (int) $item->hour,
            'time_slot' => sprintf('%02d:00', $item->hour),
            'booking_count' => (int) $item->booking_count,
        ])->toArray();
    }

    /**
     * Get booking distribution by day of week
     */
    public function getBookingsByDayOfWeek(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $data = Booking::query()
            ->selectRaw('DAYOFWEEK(starts_at) as day_of_week, COUNT(*) as booking_count')
            ->where('status', Booking::STATUS_CONFIRMED)
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get();

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return $data->map(fn($item) => [
            'day_of_week' => (int) $item->day_of_week,
            'day_name' => $dayNames[$item->day_of_week - 1] ?? 'Unknown',
            'booking_count' => (int) $item->booking_count,
        ])->toArray();
    }

    /**
     * Get cancellation rate by service
     */
    public function getCancellationRateByService(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $allBookings = Booking::query()
            ->selectRaw('service_prices.service_id, services.name, COUNT(*) as total')
            ->join('service_prices', 'bookings.service_price_id', '=', 'service_prices.id')
            ->join('services', 'service_prices.service_id', '=', 'services.id')
            ->whereBetween('bookings.created_at', [$startDate, $endDate])
            ->groupBy('service_prices.service_id', 'services.name')
            ->get();

        $cancelledBookings = Booking::query()
            ->selectRaw('service_prices.service_id, COUNT(*) as cancelled')
            ->join('service_prices', 'bookings.service_price_id', '=', 'service_prices.id')
            ->whereIn('bookings.status', [Booking::STATUS_CANCELLED, Booking::STATUS_REFUNDED])
            ->whereBetween('bookings.created_at', [$startDate, $endDate])
            ->groupBy('service_prices.service_id')
            ->pluck('cancelled', 'service_id');

        return $allBookings->map(function($item) use ($cancelledBookings) {
            $cancelled = $cancelledBookings->get($item->service_id, 0);
            return [
                'service_id' => $item->service_id,
                'name' => $item->name,
                'total_bookings' => (int) $item->total,
                'cancelled_bookings' => (int) $cancelled,
                'cancellation_rate' => $item->total > 0 ? round(($cancelled / $item->total) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Get average booking value
     */
    public function getAverageBookingValue(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $result = Booking::query()
            ->selectRaw('AVG(total_amount) as avg_amount, COUNT(*) as count')
            ->where('status', Booking::STATUS_CONFIRMED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->first();

        return [
            'average' => $result ? (int) $result->avg_amount : 0,
            'count' => $result ? (int) $result->count : 0,
        ];
    }

    /**
     * Get comprehensive dashboard data
     */
    public function getDashboardData(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return [
            'revenue' => $this->getRevenueStats($startDate, $endDate),
            'daily_revenue' => $this->getDailyRevenue($startDate, $endDate),
            'bookings' => $this->getBookingStats($startDate, $endDate),
            'popular_services' => $this->getPopularServices($startDate, $endDate, 5),
            'staff_performance' => $this->getStaffPerformance($startDate, $endDate),
            'popular_time_slots' => $this->getPopularTimeSlots($startDate, $endDate),
            'bookings_by_day' => $this->getBookingsByDayOfWeek($startDate, $endDate),
            'average_booking_value' => $this->getAverageBookingValue($startDate, $endDate),
            'cancellation_rates' => $this->getCancellationRateByService($startDate, $endDate),
        ];
    }
}
