<script setup>
import ModernLayout from '@/Layouts/ModernLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Chart from '@/Components/Chart.vue';

const props = defineProps({
    analytics: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
});

const selectedPeriod = ref(props.filters.period);
const customStartDate = ref(props.filters.start_date);
const customEndDate = ref(props.filters.end_date);

function updatePeriod(period) {
    selectedPeriod.value = period;
    
    if (period !== 'custom') {
        router.get(route('admin.analytics.index'), { period }, { preserveState: true });
    }
}

function applyCustomRange() {
    router.get(route('admin.analytics.index'), {
        period: 'custom',
        start_date: customStartDate.value,
        end_date: customEndDate.value,
    }, { preserveState: true });
}

function formatMoney(cents, currency = 'MYR') {
    const amount = (Number(cents || 0) / 100).toFixed(2);
    return `${amount} ${currency}`;
}

// Chart data configurations
const revenueChartData = computed(() => ({
    labels: props.analytics.daily_revenue.map(d => d.date),
    datasets: [{
        label: 'Revenue',
        data: props.analytics.daily_revenue.map(d => d.revenue / 100),
        backgroundColor: 'rgba(59, 130, 246, 0.5)',
        borderColor: 'rgb(59, 130, 246)',
        borderWidth: 2,
    }]
}));

const servicesChartData = computed(() => ({
    labels: props.analytics.popular_services.map(s => s.name),
    datasets: [{
        label: 'Bookings',
        data: props.analytics.popular_services.map(s => s.booking_count),
        backgroundColor: [
            'rgba(59, 130, 246, 0.8)',
            'rgba(16, 185, 129, 0.8)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(239, 68, 68, 0.8)',
            'rgba(139, 92, 246, 0.8)',
        ],
    }]
}));

const timeSlotChartData = computed(() => ({
    labels: props.analytics.popular_time_slots.map(t => t.time_slot),
    datasets: [{
        label: 'Bookings',
        data: props.analytics.popular_time_slots.map(t => t.booking_count),
        backgroundColor: 'rgba(16, 185, 129, 0.5)',
        borderColor: 'rgb(16, 185, 129)',
        borderWidth: 2,
        tension: 0.4,
    }]
}));

const dayOfWeekChartData = computed(() => ({
    labels: props.analytics.bookings_by_day.map(d => d.day_name),
    datasets: [{
        label: 'Bookings',
        data: props.analytics.bookings_by_day.map(d => d.booking_count),
        backgroundColor: 'rgba(139, 92, 246, 0.5)',
        borderColor: 'rgb(139, 92, 246)',
        borderWidth: 2,
    }]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 2,
    plugins: {
        legend: {
            display: true,
            position: 'top',
        },
    },
};
</script>

<template>
    <ModernLayout title="Analytics Dashboard">
        <template #header>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Analytics Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ filters.start_date }} to {{ filters.end_date }}
                    </p>
                </div>
            </div>
        </template>

        <!-- Period Selector -->
        <div class="mb-6 bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Time Period</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="period in ['today', 'week', 'month', 'quarter', 'year']"
                            :key="period"
                            @click="updatePeriod(period)"
                            :class="[
                                'px-3 py-1.5 text-sm font-medium rounded-md border transition-colors',
                                selectedPeriod === period
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                            ]"
                        >
                            {{ period.charAt(0).toUpperCase() + period.slice(1) }}
                        </button>
                        <button
                            @click="selectedPeriod = 'custom'"
                            :class="[
                                'px-3 py-1.5 text-sm font-medium rounded-md border transition-colors',
                                selectedPeriod === 'custom'
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                            ]"
                        >
                            Custom
                        </button>
                    </div>
                </div>
                
                <div v-if="selectedPeriod === 'custom'" class="flex gap-2 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input
                            v-model="customStartDate"
                            type="date"
                            class="px-3 py-1.5 text-sm border border-gray-300 rounded-md"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input
                            v-model="customEndDate"
                            type="date"
                            class="px-3 py-1.5 text-sm border border-gray-300 rounded-md"
                        />
                    </div>
                    <button
                        @click="applyCustomRange"
                        class="px-4 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                    >
                        Apply
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Revenue -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">
                            {{ formatMoney(analytics.revenue.total) }}
                        </p>
                        <p :class="[
                            'mt-2 text-sm font-medium',
                            analytics.revenue.growth_percentage >= 0 ? 'text-green-600' : 'text-red-600'
                        ]">
                            {{ analytics.revenue.growth_percentage >= 0 ? '+' : '' }}{{ analytics.revenue.growth_percentage }}% vs previous
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Bookings -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Bookings</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ analytics.bookings.total }}</p>
                        <p class="mt-2 text-sm text-gray-600">
                            {{ analytics.bookings.confirmed }} confirmed
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Conversion Rate -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Conversion Rate</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ analytics.bookings.conversion_rate }}%</p>
                        <p class="mt-2 text-sm text-gray-600">
                            {{ analytics.bookings.cancelled }} cancelled
                        </p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Average Booking Value -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Avg. Booking Value</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">
                            {{ formatMoney(analytics.average_booking_value.average) }}
                        </p>
                        <p class="mt-2 text-sm text-gray-600">
                            From {{ analytics.average_booking_value.count }} bookings
                        </p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue Chart -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Daily Revenue</h2>
                <Chart v-if="analytics.daily_revenue.length" type="line" :data="revenueChartData" :options="chartOptions" />
                <p v-else class="text-sm text-gray-500 text-center py-8">No revenue data available</p>
            </div>

            <!-- Popular Services -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Top Services</h2>
                <Chart v-if="analytics.popular_services.length" type="doughnut" :data="servicesChartData" :options="chartOptions" />
                <p v-else class="text-sm text-gray-500 text-center py-8">No service data available</p>
            </div>

            <!-- Popular Time Slots -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Popular Time Slots</h2>
                <Chart v-if="analytics.popular_time_slots.length" type="bar" :data="timeSlotChartData" :options="chartOptions" />
                <p v-else class="text-sm text-gray-500 text-center py-8">No time slot data available</p>
            </div>

            <!-- Bookings by Day of Week -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Bookings by Day</h2>
                <Chart v-if="analytics.bookings_by_day.length" type="bar" :data="dayOfWeekChartData" :options="chartOptions" />
                <p v-else class="text-sm text-gray-500 text-center py-8">No day of week data available</p>
            </div>
        </div>

        <!-- Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <!-- Staff Performance -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Staff Performance</h2>
                <div v-if="analytics.staff_performance.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Staff</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bookings</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="staff in analytics.staff_performance" :key="staff.staff_id">
                                <td class="px-3 py-2 text-sm text-gray-900">{{ staff.name }}</td>
                                <td class="px-3 py-2 text-sm text-gray-700">{{ staff.booking_count }}</td>
                                <td class="px-3 py-2 text-sm text-gray-700">{{ formatMoney(staff.revenue) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500 text-center py-4">No staff data available</p>
            </div>

            <!-- Cancellation Rates -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Cancellation Rates by Service</h2>
                <div v-if="analytics.cancellation_rates.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cancelled</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="service in analytics.cancellation_rates" :key="service.service_id">
                                <td class="px-3 py-2 text-sm text-gray-900">{{ service.name }}</td>
                                <td class="px-3 py-2 text-sm text-gray-700">{{ service.total_bookings }}</td>
                                <td class="px-3 py-2 text-sm text-gray-700">{{ service.cancelled_bookings }}</td>
                                <td class="px-3 py-2 text-sm">
                                    <span :class="[
                                        'font-medium',
                                        service.cancellation_rate > 20 ? 'text-red-600' : 'text-gray-700'
                                    ]">
                                        {{ service.cancellation_rate }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500 text-center py-4">No cancellation data available</p>
            </div>
        </div>
    </ModernLayout>
</template>
