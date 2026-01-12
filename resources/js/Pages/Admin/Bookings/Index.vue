<script setup>
import ModernLayout from '@/Layouts/ModernLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    bookings: {
        type: Array,
        required: true,
    },
});

const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

function fmtDateTime(iso) {
    if (!iso) return '';
    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: timezone,
    }).format(new Date(iso));
}

function fmtMoney(cents, currency) {
    const amount = (Number(cents || 0) / 100).toFixed(2);
    return `${amount} ${currency || ''}`.trim();
}

function badgeClass(status) {
    switch (status) {
        case 'confirmed':
            return 'bg-green-50 text-green-700 border-green-200';
        case 'pending_payment':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'expired':
        case 'cancelled':
            return 'bg-gray-50 text-gray-700 border-gray-200';
        case 'refunded':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        default:
            return 'bg-gray-50 text-gray-700 border-gray-200';
    }
}
</script>

<template>
    <ModernLayout title="Admin · Bookings">
        <template #header>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Admin · Bookings</h1>
                    <p class="mt-1 text-sm text-gray-600">Latest bookings and payment status.</p>
                </div>
                <Link :href="route('admin.bookings.create')" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                    Create booking →
                </Link>
            </div>
        </template>

        <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Staff</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Start</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="b in bookings" :key="b.id">
                        <td class="px-3 py-2 text-sm text-gray-900">#{{ b.id }}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium" :class="badgeClass(b.status)">
                                {{ b.status }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-sm text-gray-900">
                            <div class="font-medium">{{ b.customer?.name || '-' }}</div>
                            <div class="text-xs text-gray-500">{{ b.customer?.email || '' }}</div>
                        </td>
                        <td class="px-3 py-2 text-sm text-gray-900">
                            <div class="font-medium">{{ b.service?.name || '-' }}</div>
                            <div class="text-xs text-gray-500">{{ b.service_price?.name || '' }}</div>
                        </td>
                        <td class="px-3 py-2 text-sm text-gray-900">{{ b.staff?.name || '-' }}</td>
                        <td class="px-3 py-2 text-sm text-gray-900">{{ fmtDateTime(b.starts_at) }}</td>
                        <td class="px-3 py-2 text-sm text-gray-900">{{ fmtMoney(b.total_amount, b.currency) }}</td>
                        <td class="px-3 py-2 text-sm text-gray-900">
                            <span v-if="b.latest_payment">{{ b.latest_payment.provider }} · {{ b.latest_payment.status }}</span>
                            <span v-else class="text-gray-500">-</span>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <Link :href="route('admin.bookings.show', { booking: b.id })" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                                View →
                            </Link>
                        </td>
                    </tr>

                    <tr v-if="bookings.length === 0">
                        <td colspan="9" class="px-3 py-6 text-center text-sm text-gray-600">No bookings.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </ModernLayout>
</template>
