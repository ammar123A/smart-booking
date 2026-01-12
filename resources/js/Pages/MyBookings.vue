<script setup>
import axios from 'axios';
import { ref } from 'vue';
import ModernLayout from '@/Layouts/ModernLayout.vue';
import { Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    bookings: {
        type: Array,
        required: true,
    },
});

const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

const payingBookingId = ref(null);
const payError = ref(null);

function fmtDateTime(iso) {
    if (!iso) return '';
    const dt = new Date(iso);
    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: timezone,
    }).format(dt);
}

function fmtMoney(cents, currency) {
    const amount = (Number(cents || 0) / 100).toFixed(2);
    return `${amount} ${currency || ''}`.trim();
}

function statusBadgeClass(status) {
    switch (status) {
        case 'confirmed':
            return 'bg-green-50 text-green-700 border-green-200';
        case 'pending_payment':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'expired':
        case 'cancelled':
            return 'bg-gray-50 text-gray-700 border-gray-200';
        default:
            return 'bg-gray-50 text-gray-700 border-gray-200';
    }
}

async function payNow(bookingId) {
    payError.value = null;
    payingBookingId.value = bookingId;
    try {
        const payRes = await axios.post(route('payments.stripe.initiate', { booking: bookingId }));
        const paymentUrl = payRes.data?.data?.payment_url;

        if (!paymentUrl) {
            payError.value = 'Payment could not be initiated.';
            return;
        }

        window.location.href = paymentUrl;
    } catch (e) {
        payError.value = e?.response?.data?.message || 'Could not proceed to payment.';
    } finally {
        payingBookingId.value = null;
    }
}
</script>

<template>
    <ModernLayout title="My Bookings">
        <template #header>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">My bookings</h1>
                <p class="mt-1 text-sm text-gray-600">Your recent bookings and payment status.</p>
            </div>
        </template>

        <div class="space-y-3">
            <div v-if="payError" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ payError }}
            </div>

            <div v-if="bookings.length === 0" class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="text-sm text-gray-700">No bookings yet.</div>
                <div class="mt-3">
                    <Link :href="route('checkout')" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                        Book a service →
                    </Link>
                </div>
            </div>

            <div
                v-for="b in bookings"
                :key="b.id"
                class="bg-white border border-gray-200 rounded-lg p-5"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">
                            {{ b.service?.name || 'Service' }}
                            <span class="text-gray-400">·</span>
                            <span class="font-medium">{{ b.service_price?.name || 'Option' }}</span>
                        </div>
                        <div class="mt-1 text-sm text-gray-600">
                            {{ fmtDateTime(b.starts_at) }} – {{ fmtDateTime(b.ends_at) }}
                            <span class="text-gray-400">·</span>
                            Staff: {{ b.staff?.name || 'Auto-assigned' }}
                        </div>
                        <div class="mt-1 text-sm text-gray-600">
                            Total: {{ fmtMoney(b.total_amount, b.currency) }}
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium" :class="statusBadgeClass(b.status)">
                            {{ b.status }}
                        </span>
                    </div>
                </div>

                <div v-if="b.status === 'pending_payment'" class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-xs text-gray-500" v-if="b.expires_at">
                        Expires at {{ fmtDateTime(b.expires_at) }}
                    </div>
                    <PrimaryButton
                        :type="'button'"
                        :disabled="payingBookingId !== null"
                        @click="payNow(b.id)"
                    >
                        {{ payingBookingId === b.id ? 'Redirecting…' : 'Pay now' }}
                    </PrimaryButton>
                </div>

                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    <div class="text-sm text-gray-700">
                        <div class="text-xs font-medium text-gray-500">Payment</div>
                        <div v-if="b.latest_payment" class="mt-1">
                            <div class="text-sm text-gray-700">
                                {{ b.latest_payment.provider }} · {{ b.latest_payment.status }}
                            </div>
                            <div v-if="b.latest_payment.paid_at" class="text-xs text-gray-500">
                                Paid at {{ fmtDateTime(b.latest_payment.paid_at) }}
                            </div>
                        </div>
                        <div v-else class="mt-1 text-sm text-gray-600">No payment record.</div>

                        <div v-if="b.payments && b.payments.length > 0" class="mt-3 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ref</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Paid at</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="p in b.payments" :key="p.id">
                                        <td class="px-3 py-2 text-sm text-gray-900">{{ p.provider }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-700">{{ p.provider_ref }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900">{{ p.status }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-700">{{ p.paid_at ? fmtDateTime(p.paid_at) : '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="text-sm text-gray-700">
                        <div class="text-xs font-medium text-gray-500">Actions</div>
                        <div class="mt-2 flex flex-wrap gap-3">
                            <Link :href="route('bookings.show', { booking: b.id })" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                                View JSON →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ModernLayout>
</template>
