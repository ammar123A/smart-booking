<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    title: String,
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-gray-900">
        <Head :title="title" />

        <header class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="h-16 flex items-center justify-between">
                    <Link :href="route('dashboard')" class="flex items-center gap-2">
                        <div class="size-8 rounded-lg bg-gray-900" />
                        <span class="text-sm font-semibold">Smart Booking</span>
                    </Link>

                    <nav class="flex items-center gap-2">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="text-sm font-medium text-gray-700 hover:text-gray-900"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link :href="route('login')" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                                Log in
                            </Link>
                            <Link
                                :href="route('register')"
                                class="inline-flex items-center px-3 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800"
                            >
                                Register
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <main>
            <slot />
        </main>

        <footer class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-sm text-gray-500">
                <span>© {{ new Date().getFullYear() }} Smart Booking</span>
            </div>
        </footer>
    </div>
</template>
