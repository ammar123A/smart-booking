<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    title: String,
    subtitle: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-gray-900">
        <Head :title="title" />

        <div class="min-h-screen flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full max-w-md">
                <div class="mb-8">
                    <Link :href="route('dashboard')" class="inline-flex items-center gap-2">
                        <div class="size-10 rounded-xl bg-gray-900" />
                        <span class="text-sm font-semibold">Smart Booking</span>
                    </Link>

                    <h1 class="mt-6 text-2xl font-semibold tracking-tight">{{ title }}</h1>
                    <p v-if="subtitle" class="mt-2 text-sm text-gray-600">{{ subtitle }}</p>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <slot />
                </div>

                <div v-if="$slots.footer" class="mt-6 text-sm text-gray-600">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </div>
</template>
