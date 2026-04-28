<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    title: String,
});
</script>

<template>
    <div class="min-h-screen bg-white text-gray-900">
        <Head :title="title" />

        <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-sm border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="h-16 flex items-center justify-between">
                    <Link :href="$page.props.auth.user ? route('dashboard') : '/'" class="flex items-center gap-2.5">
                        <svg class="size-8" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="0" y="0" width="400" height="400" rx="90" fill="#1E5BFF"/>
                            <path d="M120 90h140a76 76 0 0 1 53 130 82 82 0 0 1-41 158H120Z" fill="#FFFFFF"/>
                            <circle cx="240" cy="166" r="38" fill="#1E5BFF"/>
                            <path d="M170 244h88a44 44 0 1 1 0 88h-88Z" fill="#1E5BFF"/>
                            <path d="M192 290l22 22 52-52" stroke="#FFFFFF" stroke-width="20" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                        <span class="text-base font-bold text-gray-900">BookIt</span>
                    </Link>

                    <nav class="flex items-center gap-3">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors px-3 py-2"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link :href="route('login')" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors px-3 py-2">
                                Log in
                            </Link>
                            <Link
                                :href="route('register')"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-colors"
                            >
                                Get Started
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <main>
            <slot />
        </main>

        <footer class="bg-gray-50 border-t border-gray-100 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2.5">
                        <svg class="size-7" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="0" y="0" width="400" height="400" rx="90" fill="#1E5BFF"/>
                            <path d="M120 90h140a76 76 0 0 1 53 130 82 82 0 0 1-41 158H120Z" fill="#FFFFFF"/>
                            <circle cx="240" cy="166" r="38" fill="#1E5BFF"/>
                            <path d="M170 244h88a44 44 0 1 1 0 88h-88Z" fill="#1E5BFF"/>
                            <path d="M192 290l22 22 52-52" stroke="#FFFFFF" stroke-width="20" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                        <span class="text-sm font-semibold text-gray-900">BookIt</span>
                    </div>
                    <p class="text-sm text-gray-500">© {{ new Date().getFullYear() }} BookIt. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
