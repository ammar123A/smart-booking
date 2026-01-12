<script setup>
import ApiTokenManager from '@/Pages/API/Partials/ApiTokenManager.vue';
import ModernLayout from '@/Layouts/ModernLayout.vue';

defineProps({
    tokens: Array,
    availablePermissions: Array,
    defaultPermissions: Array,
});
</script>

<template>
    <ModernLayout title="API Tokens">
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900">API Tokens</h1>
        </template>

        <ApiTokenManager
            :tokens="tokens"
            :available-permissions="availablePermissions"
            :default-permissions="defaultPermissions"
        />
    </ModernLayout>
</template>
