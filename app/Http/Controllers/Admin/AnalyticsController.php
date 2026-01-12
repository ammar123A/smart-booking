<?php

namespace App\Http\Controllers\Admin;

use App\Services\AnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController
{
    public function index(Request $request, AnalyticsService $analytics)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'period' => ['nullable', 'string', 'in:today,week,month,quarter,year,custom'],
        ]);

        $period = $validated['period'] ?? 'month';
        
        // Calculate date range based on period
        [$startDate, $endDate] = $this->getDateRange($period, $validated);

        $data = $analytics->getDashboardData($startDate, $endDate);

        return Inertia::render('Admin/Analytics/Index', [
            'analytics' => $data,
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'period' => $period,
            ],
        ]);
    }

    private function getDateRange(string $period, array $validated): array
    {
        $now = CarbonImmutable::now();

        if ($period === 'custom' && isset($validated['start_date']) && isset($validated['end_date'])) {
            return [
                CarbonImmutable::parse($validated['start_date'])->startOfDay(),
                CarbonImmutable::parse($validated['end_date'])->endOfDay(),
            ];
        }

        return match ($period) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'week' => [$now->startOfWeek(), $now->endOfWeek()],
            'month' => [$now->startOfMonth(), $now->endOfMonth()],
            'quarter' => [$now->startOfQuarter(), $now->endOfQuarter()],
            'year' => [$now->startOfYear(), $now->endOfYear()],
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };
    }
}
