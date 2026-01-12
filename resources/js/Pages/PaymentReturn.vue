<script setup>
import ModernLayout from '@/Layouts/ModernLayout.vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    session_id: {
        type: [String, null],
        default: null,
    },
    payment_id: {
        type: [Number, null],
        default: null,
    },
    booking_id: {
        type: [Number, null],
        default: null,
    },
    payment_status: {
        type: [String, null],
        default: null,
    },
    message: {
        type: [String, null],
        default: null,
    },
});

const liveStatus = ref(props.payment_status);
const liveBookingStatus = ref(null);
const pollError = ref(null);
const polling = ref(false);
let timer = null;

const isFinal = computed(() => liveStatus.value === 'paid' || liveStatus.value === 'failed');

function headline() {
    if (liveStatus.value === 'paid') return 'Payment received';
    if (liveStatus.value === 'failed') return 'Payment failed';
    if (liveStatus.value === 'cancelled') return 'Payment cancelled';
    if (liveStatus.value) return 'Payment status updated';

    return 'Payment return';
}

function body() {
    if (liveStatus.value === 'paid') {
        if (liveBookingStatus.value === 'confirmed') return 'Payment confirmed and booking is confirmed.';
        return 'Your booking will be confirmed shortly.';
    }
    if (liveStatus.value === 'failed') return 'No charge was completed. You can try booking again.';
    if (liveStatus.value === 'cancelled') return 'Payment was cancelled. You can try again.';
    if (liveStatus.value) return `Current status: ${liveStatus.value}.`;

    return 'If you just paid, we may still be waiting for confirmation. Check My bookings for the latest status.';
}

async function fetchStatusOnce() {
    if (!props.session_id) return;

    polling.value = true;
    pollError.value = null;

    try {
        const res = await axios.get(route('payments.stripe.status'), {
            params: { session_id: props.session_id },
        });

        liveStatus.value = res.data?.data?.payment_status ?? liveStatus.value;
        liveBookingStatus.value = res.data?.data?.booking_status ?? liveBookingStatus.value;
    } catch (e) {
        pollError.value = 'Unable to refresh payment status.';
    } finally {
        polling.value = false;
    }
}

onMounted(() => {
    // Poll for a short period after return to allow webhook + finalize job to complete.
    if (!props.session_id) return;
    if (isFinal.value) return;

    let attempts = 0;
    timer = window.setInterval(async () => {
        attempts += 1;
        await fetchStatusOnce();

        if (isFinal.value || attempts >= 12) {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }
    }, 2000);

    // Kick one immediate fetch.
    fetchStatusOnce();
});

onBeforeUnmount(() => {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
});
</script>

<template>
    <ModernLayout title="Payment">
        <template #header>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ headline() }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ body() }}</p>
            </div>
        </template>

        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <div class="space-y-2 text-sm text-gray-700">
                <div v-if="order_id"><span class="text-gray-500">Order ID:</span> {{ order_id }}</div>
                <div v-if="booking_id"><span class="text-gray-500">Booking ID:</span> {{ booking_id }}</div>
                <div v-if="liveStatus"><span class="text-gray-500">Payment status:</span> {{ liveStatus }}</div>
                <div v-if="liveBookingStatus"><span class="text-gray-500">Booking status:</span> {{ liveBookingStatus }}</div>
                <div v-if="status_id"><span class="text-gray-500">Gateway status_id:</span> {{ status_id }}</div>
                <div v-if="transaction_id"><span class="text-gray-500">Transaction ID:</span> {{ transaction_id }}</div>
                <div v-if="message"><span class="text-gray-500">Message:</span> {{ message }}</div>
                <div v-if="pollError" class="text-sm text-gray-600">{{ pollError }}</div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <button
                    v-if="order_id"
                    type="button"
                    class="text-sm font-medium text-gray-700 hover:text-gray-900"
                    :disabled="polling"
                    @click="fetchStatusOnce"
                >
                    {{ polling ? 'Refreshing…' : 'Refresh status' }}
                </button>
                <Link :href="route('my-bookings')" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                    Go to My bookings →
                </Link>
                <Link :href="route('checkout')" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                    Book another service →
                </Link>
            </div>
        </div>
    </ModernLayout>
</template>
