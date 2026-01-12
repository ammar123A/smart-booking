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
    <div class="min-h-screen bg-gray-50 flex">
        <Head :title="title" />

        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 w-64 bg-gradient-to-b from-gray-800 to-gray-900 shadow-xl flex flex-col">
            <!-- Logo/Brand -->
            <div class="h-16 flex items-center px-6 border-b border-white/10">
                <Link :href="route('dashboard')" class="flex items-center gap-3 group">
                    <ApplicationMark class="block h-9 w-auto filter brightness-0 invert" />
                    <span class="text-xl font-bold text-white group-hover:text-gray-300 transition-colors">Smart Booking</span>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                <div class="mb-6">
                    <p class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider mb-2">Main</p>
                    <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </NavLink>

                    <NavLink :href="route('checkout')" :active="route().current('checkout')">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Book Service
                    </NavLink>

                    <NavLink :href="route('my-bookings')" :active="route().current('my-bookings')">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        My Bookings
                    </NavLink>
                </div>

                <div v-if="$page.props.isAdmin">
                    <p class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider mb-2">Admin</p>
                    <NavLink :href="route('admin.bookings.index')" :active="route().current('admin.bookings.*')">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Bookings
                    </NavLink>

                    <NavLink :href="route('admin.analytics.index')" :active="route().current('admin.analytics.*')">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Analytics
                    </NavLink>

                    <NavLink :href="route('admin.services.index')" :active="route().current('admin.services.*')">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        Services
                    </NavLink>

                    <NavLink :href="route('admin.staff.index')" :active="route().current('admin.staff.*')">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Staff
                    </NavLink>
                </div>
            </nav>

            <!-- User Profile -->
            <div class="border-t border-white/10 p-4">
                <Dropdown align="left" width="48" direction="up">
                    <template #trigger>
                        <button type="button" class="w-full flex items-center gap-3 px-3 py-3 rounded-lg bg-white/10 hover:bg-white/20 transition-all">
                            <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-semibold text-white">{{ $page.props.auth.user.name.charAt(0).toUpperCase() }}</span>
                            </div>
                            <div class="flex-1 text-left overflow-hidden">
                                <p class="text-sm font-medium text-white truncate">{{ $page.props.auth.user.name }}</p>
                                <p class="text-xs text-white/60 truncate">{{ $page.props.auth.user.email }}</p>
                            </div>
                            <svg class="w-5 h-5 text-white/70 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                            </svg>
                        </button>
                    </template>

                    <template #content>
                        <DropdownLink :href="route('profile.show')">
                            <svg class="inline-block w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile Settings
                        </DropdownLink>

                        <div class="border-t border-gray-200" />

                        <form @submit.prevent="logout">
                            <DropdownLink as="button">
                                <svg class="inline-block w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Log Out
                            </DropdownLink>
                        </form>
                    </template>
                </Dropdown>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 ml-64">
            <main class="py-8">
                <div class="max-w-7xl mx-auto px-6 lg:px-8">
                    <div v-if="$slots.header" class="mb-6">
                        <slot name="header" />
                    </div>

                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
