<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';

defineProps({
    title: String,
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <Head :title="title" />

        <header class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="h-16 flex items-center justify-between">
                    <div class="flex items-center gap-6">
                        <Link :href="route('dashboard')" class="flex items-center gap-2">
                            <ApplicationMark class="block h-8 w-auto" />
                            <span class="text-sm font-semibold text-gray-900">Smart Booking</span>
                        </Link>

                        <nav class="hidden md:flex items-center gap-1">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                Dashboard
                            </NavLink>

                            <NavLink :href="route('checkout')" :active="route().current('checkout')">
                                Book
                            </NavLink>

                            <NavLink :href="route('my-bookings')" :active="route().current('my-bookings')">
                                My bookings
                            </NavLink>

                            <NavLink
                                v-if="$page.props.isAdmin"
                                :href="route('admin.bookings.index')"
                                :active="route().current('admin.bookings.*')"
                            >
                                Bookings
                            </NavLink>

                            <NavLink
                                v-if="$page.props.isAdmin"
                                :href="route('admin.services.index')"
                                :active="route().current('admin.services.*')"
                            >
                                Services
                            </NavLink>

                            <NavLink
                                v-if="$page.props.isAdmin"
                                :href="route('admin.staff.index')"
                                :active="route().current('admin.staff.*')"
                            >
                                Staff
                            </NavLink>
                        </nav>
                    </div>

                    <div class="flex items-center gap-2">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-md border border-gray-200 bg-white text-sm text-gray-700 hover:bg-gray-50"
                                >
                                    <span class="max-w-[160px] truncate">{{ $page.props.auth.user.name }}</span>

                                    <svg class="size-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    Account
                                </div>

                                <DropdownLink :href="route('profile.show')">
                                    Profile
                                </DropdownLink>

                                <div class="border-t border-gray-200" />

                                <form @submit.prevent="logout">
                                    <DropdownLink as="button">
                                        Log Out
                                    </DropdownLink>
                                </form>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </div>
        </header>

        <main class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div v-if="$slots.header" class="mb-6">
                    <div class="flex items-center justify-between gap-4">
                        <slot name="header" />
                    </div>
                </div>

                <slot />
            </div>
        </main>
    </div>
</template>
