<script setup>
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <PublicLayout title="Smart Booking">
        <section class="py-16 sm:py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-gray-900">
                            Smart Booking & Payments
                        </h1>
                        <p class="mt-4 text-base text-gray-600">
                            A service booking system with real-time availability, anti-double-booking, and Stripe payment processing.
                        </p>

                        <ul class="mt-6 space-y-3 text-sm text-gray-700">
                            <li class="flex gap-3">
                                <span class="mt-1 size-2 rounded-full bg-gray-900" />
                                <span>Auto-assign available staff for each slot</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="mt-1 size-2 rounded-full bg-gray-900" />
                                <span>DB-enforced protection against double booking</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="mt-1 size-2 rounded-full bg-gray-900" />
                                <span>Pending bookings expire after 10 minutes if unpaid</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="mt-1 size-2 rounded-full bg-gray-900" />
                                <span>Webhook-style callback updates payments and confirms bookings</span>
                            </li>
                        </ul>

                        <div class="mt-8 flex items-center gap-3">
                            <Link
                                v-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="inline-flex items-center px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800"
                            >
                                Go to dashboard
                            </Link>

                            <template v-else>
                                <Link
                                    :href="route('login')"
                                    class="inline-flex items-center px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800"
                                >
                                    Log in
                                </Link>
                                <Link
                                    :href="route('register')"
                                    class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Create account
                                </Link>
                            </template>
                        </div>

                        <p class="mt-6 text-xs text-gray-500">
                            Laravel v{{ laravelVersion }} · PHP v{{ phpVersion }}
                        </p>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h2 class="text-sm font-semibold text-gray-900">Admin</h2>
                        <p class="mt-2 text-sm text-gray-600">Manage services, pricing, staff, and schedules.</p>

                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div class="border border-gray-200 rounded-md p-4">
                                <div class="font-medium text-gray-900">Services</div>
                                <div class="mt-1 text-gray-600">Create services and prices</div>
                            </div>
                            <div class="border border-gray-200 rounded-md p-4">
                                <div class="font-medium text-gray-900">Staff</div>
                                <div class="mt-1 text-gray-600">Set schedules and assignment</div>
                            </div>
                        </div>

                        <div v-if="$page.props.auth.user" class="mt-6">
                            <Link :href="route('admin.services.index')" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                                Open admin →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
