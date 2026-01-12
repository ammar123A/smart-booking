<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: String,
    active: Boolean,
});

const classes = computed(() => {
    return props.active
        ? 'flex items-center w-full px-3 py-3 text-sm font-medium text-white bg-white/20 rounded-lg transition-all'
        : 'flex items-center w-full px-3 py-3 text-sm font-medium text-white/90 hover:text-white hover:bg-white/10 rounded-lg transition-all';
});
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
