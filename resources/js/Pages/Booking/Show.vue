<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ModernLayout from '@/Layouts/ModernLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StarRating from '@/Components/StarRating.vue';
import axios from 'axios';

const props = defineProps({
    booking: {
        type: Object,
        required: true,
    },
});

const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
const paying = ref(false);
const payError = ref(null);

const reviewingMode = ref(false);
const reviewForm = ref({ rating: 0, comment: '' });

function fmtDateTime(iso) {
    if (!iso) return '—';
    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric', month: 'short', day: '2-digit',
        hour: '2-digit', minute: '2-digit', timeZone: timezone,
    }).format(new Date(iso));
}

function fmtMoney(cents, currency) {
    return `${(Number(cents || 0) / 100).toFixed(2)} ${currency || ''}`.trim();
}

const statusColors = {
    confirmed:       'bg-green-50 text-green-700 border-green-200',
    pending_payment: 'bg-amber-50 text-amber-700 border-amber-200',
    expired:         'bg-gray-50 text-gray-500 border-gray-200',
    cancelled:       'bg-gray-50 text-gray-500 border-gray-200',
    refunded:        'bg-blue-50 text-blue-600 border-blue-200',
};

const paymentStatusColors = {
    paid:      'text-green-600',
    initiated: 'text-amber-600',
    pending:   'text-amber-600',
    failed:    'text-red-600',
    refunded:  'text-blue-600',
};

async function payNow() {
    payError.value = null;
    paying.value = true;
    try {
        const res = await axios.post(route('payments.stripe.initiate', { booking: props.booking.id }));
        const url = res.data?.data?.payment_url;
        if (!url) { payError.value = 'Payment could not be initiated.'; return; }
        window.location.href = url;
    } catch (e) {
        payError.value = e?.response?.data?.message || 'Could not proceed to payment.';
    } finally {
        paying.value = false;
    }
}

function submitReview() {
    if (reviewForm.value.rating === 0) return;
    router.post(route('reviews.store'), {
        booking_id: props.booking.id,
        rating: reviewForm.value.rating,
        comment: reviewForm.value.comment,
    }, {
        preserveScroll: true,
        onSuccess: () => { reviewingMode.value = false; },
    });
}
</script>

<template>
    <ModernLayout title="Booking Details">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('my-bookings')" class="text-sm text-gray-500 hover:text-gray-700">
                    ← My Bookings
                </Link>
                <span class="text-gray-300">/</span>
                <h1 class="text-xl font-semibold text-gray-900">Booking #{{ booking.id }}</h1>
            </div>
        </template>

        <div class="max-w-2xl space-y-4">

            <!-- Error -->
            <div v-if="payError" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ payError }}
            </div>

            <!-- Main card -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 space-y-5">

                <!-- Header row -->
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="text-lg font-semibold text-gray-900">
                            {{ booking.service?.name || 'Service' }}
                            <span class="text-gray-400 font-normal">·</span>
                            {{ booking.service_price?.name || 'Option' }}
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            {{ booking.service_price?.duration_min }} min session
                        </div>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium"
                        :class="statusColors[booking.status] || 'bg-gray-50 text-gray-700 border-gray-200'"
                    >
                        {{ booking.status }}
                    </span>
                </div>

                <!-- Details grid -->
                <div class="grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <div class="text-xs font-medium text-gray-500 mb-1">Date & Time</div>
                        <div class="text-gray-900">{{ fmtDateTime(booking.starts_at) }}</div>
                        <div class="text-gray-500">to {{ fmtDateTime(booking.ends_at) }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 mb-1">Staff</div>
                        <div class="text-gray-900">{{ booking.staff?.name || '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 mb-1">Total</div>
                        <div class="text-gray-900 font-medium">{{ fmtMoney(booking.total_amount, booking.currency) }}</div>
                    </div>
                    <div v-if="booking.expires_at && booking.status === 'pending_payment'">
                        <div class="text-xs font-medium text-gray-500 mb-1">Expires</div>
                        <div class="text-amber-600 text-sm">{{ fmtDateTime(booking.expires_at) }}</div>
                    </div>
                </div>

                <!-- Pay now -->
                <div v-if="booking.status === 'pending_payment'" class="pt-2 border-t border-gray-100">
                    <PrimaryButton :disabled="paying" @click="payNow">
                        {{ paying ? 'Redirecting…' : 'Pay Now' }}
                    </PrimaryButton>
                </div>
            </div>

            <!-- Payments card -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="text-sm font-medium text-gray-700 mb-3">Payment History</div>
                <div v-if="booking.payments && booking.payments.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Paid At</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="p in booking.payments" :key="p.id">
                                <td class="px-3 py-2 capitalize text-gray-900">{{ p.provider }}</td>
                                <td class="px-3 py-2 text-gray-500 font-mono text-xs">{{ p.provider_ref }}</td>
                                <td class="px-3 py-2 font-medium" :class="paymentStatusColors[p.status] || 'text-gray-700'">{{ p.status }}</td>
                                <td class="px-3 py-2 text-gray-900">{{ fmtMoney(p.amount, p.currency) }}</td>
                                <td class="px-3 py-2 text-gray-500">{{ p.paid_at ? fmtDateTime(p.paid_at) : '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-sm text-gray-500">No payment records.</div>
            </div>

            <!-- Review card -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="text-sm font-medium text-gray-700 mb-3">Review</div>

                <!-- Existing review -->
                <div v-if="booking.review" class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <StarRating :rating="booking.review.rating" size="sm" />
                        <span class="text-xs text-gray-400">{{ booking.review.rating }}/5</span>
                    </div>
                    <p v-if="booking.review.comment" class="text-sm text-gray-700">{{ booking.review.comment }}</p>
                </div>

                <!-- Write review form -->
                <div v-else-if="booking.can_review">
                    <div v-if="!reviewingMode">
                        <button
                            type="button"
                            @click="reviewingMode = true"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            ✦ Write a Review
                        </button>
                    </div>
                    <div v-else class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                            <StarRating v-model:rating="reviewForm.rating" :interactive="true" size="lg" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Comment (optional)</label>
                            <textarea
                                v-model="reviewForm.comment"
                                rows="3"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="Share your experience…"
                            />
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="submitReview"
                                :disabled="reviewForm.rating === 0"
                                class="px-4 py-2 text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >Submit</button>
                            <button
                                type="button"
                                @click="reviewingMode = false"
                                class="px-4 py-2 text-sm font-medium rounded-md text-gray-700 border border-gray-300 bg-white hover:bg-gray-50"
                            >Cancel</button>
                        </div>
                    </div>
                </div>

                <div v-else class="text-sm text-gray-500">
                    {{ booking.status === 'confirmed' ? 'Review available after the session ends.' : 'No review available for this booking.' }}
                </div>
            </div>

        </div>
    </ModernLayout>
</template>
