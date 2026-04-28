<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link :href="'/'">
        <svg class="size-16" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="0" y="0" width="400" height="400" rx="90" fill="#1E5BFF"/>
            <path d="M120 90h140a76 76 0 0 1 53 130 82 82 0 0 1-41 158H120Z" fill="#FFFFFF"/>
            <circle cx="240" cy="166" r="38" fill="#1E5BFF"/>
            <path d="M170 244h88a44 44 0 1 1 0 88h-88Z" fill="#1E5BFF"/>
            <path d="M192 290l22 22 52-52" stroke="#FFFFFF" stroke-width="20" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        </svg>
    </Link>
</template>
