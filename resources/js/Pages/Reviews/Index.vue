<script setup>
import ModernLayout from '@/Layouts/ModernLayout.vue';
import StarRating from '@/Components/StarRating.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    reviews: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

function formatDateTime(iso) {
    if (!iso) return '';
    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

function formatDate(iso) {
    if (!iso) return '';
    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date(iso));
}
</script>

<template>
    <ModernLayout title="Reviews">
        <template #header>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Customer Reviews</h1>
                <p class="mt-1 text-sm text-gray-600">See what our customers are saying</p>
            </div>
        </template>

        <div class="bg-white border border-gray-200 rounded-lg">
            <!-- Filter Options (Future Enhancement) -->
            <div class="border-b border-gray-200 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm font-medium text-gray-700">Filters:</span>
                    <select class="rounded-md border-gray-300 text-sm">
                        <option value="">All Services</option>
                    </select>
                    <select class="rounded-md border-gray-300 text-sm">
                        <option value="">All Ratings</option>
                        <option value="5">5 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="2">2 Stars</option>
                        <option value="1">1 Star</option>
                    </select>
                </div>
            </div>

            <!-- Reviews List -->
            <div class="divide-y divide-gray-200">
                <div v-if="reviews.data.length === 0" class="p-8 text-center">
                    <div class="text-gray-500">No reviews yet</div>
                </div>

                <div v-for="review in reviews.data" :key="review.id" class="p-6">
                    <div class="flex items-start gap-4">
                        <!-- Customer Avatar -->
                        <div class="flex-shrink-0">
                            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-lg font-semibold text-blue-600">
                                    {{ review.customer_name.charAt(0).toUpperCase() }}
                                </span>
                            </div>
                        </div>

                        <!-- Review Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ review.customer_name }}</div>
                                    <div class="mt-1 flex items-center gap-2">
                                        <StarRating :rating="review.rating" size="sm" />
                                        <span class="text-xs text-gray-500">{{ formatDate(review.created_at) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2">
                                <div class="text-xs text-gray-600 mb-1">
                                    <span class="font-medium">{{ review.service_name }}</span>
                                    <span v-if="review.staff_name" class="text-gray-400"> · </span>
                                    <span v-if="review.staff_name">with {{ review.staff_name }}</span>
                                </div>
                                <p v-if="review.comment" class="text-sm text-gray-700 mt-2">{{ review.comment }}</p>
                                <p v-else class="text-sm text-gray-500 italic">No comment provided</p>
                            </div>

                            <div class="mt-3 text-xs text-gray-500">
                                Booking date: {{ formatDate(review.booking_date) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="reviews.links.length > 3" class="border-t border-gray-200 px-6 py-4">
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <Link
                        v-for="(link, index) in reviews.links"
                        :key="index"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-2 text-sm rounded-md',
                            link.active 
                                ? 'bg-blue-600 text-white font-medium' 
                                : link.url 
                                ? 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' 
                                : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                        ]"
                        :disabled="!link.url"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </ModernLayout>
</template>
