<script setup>
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    laravelVersion: { type: String, required: true },
    phpVersion: { type: String, required: true },
});

const bookingSlots = [
    { initials: 'AH', name: 'Ahmad Hafiz', service: 'Haircut · 45 min', time: '9:00 AM', status: 'Confirmed', statusClass: 'bg-green-100 text-green-700' },
    { initials: 'SR', name: 'Siti Rahimah', service: 'Massage · 60 min', time: '10:30 AM', status: 'Confirmed', statusClass: 'bg-green-100 text-green-700' },
    { initials: 'LW', name: 'Lee Wei', service: 'Facial · 30 min', time: '2:00 PM', status: 'Pending', statusClass: 'bg-yellow-100 text-yellow-700' },
];

const features = [
    {
        title: 'Real-time Availability',
        description: 'Slots update instantly as bookings are made. Customers always see accurate availability with no manual refresh needed.',
        iconBg: 'bg-blue-50',
        iconColor: 'text-blue-600',
        iconPath: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
    {
        title: 'Zero Double-Bookings',
        description: 'Database-level locks prevent any two bookings from claiming the same slot, even under high concurrent load.',
        iconBg: 'bg-indigo-50',
        iconColor: 'text-indigo-600',
        iconPath: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    },
    {
        title: 'Auto-assign Staff',
        description: 'BookIt automatically picks the most suitable available staff for each slot based on schedules and service type.',
        iconBg: 'bg-violet-50',
        iconColor: 'text-violet-600',
        iconPath: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    },
    {
        title: 'Secure Payments',
        description: 'Powered by Stripe. Pending bookings expire after 10 minutes if unpaid, keeping your calendar clean.',
        iconBg: 'bg-emerald-50',
        iconColor: 'text-emerald-600',
        iconPath: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    },
    {
        title: 'Smart Notifications',
        description: 'Automated emails for confirmations, reminders, cancellations, and reschedules keep everyone informed.',
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-600',
        iconPath: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    },
    {
        title: 'Loyalty Rewards',
        description: 'Reward returning customers with points and tier-based perks that encourage repeat bookings.',
        iconBg: 'bg-rose-50',
        iconColor: 'text-rose-600',
        iconPath: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
    },
];
</script>

<template>
    <PublicLayout title="BookIt — Smart Booking & Payments">

        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-white">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50/60 via-white to-indigo-50/40 pointer-events-none" />
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
                <div class="grid lg:grid-cols-2 gap-16 items-center">

                    <!-- Left: Copy -->
                    <div>
                        <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1.5 rounded-full mb-6 border border-blue-100">
                            <span class="size-1.5 rounded-full bg-blue-500 inline-block"></span>
                            Real-time availability · Zero double-bookings
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight tracking-tight">
                            Smart Scheduling,
                            <span class="text-blue-600"> Simplified</span>
                        </h1>

                        <p class="mt-6 text-lg text-gray-600 leading-relaxed max-w-lg">
                            BookIt gives your business real-time booking management with auto-assigned staff, secure Stripe payments, and zero scheduling conflicts.
                        </p>

                        <div class="mt-8 flex items-center gap-3 flex-wrap">
                            <Link
                                v-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-colors shadow-sm shadow-blue-200"
                            >
                                Go to Dashboard
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </Link>
                            <template v-else>
                                <Link
                                    :href="route('register')"
                                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-colors shadow-sm shadow-blue-200"
                                >
                                    Get Started Free
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                </Link>
                                <Link
                                    :href="route('login')"
                                    class="inline-flex items-center px-6 py-3 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
                                >
                                    Log in
                                </Link>
                            </template>
                        </div>

                        <div class="mt-10 flex items-center gap-6 flex-wrap text-sm text-gray-500">
                            <div class="flex items-center gap-1.5">
                                <svg class="size-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                No double-bookings
                            </div>
                            <div class="flex items-center gap-1.5">
                                <svg class="size-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Stripe-secured payments
                            </div>
                            <div class="flex items-center gap-1.5">
                                <svg class="size-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Auto-assign staff
                            </div>
                        </div>
                    </div>

                    <!-- Right: UI Mockup -->
                    <div class="relative">
                        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl p-1 shadow-2xl shadow-blue-200">
                            <div class="bg-white rounded-[22px] p-6">
                                <!-- Header row -->
                                <div class="flex items-center justify-between mb-5">
                                    <div>
                                        <p class="text-xs font-medium text-gray-500">Today's bookings</p>
                                        <p class="text-3xl font-bold text-gray-900 mt-0.5">24</p>
                                    </div>
                                    <div class="size-11 rounded-xl bg-blue-100 flex items-center justify-center">
                                        <svg class="size-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                </div>

                                <!-- Booking rows -->
                                <div class="space-y-1">
                                    <div
                                        v-for="slot in bookingSlots"
                                        :key="slot.time"
                                        class="flex items-center justify-between py-2.5 px-3 rounded-xl hover:bg-gray-50 transition-colors"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="size-9 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center text-xs font-bold text-blue-700">
                                                {{ slot.initials }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800">{{ slot.name }}</p>
                                                <p class="text-xs text-gray-400">{{ slot.service }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-gray-500 mb-1">{{ slot.time }}</p>
                                            <span :class="slot.statusClass" class="inline-block text-xs px-2 py-0.5 rounded-full font-semibold">{{ slot.status }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Stats row -->
                                <div class="mt-5 grid grid-cols-3 gap-2.5">
                                    <div class="bg-gray-50 rounded-xl p-3 text-center">
                                        <p class="text-base font-bold text-gray-900">98%</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Fill rate</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-3 text-center">
                                        <p class="text-base font-bold text-gray-900">0</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Conflicts</p>
                                    </div>
                                    <div class="bg-blue-50 rounded-xl p-3 text-center">
                                        <p class="text-base font-bold text-blue-600">+12%</p>
                                        <p class="text-xs text-gray-500 mt-0.5">vs last week</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating notification badge -->
                        <div class="absolute -bottom-5 -left-6 bg-white rounded-2xl shadow-xl border border-gray-100 p-4 flex items-center gap-3 max-w-[220px]">
                            <div class="size-10 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
                                <svg class="size-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-900">Payment confirmed</p>
                                <p class="text-xs text-gray-500">Just now · RM 120.00</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Bar -->
        <section class="bg-white border-y border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-gray-900">10 min</p>
                        <p class="text-sm text-gray-500 mt-1">Booking expiry protection</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-gray-900">0</p>
                        <p class="text-sm text-gray-500 mt-1">Double-booking incidents</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-gray-900">100%</p>
                        <p class="text-sm text-gray-500 mt-1">Automated staff assignment</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-blue-600">Stripe</p>
                        <p class="text-sm text-gray-500 mt-1">Secure payment processing</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="bg-gray-50 py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-14">
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Everything you need to run your bookings</h2>
                    <p class="mt-4 text-gray-600 text-lg max-w-xl mx-auto">Powerful features built to handle the complexity, so you can focus on your customers.</p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:-translate-y-0.5 transition-all"
                    >
                        <div :class="feature.iconBg" class="size-12 rounded-xl flex items-center justify-center mb-4">
                            <svg class="size-6" :class="feature.iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="feature.iconPath"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900">{{ feature.title }}</h3>
                        <p class="mt-2 text-sm text-gray-600 leading-relaxed">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Banner -->
        <section class="bg-white py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl px-8 py-16 sm:px-16 text-center relative overflow-hidden">
                    <!-- Decorative circles -->
                    <div class="absolute top-0 right-0 size-72 rounded-full bg-white/5 -translate-y-1/3 translate-x-1/3 pointer-events-none" />
                    <div class="absolute bottom-0 left-0 size-56 rounded-full bg-white/5 translate-y-1/3 -translate-x-1/3 pointer-events-none" />

                    <h2 class="relative text-3xl sm:text-4xl font-bold text-white">Ready to manage your business smarter?</h2>
                    <p class="relative mt-4 text-blue-100 text-lg max-w-xl mx-auto">Set up services, assign staff, and start accepting bookings in minutes with BookIt.</p>

                    <div class="relative mt-8 flex items-center justify-center gap-4 flex-wrap">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl bg-white text-blue-600 font-semibold text-sm hover:bg-blue-50 transition-colors"
                        >
                            Open Dashboard →
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('register')"
                                class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl bg-white text-blue-600 font-semibold text-sm hover:bg-blue-50 transition-colors"
                            >
                                Create Free Account →
                            </Link>
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center px-8 py-3.5 rounded-xl border border-white/30 text-white font-semibold text-sm hover:bg-white/10 transition-colors"
                            >
                                Log in
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </section>

    </PublicLayout>
</template>
