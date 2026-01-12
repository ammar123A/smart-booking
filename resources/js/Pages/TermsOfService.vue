<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    terms: String,
});
</script>

<template>
    <PublicLayout title="Terms of Service">
        <div class="py-12">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white border border-gray-200 rounded-lg p-6 prose max-w-none" v-html="terms" />
            </div>
        </div>
    </PublicLayout>
</template>
