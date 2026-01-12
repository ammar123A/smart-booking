<script setup>
import ModernLayout from '@/Layouts/ModernLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <ModernLayout title="Dashboard">
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
        </template>

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

            <div v-if="$page.props.isAdmin" class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-sm font-semibold text-gray-900">Services</h2>
                <p class="mt-2 text-sm text-gray-600">Manage services and pricing.</p>
                <div class="mt-4">
                    <Link :href="route('admin.services.index')" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                        Manage services →
                    </Link>
                </div>
            </div>

            <div v-if="$page.props.isAdmin" class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-sm font-semibold text-gray-900">Staff</h2>
                <p class="mt-2 text-sm text-gray-600">Set up staff and schedules.</p>
                <div class="mt-4">
                    <Link :href="route('admin.staff.index')" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                        Manage staff →
                    </Link>
                </div>
            </div>
        </div>
    </ModernLayout>
</template>
