<script setup>
import ModernLayout from '@/Layouts/ModernLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: {
        type: Object,
        default: null,
    },
    upcoming_today: {
        type: Array,
        default: () => [],
    },
    recent_bookings: {
        type: Array,
        default: () => [],
    },
    upcoming_bookings: {
        type: Array,
        default: () => [],
    },
    total_bookings: {
        type: Number,
        default: 0,
    },
});

function formatMoney(cents, currency = 'MYR') {
    const amount = (Number(cents || 0) / 100).toFixed(2);
    return `${amount} ${currency}`;
}

function formatDateTime(iso) {
    if (!iso) return '';
    return new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

function formatTime(iso) {
    if (!iso) return '';
    return new Intl.DateTimeFormat(undefined, {
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

const statusColors = {
    'confirmed': 'bg-green-100 text-green-800',
    'pending_payment': 'bg-yellow-100 text-yellow-800',
    'cancelled': 'bg-red-100 text-red-800',
    'refunded': 'bg-purple-100 text-purple-800',
    'expired': 'bg-gray-100 text-gray-800',
};
</script>

<template>
    <ModernLayout title="Dashboard">
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
        </template>

        <!-- Admin Dashboard -->
        <div v-if="$page.props.isAdmin && stats">
            <!-- Quick Stats -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Today's Bookings</p>
                            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ stats.today_bookings }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Today's Revenue</p>
                            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ formatMoney(stats.today_revenue) }}</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">This Month</p>
                            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ stats.month_bookings }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ formatMoney(stats.month_revenue) }}</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Pending Payment</p>
                            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ stats.pending_bookings }}</p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-lg">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Lists -->
            <div class="grid gap-6 lg:grid-cols-3 mb-6">
                <!-- Quick Actions -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">Quick Actions</h2>
                    <div class="space-y-3">
                        <Link :href="route('admin.bookings.create')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                            <div class="p-2 bg-blue-100 rounded">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-900">New Booking</span>
                        </Link>
                        
                        <Link :href="route('admin.analytics.index')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                            <div class="p-2 bg-purple-100 rounded">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-900">View Analytics</span>
                        </Link>

                        <Link :href="route('admin.services.index')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                            <div class="p-2 bg-green-100 rounded">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-900">Manage Services</span>
                        </Link>

                        <Link :href="route('admin.staff.index')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                            <div class="p-2 bg-orange-100 rounded">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-900">Manage Staff</span>
                        </Link>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Active Services</span>
                            <span class="font-semibold text-gray-900">{{ stats.total_services }}</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-sm">
                            <span class="text-gray-600">Active Staff</span>
                            <span class="font-semibold text-gray-900">{{ stats.total_staff }}</span>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Today -->
                <div class="bg-white border border-gray-200 rounded-lg p-6 lg:col-span-2">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-900">Upcoming Today</h2>
                        <Link :href="route('admin.bookings.index')" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                            View all →
                        </Link>
                    </div>
                    
                    <div v-if="upcoming_today.length" class="space-y-3">
                        <div v-for="booking in upcoming_today" :key="booking.id" class="flex items-center gap-4 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                            <div class="flex-shrink-0 text-center">
                                <div class="text-lg font-semibold text-gray-900">{{ formatTime(booking.starts_at) }}</div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ booking.customer_name }}</p>
                                <p class="text-xs text-gray-600 truncate">{{ booking.service_name }}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="text-xs font-medium text-gray-700 bg-gray-100 px-2 py-1 rounded">{{ booking.staff_name }}</span>
                            </div>
                            <Link :href="route('admin.bookings.show', booking.id)" class="flex-shrink-0 text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500 text-center py-8">No upcoming bookings today</p>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-900">Recent Bookings</h2>
                    <Link :href="route('admin.bookings.index')" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                        View all →
                    </Link>
                </div>
                
                <div v-if="recent_bookings.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="booking in recent_bookings" :key="booking.id" class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-sm text-gray-900">#{{ booking.id }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">{{ booking.customer_name }}</td>
                                <td class="px-3 py-2 text-sm text-gray-700">{{ booking.service_name }}</td>
                                <td class="px-3 py-2 text-sm text-gray-700">{{ formatDateTime(booking.starts_at) }}</td>
                                <td class="px-3 py-2">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', statusColors[booking.status] || 'bg-gray-100 text-gray-800']">
                                        {{ booking.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <Link :href="route('admin.bookings.show', booking.id)" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                        View
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500 text-center py-8">No bookings yet</p>
            </div>
        </div>

        <!-- Customer Dashboard -->
        <div v-else>
            <div class="grid gap-6 md:grid-cols-2 mb-6">
                <!-- Welcome Card -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                    <h2 class="text-2xl font-bold mb-2">Welcome back!</h2>
                    <p class="text-blue-100 mb-4">Ready to book your next appointment?</p>
                    <Link :href="route('checkout')" class="inline-block bg-white text-blue-600 px-4 py-2 rounded-lg font-medium hover:bg-blue-50 transition-colors">
                        Book Now
                    </Link>
                </div>

                <!-- Stats Card -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">Your Bookings</h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Total Bookings</span>
                            <span class="text-2xl font-bold text-gray-900">{{ total_bookings }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Upcoming</span>
                            <span class="text-2xl font-bold text-blue-600">{{ upcoming_bookings.length }}</span>
                        </div>
                    </div>
                    <Link :href="route('my-bookings')" class="mt-4 block text-center text-sm font-medium text-blue-600 hover:text-blue-700">
                        View All Bookings →
                    </Link>
                </div>
            </div>

            <!-- Upcoming Appointments -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-900">Upcoming Appointments</h2>
                    <Link :href="route('my-bookings')" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                        View all →
                    </Link>
                </div>
                
                <div v-if="upcoming_bookings.length" class="space-y-3">
                    <div v-for="booking in upcoming_bookings" :key="booking.id" class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-blue-300 transition-colors">
                        <div class="flex-shrink-0">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">{{ booking.service_name }}</p>
                            <p class="text-sm text-gray-600">with {{ booking.staff_name }}</p>
                            <p class="text-sm text-gray-500 mt-1">{{ formatDateTime(booking.starts_at) }}</p>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="mt-4 text-sm text-gray-600">No upcoming appointments</p>
                    <Link :href="route('checkout')" class="mt-4 inline-block bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        Book Your First Appointment
                    </Link>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h2 class="text-sm font-semibold text-gray-900">Account</h2>
                    <p class="mt-2 text-sm text-gray-600">Update your profile and security settings.</p>
                    <div class="mt-4">
                        <Link :href="route('profile.show')" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                            Open profile →
                        </Link>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h2 class="text-sm font-semibold text-gray-900">Book Service</h2>
                    <p class="mt-2 text-sm text-gray-600">Browse services and make a booking.</p>
                    <div class="mt-4">
                        <Link :href="route('checkout')" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                            Book now →
                        </Link>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h2 class="text-sm font-semibold text-gray-900">My Bookings</h2>
                    <p class="mt-2 text-sm text-gray-600">View and manage your bookings.</p>
                    <div class="mt-4">
                        <Link :href="route('my-bookings')" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                            View bookings →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </ModernLayout>
</template>
