<script setup>
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import ModernLayout from '@/Layouts/ModernLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StarRating from '@/Components/StarRating.vue';

const props = defineProps({
    services: {
        type: Array,
        required: true,
    },
});

const timezone = ref(Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC');
const selectedServiceId = ref(null);
const selectedPriceId = ref(null);
const selectedDate = ref(todayYmd());

const slots = ref([]);
const loadingSlots = ref(false);
const selectedStartAt = ref(null);

const errorMessage = ref(null);
const processing = ref(false);

const selectedService = computed(() => props.services.find((s) => s.id === selectedServiceId.value) || null);
const selectedPrice = computed(() => {
    const service = selectedService.value;
    if (!service) return null;
    return service.prices.find((p) => p.id === selectedPriceId.value) || null;
});

watch([selectedServiceId], () => {
    selectedPriceId.value = null;
    slots.value = [];
    selectedStartAt.value = null;
    errorMessage.value = null;
});

watch([selectedPriceId, selectedDate, timezone], async () => {
    slots.value = [];
    selectedStartAt.value = null;
    errorMessage.value = null;

    if (!selectedService.value || !selectedPrice.value || !selectedDate.value) return;

    await loadSlots();
});

function todayYmd() {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function formatMoney(cents, currency) {
    const amount = (Number(cents || 0) / 100).toFixed(2);
    return `${amount} ${currency || ''}`.trim();
}

function formatTime(isoUtc) {
    const dt = new Date(isoUtc);
    return new Intl.DateTimeFormat(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: timezone.value,
    }).format(dt);
}

async function loadSlots() {
    loadingSlots.value = true;
    try {
        const res = await axios.get(route('services.availability', { service: selectedServiceId.value }), {
            params: {
                service_price_id: selectedPriceId.value,
                date: selectedDate.value,
                timezone: timezone.value,
            },
        });

        slots.value = Array.isArray(res.data?.slots) ? res.data.slots : [];
    } catch (e) {
        const msg = e?.response?.data?.message || 'Failed to load availability.';
        errorMessage.value = msg;
    } finally {
        loadingSlots.value = false;
    }
}

async function proceedToPayment() {
    errorMessage.value = null;

    if (!selectedPrice.value || !selectedStartAt.value) {
        errorMessage.value = 'Please select a time slot.';
        return;
    }

    processing.value = true;
    try {
        const bookingRes = await axios.post(route('bookings.store'), {
            service_price_id: selectedPriceId.value,
            starts_at: selectedStartAt.value,
            timezone: timezone.value,
        });

        const booking = bookingRes.data?.data;
        if (!booking?.id) {
            errorMessage.value = 'Booking could not be created.';
            return;
        }

        const payRes = await axios.post(route('payments.stripe.initiate', { booking: booking.id }));
        const paymentUrl = payRes.data?.data?.payment_url;

        if (!paymentUrl) {
            errorMessage.value = 'Payment could not be initiated.';
            return;
        }

        window.location.href = paymentUrl;
    } catch (e) {
        const msg = e?.response?.data?.message || 'Could not proceed to payment.';
        errorMessage.value = msg;
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <ModernLayout title="Checkout">
        <template #header>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Book a service</h1>
                <p class="mt-1 text-sm text-gray-600">Select a service, choose a time, then pay.</p>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-sm font-semibold text-gray-900">1) Choose a service</h2>

                <div class="mt-4 space-y-4">
                    <div
                        v-for="service in services"
                        :key="service.id"
                        class="rounded-lg border p-4"
                        :class="selectedServiceId === service.id ? 'border-gray-400 bg-gray-50' : 'border-gray-200 bg-white'"
                    >
                        <button
                            type="button"
                            class="w-full text-left"
                            @click="selectedServiceId = service.id"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-semibold text-gray-900">{{ service.name }}</div>
                                        <StarRating 
                                            v-if="service.average_rating" 
                                            :rating="service.average_rating" 
                                            size="sm" 
                                            :show-count="true"
                                            :count="service.total_reviews"
                                        />
                                    </div>
                                    <div v-if="service.description" class="mt-1 text-sm text-gray-600">{{ service.description }}</div>
                                </div>
                                <div class="text-xs text-gray-500">Select</div>
                            </div>
                        </button>

                        <div v-if="selectedServiceId === service.id" class="mt-4">
                            <div class="text-xs font-medium text-gray-600">Options</div>
                            <div class="mt-2 space-y-2">
                                <label
                                    v-for="price in service.prices"
                                    :key="price.id"
                                    class="flex items-start gap-3 rounded-md border border-gray-200 p-3 hover:bg-gray-50"
                                >
                                    <input
                                        type="radio"
                                        name="service_price"
                                        class="mt-1"
                                        :value="price.id"
                                        v-model="selectedPriceId"
                                    />
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <span class="text-sm font-medium text-gray-900">{{ price.name }}</span>
                                            <span class="text-xs text-gray-500">· {{ price.duration_min }} min</span>
                                            <span class="text-xs text-gray-500">· {{ formatMoney(price.amount, price.currency) }}</span>
                                        </div>
                                    </div>
                                </label>

                                <div v-if="service.prices.length === 0" class="text-sm text-gray-600">
                                    No active pricing available.
                                </div>
                            </div>
                        </div>

                        <!-- Recent Reviews Section -->
                        <div v-if="selectedServiceId === service.id && service.recent_reviews && service.recent_reviews.length > 0" class="mt-4 border-t border-gray-200 pt-4">
                            <div class="text-xs font-medium text-gray-600 mb-3">Recent Reviews</div>
                            <div class="space-y-3">
                                <div v-for="review in service.recent_reviews" :key="review.id" class="bg-gray-50 rounded-lg p-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <StarRating :rating="review.rating" size="sm" />
                                        <span class="text-xs text-gray-500">{{ new Date(review.created_at).toLocaleDateString() }}</span>
                                    </div>
                                    <p v-if="review.comment" class="mt-2 text-sm text-gray-700">{{ review.comment }}</p>
                                    <p class="mt-1 text-xs text-gray-500">— {{ review.customer_name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="services.length === 0" class="text-sm text-gray-600">
                        No services available.
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2" v-if="selectedPrice">
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Date</label>
                        <input
                            v-model="selectedDate"
                            type="date"
                            class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600">Timezone</label>
                        <input
                            v-model="timezone"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                        />
                    </div>
                </div>
            </section>

            <section class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-sm font-semibold text-gray-900">2) Choose a time</h2>

                <div v-if="errorMessage" class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ errorMessage }}
                </div>

                <div class="mt-4" v-if="!selectedPrice">
                    <div class="text-sm text-gray-600">Select a service option to see available times.</div>
                </div>

                <div class="mt-4" v-else>
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-sm text-gray-700">
                            <span class="font-medium text-gray-900">{{ selectedService?.name }}</span>
                            <span class="text-gray-500">·</span>
                            <span>{{ selectedPrice?.name }}</span>
                        </div>
                        <div class="text-xs text-gray-500" v-if="loadingSlots">Loading…</div>
                    </div>

                    <div class="mt-4">
                        <div v-if="!loadingSlots && slots.length === 0" class="text-sm text-gray-600">
                            No available slots for this date.
                        </div>

                        <div v-else class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            <button
                                v-for="slot in slots"
                                :key="slot.start_at"
                                type="button"
                                class="rounded-md border px-3 py-2 text-left text-sm"
                                :class="selectedStartAt === slot.start_at ? 'border-gray-800 bg-gray-800 text-white' : 'border-gray-200 bg-white text-gray-900 hover:bg-gray-50'"
                                @click="selectedStartAt = slot.start_at"
                            >
                                <div class="font-medium">{{ formatTime(slot.start_at) }}</div>
                                <div class="text-xs" :class="selectedStartAt === slot.start_at ? 'text-gray-200' : 'text-gray-500'">
                                    {{ slot.available_staff_count }} staff
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-between gap-3">
                        <div class="text-sm text-gray-600">
                            <span v-if="selectedStartAt">Selected: <span class="font-medium text-gray-900">{{ formatTime(selectedStartAt) }}</span></span>
                            <span v-else>Select a slot to continue.</span>
                        </div>

                        <PrimaryButton :type="'button'" :disabled="processing || !selectedStartAt" @click="proceedToPayment">
                            {{ processing ? 'Redirecting…' : 'Pay with Stripe' }}
                        </PrimaryButton>
                    </div>
                </div>
            </section>
        </div>
    </ModernLayout>
</template>
