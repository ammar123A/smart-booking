<script setup>
import axios from 'axios';
import { ref, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
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
const showPaymentSuccess = ref(false);

// Review form state
const reviewingBookingId = ref(null);
const reviewForm = ref({ rating: 0, comment: '' });

// Show success popup if redirected here after payment
onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('paid') === '1') {
        showPaymentSuccess.value = true;
        // Clean the URL without reloading
        window.history.replaceState({}, '', window.location.pathname);
    }
});

function fmtDate(iso) {
    if (!iso) return '';
    return new Intl.DateTimeFormat(undefined, {
        weekday: 'short', year: 'numeric', month: 'short', day: 'numeric',
        timeZone: timezone,
    }).format(new Date(iso));
}

function fmtTime(iso) {
    if (!iso) return '';
    return new Intl.DateTimeFormat(undefined, {
        hour: '2-digit', minute: '2-digit',
        timeZone: timezone,
    }).format(new Date(iso));
}

function fmtDateTime(iso) {
    if (!iso) return '';
    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric', month: 'short', day: '2-digit',
        hour: '2-digit', minute: '2-digit',
        timeZone: timezone,
    }).format(new Date(iso));
}

function fmtMoney(cents, currency) {
    const amount = (Number(cents || 0) / 100).toFixed(2);
    return `${amount} ${currency || ''}`.trim();
}

function statusBadgeClass(status) {
    switch (status) {
        case 'confirmed':   return 'bg-green-50 text-green-700 border-green-200';
        case 'pending_payment': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'expired':
        case 'cancelled':   return 'bg-gray-100 text-gray-600 border-gray-200';
        default:            return 'bg-gray-50 text-gray-700 border-gray-200';
    }
}

function statusLabel(status) {
    return status === 'pending_payment' ? 'Pending Payment' : status.charAt(0).toUpperCase() + status.slice(1);
}

async function payNow(bookingId) {
    payError.value = null;
    payingBookingId.value = bookingId;
    try {
        const payRes = await axios.post(route('payments.stripe.initiate', { booking: bookingId }));
        const paymentUrl = payRes.data?.data?.payment_url;
        if (!paymentUrl) { payError.value = 'Payment could not be initiated.'; return; }
        window.location.href = paymentUrl;
    } catch (e) {
        payError.value = e?.response?.data?.message || 'Could not proceed to payment.';
    } finally {
        payingBookingId.value = null;
    }
}

function startReview(bookingId) {
    reviewingBookingId.value = bookingId;
    reviewForm.value = { rating: 0, comment: '' };
}

function cancelReview() {
    reviewingBookingId.value = null;
    reviewForm.value = { rating: 0, comment: '' };
}

function submitReview(bookingId) {
    if (reviewForm.value.rating === 0) return;
    router.post(route('reviews.store'), {
        booking_id: bookingId,
        rating: reviewForm.value.rating,
        comment: reviewForm.value.comment,
    }, {
        preserveScroll: true,
        onSuccess: () => cancelReview(),
    });
}
</script>

<template>
    <ModernLayout title="My Bookings">
        <template #header>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">My Bookings</h1>
                <p class="mt-1 text-sm text-gray-500">Your upcoming and past appointments.</p>
            </div>
        </template>

        <!-- Payment Success Modal -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showPaymentSuccess" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                >
                    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-8 text-center">
                        <!-- Animated check icon -->
                        <div class="mx-auto size-20 rounded-full bg-green-100 flex items-center justify-center mb-5">
                            <svg class="size-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">Payment Successful!</h2>
                        <p class="mt-2 text-sm text-gray-500">Your booking has been confirmed. We'll see you at your appointment.</p>
                        <button
                            type="button"
                            @click="showPaymentSuccess = false"
                            class="mt-6 w-full inline-flex items-center justify-center px-6 py-3 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-colors"
                        >
                            View My Bookings
                        </button>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- Pay error banner -->
        <div v-if="payError" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 flex items-center gap-2">
            <svg class="size-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ payError }}
        </div>

        <!-- Empty state -->
        <div v-if="bookings.length === 0" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
            <div class="mx-auto size-14 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                <svg class="size-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <p class="text-sm font-semibold text-gray-900">No bookings yet</p>
            <p class="mt-1 text-sm text-gray-500">Ready to book your first appointment?</p>
            <Link :href="route('checkout')" class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-colors">
                Book a service →
            </Link>
        </div>

        <!-- Booking cards -->
        <div class="space-y-4">
            <div
                v-for="b in bookings"
                :key="b.id"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <!-- Card header: service + status badge -->
                <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4 border-b border-gray-50">
                    <div>
                        <p class="text-base font-semibold text-gray-900">{{ b.service?.name || 'Service' }}</p>
                        <p class="text-sm text-gray-500 mt-0.5">{{ b.service_price?.name || '' }}</p>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold flex-shrink-0 mt-0.5"
                        :class="statusBadgeClass(b.status)"
                    >
                        {{ statusLabel(b.status) }}
                    </span>
                </div>

                <!-- Main info grid -->
                <div class="px-6 py-4 grid sm:grid-cols-3 gap-4">
                    <!-- Appointment date & time -->
                    <div class="sm:col-span-2">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1.5">Appointment</p>
                        <div class="flex items-start gap-3">
                            <div class="size-9 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                                <svg class="size-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ fmtDate(b.starts_at) }}</p>
                                <p class="text-sm text-gray-600 mt-0.5">{{ fmtTime(b.starts_at) }} – {{ fmtTime(b.ends_at) }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">with {{ b.staff?.name || 'Auto-assigned' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Amount -->
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1.5">Amount</p>
                        <p class="text-lg font-bold text-gray-900">{{ fmtMoney(b.total_amount, b.currency) }}</p>
                    </div>
                </div>

                <!-- Payment info row -->
                <div v-if="b.latest_payment" class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center gap-x-6 gap-y-2">
                    <div class="flex items-center gap-2">
                        <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <span class="text-xs text-gray-500">Paid via</span>
                        <span class="text-xs font-semibold text-gray-700 capitalize">{{ b.latest_payment.provider }}</span>
                    </div>
                    <div v-if="b.latest_payment.paid_at" class="flex items-center gap-2">
                        <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-xs text-gray-500">Paid on</span>
                        <span class="text-xs font-semibold text-gray-700">{{ fmtDateTime(b.latest_payment.paid_at) }}</span>
                    </div>
                    <div class="ml-auto">
                        <span :class="b.latest_payment.status === 'paid' ? 'text-green-600' : 'text-amber-600'" class="text-xs font-semibold capitalize flex items-center gap-1">
                            <span v-if="b.latest_payment.status === 'paid'" class="size-1.5 rounded-full bg-green-500 inline-block"></span>
                            <span v-else class="size-1.5 rounded-full bg-amber-400 inline-block"></span>
                            {{ b.latest_payment.status }}
                        </span>
                    </div>
                </div>

                <!-- Pending payment action -->
                <div v-if="b.status === 'pending_payment'" class="px-6 py-4 border-t border-amber-100 bg-amber-50 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-amber-800">Payment required</p>
                        <p v-if="b.expires_at" class="text-xs text-amber-600 mt-0.5">Expires at {{ fmtDateTime(b.expires_at) }}</p>
                    </div>
                    <PrimaryButton
                        type="button"
                        :disabled="payingBookingId !== null"
                        @click="payNow(b.id)"
                        class="!bg-amber-500 hover:!bg-amber-600"
                    >
                        {{ payingBookingId === b.id ? 'Redirecting…' : 'Pay Now' }}
                    </PrimaryButton>
                </div>

                <!-- Footer: actions + review -->
                <div class="px-6 py-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                    <Link
                        :href="route('bookings.show', { booking: b.id })"
                        class="text-sm font-medium text-blue-600 hover:text-blue-700 flex items-center gap-1"
                    >
                        View Details
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </Link>

                    <!-- Review section -->
                    <div v-if="b.review" class="flex items-center gap-2">
                        <StarRating :rating="b.review.rating" size="sm" />
                        <span class="text-xs text-gray-500">Your review</span>
                    </div>
                    <button
                        v-else-if="b.can_review && reviewingBookingId !== b.id"
                        type="button"
                        @click="startReview(b.id)"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        <svg class="size-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.448a1 1 0 00-.364 1.118l1.287 3.957c.3.921-.755 1.688-1.54 1.118l-3.367-2.448a1 1 0 00-1.175 0l-3.368 2.448c-.783.57-1.838-.197-1.539-1.118l1.287-3.957a1 1 0 00-.364-1.118L2.063 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69L9.049 2.927z"/></svg>
                        Write a Review
                    </button>
                </div>

                <!-- Inline review form -->
                <div v-if="reviewingBookingId === b.id" class="px-6 pb-6 border-t border-blue-100 bg-blue-50">
                    <div class="pt-4">
                        <p class="text-sm font-semibold text-gray-900 mb-3">Rate your experience</p>
                        <div class="mb-3">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Rating</label>
                            <StarRating v-model:rating="reviewForm.rating" :interactive="true" size="lg" />
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Comment <span class="text-gray-400">(optional)</span></label>
                            <textarea
                                v-model="reviewForm.comment"
                                rows="3"
                                class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Share your experience..."
                            ></textarea>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="submitReview(b.id)"
                                :disabled="reviewForm.rating === 0"
                                class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                Submit Review
                            </button>
                            <button
                                type="button"
                                @click="cancelReview"
                                class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 bg-white hover:bg-gray-50 transition-colors"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ModernLayout>
</template>