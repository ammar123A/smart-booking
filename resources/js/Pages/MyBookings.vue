<script setup>
import axios from 'axios';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ModernLayout from '@/Layouts/ModernLayout.vue';
import { Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StarRating from '@/Components/StarRating.vue';

const props = defineProps({
    bookings: {
        type: Array,
        required: true,
    },
});

const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

const payingBookingId = ref(null);
const payError = ref(null);

// Review form state
const reviewingBookingId = ref(null);
const reviewForm = ref({
    rating: 0,
    comment: '',
});

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

function startReview(bookingId) {
    reviewingBookingId.value = bookingId;
    reviewForm.value = {
        rating: 0,
        comment: '',
    };
}

function cancelReview() {
    reviewingBookingId.value = null;
    reviewForm.value = {
        rating: 0,
        comment: '',
    };
}

function submitReview(bookingId) {
    if (reviewForm.value.rating === 0) {
        return;
    }

    router.post(route('reviews.store'), {
        booking_id: bookingId,
        rating: reviewForm.value.rating,
        comment: reviewForm.value.comment,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            cancelReview();
        },
    });
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
                                View Details →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Review Section -->
                <div v-if="b.review || b.can_review" class="mt-4 border-t border-gray-200 pt-4">
                    <!-- Existing Review -->
                    <div v-if="b.review" class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-gray-700">Your Review</div>
                            <StarRating :rating="b.review.rating" size="sm" />
                        </div>
                        <p v-if="b.review.comment" class="text-sm text-gray-600 mt-2">{{ b.review.comment }}</p>
                    </div>

                    <!-- Add Review Form -->
                    <div v-else-if="reviewingBookingId === b.id" class="bg-blue-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-900 mb-3">Rate your experience</div>
                        
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                            <StarRating 
                                v-model:rating="reviewForm.rating" 
                                :interactive="true" 
                                size="lg" 
                            />
                        </div>

                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Comment (optional)</label>
                            <textarea
                                v-model="reviewForm.comment"
                                rows="3"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="Share your experience..."
                            ></textarea>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="submitReview(b.id)"
                                :disabled="reviewForm.rating === 0"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Submit Review
                            </button>
                            <button
                                type="button"
                                @click="cancelReview"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>

                    <!-- Add Review Button -->
                    <div v-else>
                        <button
                            type="button"
                            @click="startReview(b.id)"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                            Write a Review
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </ModernLayout>
</template>
