<script setup>
import ModernLayout from '@/Layouts/ModernLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    user: Object,
    points_history: Array,
    available_rewards: Array,
    all_tiers: Array,
    my_vouchers: Array,
});

const page = usePage();
const activeTab = ref('overview');
const flashMessage = ref('');
const flashType = ref('success');
const redeemingId = ref(null);

watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        flashMessage.value = flash.success;
        flashType.value = 'success';
        setTimeout(() => { flashMessage.value = ''; }, 6000);
    } else if (flash?.error) {
        flashMessage.value = flash.error;
        flashType.value = 'error';
        setTimeout(() => { flashMessage.value = ''; }, 6000);
    }
}, { immediate: true, deep: true });

const formatPoints = (points) => {
    return points >= 0 ? `+${points}` : points.toString();
};

const formatDiscount = (reward) => {
    if (reward.type === 'discount_percentage') {
        return `${reward.value / 100}% Off`;
    } else if (reward.type === 'discount_fixed') {
        return `RM ${(reward.value / 100).toFixed(2)} Off`;
    }
    return reward.name;
};

const redeemReward = (reward) => {
    if (!confirm(`Redeem "${reward.name}" for ${reward.points_cost} points?`)) {
        return;
    }

    redeemingId.value = reward.id;
    router.post(route('loyalty.redeem', reward.id), {}, {
        preserveScroll: true,
        onFinish: () => { redeemingId.value = null; },
    });
};

const getTypeColor = (type) => {
    const colors = {
        earned: 'text-green-600 bg-green-100',
        redeemed: 'text-blue-600 bg-blue-100',
        expired: 'text-gray-600 bg-gray-100',
        bonus: 'text-purple-600 bg-purple-100',
        adjustment: 'text-orange-600 bg-orange-100',
    };
    return colors[type] || 'text-gray-600 bg-gray-100';
};

const getProgressToNextTier = () => {
    if (!props.all_tiers || props.all_tiers.length === 0) return 0;
    
    const currentPoints = props.user.loyalty_points;
    const currentTier = props.all_tiers.find(t => t.is_current);
    const nextTier = props.all_tiers.find(t => !t.is_unlocked);
    
    if (!nextTier) return 100; // Max tier reached
    
    const currentMin = currentTier?.min_points || 0;
    const nextMin = nextTier.min_points;
    const progress = ((currentPoints - currentMin) / (nextMin - currentMin)) * 100;
    
    return Math.min(100, Math.max(0, progress));
};
</script>

<template>
    <ModernLayout title="Loyalty Program">
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900">Loyalty Program</h1>
        </template>

        <!-- Flash Message -->
        <div v-if="flashMessage" class="mb-4 rounded-lg p-4 text-sm font-medium"
            :class="flashType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'"
        >
            {{ flashMessage }}
        </div>

        <div class="space-y-6">
            <!-- Points & Tier Overview -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Points Balance Card -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold">Your Points</h2>
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="text-4xl font-bold mb-2">{{ user.loyalty_points }}</div>
                    <p class="text-blue-100 text-sm">Total loyalty points</p>
                </div>

                <!-- Current Tier Card -->
                <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Current Tier</h2>
                    <div v-if="user.tier" class="flex items-center gap-4">
                        <div class="text-5xl">{{ user.tier.icon }}</div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold" :style="{ color: user.tier.color }">
                                {{ user.tier.name }}
                            </h3>
                            <div class="mt-2 space-y-1 text-sm text-gray-600">
                                <p>💰 {{ user.tier.discount_percentage }}% discount on all bookings</p>
                                <p>⭐ {{ user.tier.points_multiplier }}x points multiplier</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-4 text-gray-500">
                        No tier yet - start booking to earn points!
                    </div>

                    <!-- Progress to Next Tier -->
                    <div v-if="all_tiers.find(t => !t.is_unlocked)" class="mt-6">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-gray-600">Progress to {{ all_tiers.find(t => !t.is_unlocked).name }}</span>
                            <span class="font-semibold text-gray-900">
                                {{ user.loyalty_points }} / {{ all_tiers.find(t => !t.is_unlocked).min_points }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all" :style="{ width: getProgressToNextTier() + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button
                        @click="activeTab = 'overview'"
                        :class="[
                            activeTab === 'overview'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Overview
                    </button>
                    <button
                        @click="activeTab = 'rewards'"
                        :class="[
                            activeTab === 'rewards'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Rewards Catalog
                    </button>
                    <button
                        @click="activeTab = 'history'"
                        :class="[
                            activeTab === 'history'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Points History
                    </button>
                    <button
                        @click="activeTab = 'tiers'"
                        :class="[
                            activeTab === 'tiers'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Tier Benefits
                    </button>
                    <button
                        @click="activeTab = 'vouchers'"
                        :class="[
                            activeTab === 'vouchers'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        My Vouchers
                        <span v-if="my_vouchers.length > 0" class="ml-1.5 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold rounded-full bg-blue-100 text-blue-700">{{ my_vouchers.length }}</span>
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div>
                <!-- Overview Tab -->
                <div v-if="activeTab === 'overview'" class="space-y-6">
                    <!-- Available Rewards -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Rewards You Can Redeem</h3>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="reward in available_rewards.filter(r => r.can_redeem).slice(0, 6)"
                                :key="reward.id"
                                class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                            >
                                <div class="flex items-start justify-between mb-2">
                                    <h4 class="font-semibold text-gray-900">{{ reward.name }}</h4>
                                    <span class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-full font-medium">
                                        {{ reward.points_cost }} pts
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-3">{{ reward.description }}</p>
                                <button
                                    @click="redeemReward(reward)"
                                    :disabled="!reward.can_redeem || redeemingId === reward.id"
                                    class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
                                >
                                    {{ redeemingId === reward.id ? 'Redeeming…' : 'Redeem Now' }}
                                </button>
                            </div>
                        </div>
                        <div v-if="available_rewards.filter(r => r.can_redeem).length === 0" class="text-center py-8 text-gray-500">
                            <p>No rewards available yet. Keep earning points!</p>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
                        <div class="space-y-3">
                            <div
                                v-for="point in points_history.slice(0, 5)"
                                :key="point.id"
                                class="flex items-center justify-between py-2"
                            >
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ point.description }}</p>
                                    <p class="text-xs text-gray-500">{{ point.created_at }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span
                                        :class="[
                                            point.points >= 0 ? 'text-green-600' : 'text-red-600',
                                            'font-semibold'
                                        ]"
                                    >
                                        {{ formatPoints(point.points) }}
                                    </span>
                                    <span :class="['text-xs px-2 py-1 rounded-full font-medium', getTypeColor(point.type)]">
                                        {{ point.type }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rewards Tab -->
                <div v-if="activeTab === 'rewards'" class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="reward in available_rewards"
                            :key="reward.id"
                            class="border border-gray-200 rounded-lg p-4"
                            :class="reward.can_redeem ? 'hover:shadow-md transition-shadow' : 'opacity-60'"
                        >
                            <div class="flex items-start justify-between mb-2">
                                <h4 class="font-semibold text-gray-900">{{ reward.name }}</h4>
                                <span class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-full font-medium">
                                    {{ reward.points_cost }} pts
                                </span>
                            </div>
                            <div class="mb-3">
                                <p class="text-sm text-gray-600 mb-2">{{ reward.description }}</p>
                                <p class="text-lg font-bold text-blue-600">{{ formatDiscount(reward) }}</p>
                            </div>
                            <div v-if="reward.min_tier_name" class="text-xs text-gray-500 mb-2">
                                Requires: {{ reward.min_tier_name }} tier
                            </div>
                            <div v-if="reward.max_redemptions" class="text-xs text-gray-500 mb-3">
                                {{ reward.times_redeemed }} / {{ reward.max_redemptions }} redeemed
                            </div>
                            <button
                                @click="redeemReward(reward)"
                                :disabled="!reward.can_redeem || redeemingId === reward.id"
                                class="w-full px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                :class="reward.can_redeem 
                                    ? 'bg-blue-600 text-white hover:bg-blue-700' 
                                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
                            >
                                {{ redeemingId === reward.id ? 'Redeeming…' : (reward.can_redeem ? 'Redeem Now' : 'Not Available') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- History Tab -->
                <div v-if="activeTab === 'history'" class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="point in points_history" :key="point.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ point.created_at }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ point.description }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['text-xs px-2 py-1 rounded-full font-medium', getTypeColor(point.type)]">
                                        {{ point.type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold"
                                    :class="point.points >= 0 ? 'text-green-600' : 'text-red-600'"
                                >
                                    {{ formatPoints(point.points) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 font-medium">
                                    {{ point.balance_after }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tiers Tab -->
                <div v-if="activeTab === 'tiers'" class="grid gap-6 md:grid-cols-2">
                    <div
                        v-for="tier in all_tiers"
                        :key="tier.id"
                        class="rounded-lg border-2 p-6 transition-all"
                        :class="[
                            tier.is_current ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white',
                            tier.is_unlocked ? '' : 'opacity-60'
                        ]"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <span class="text-4xl">{{ tier.icon }}</span>
                                <div>
                                    <h3 class="text-xl font-bold" :style="{ color: tier.color }">{{ tier.name }}</h3>
                                    <p class="text-sm text-gray-600">{{ tier.min_points }} points required</p>
                                </div>
                            </div>
                            <span v-if="tier.is_current" class="text-xs px-3 py-1 bg-blue-600 text-white rounded-full font-medium">
                                Current
                            </span>
                            <span v-else-if="tier.is_unlocked" class="text-xs px-3 py-1 bg-green-600 text-white rounded-full font-medium">
                                Unlocked
                            </span>
                            <span v-else class="text-xs px-3 py-1 bg-gray-300 text-gray-600 rounded-full font-medium">
                                Locked
                            </span>
                        </div>
                        
                        <div class="space-y-2 mb-4">
                            <p class="text-sm font-semibold text-gray-900">Benefits:</p>
                            <ul class="space-y-1">
                                <li v-for="(benefit, index) in tier.benefits" :key="index" class="text-sm text-gray-700 flex items-start">
                                    <svg class="w-4 h-4 mr-2 mt-0.5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    {{ benefit }}
                                </li>
                            </ul>
                        </div>

                        <div class="pt-4 border-t border-gray-200 grid grid-cols-2 gap-4 text-center">
                            <div>
                                <p class="text-xs text-gray-600">Discount</p>
                                <p class="text-lg font-bold text-gray-900">{{ tier.discount_percentage }}%</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">Points</p>
                                <p class="text-lg font-bold text-gray-900">{{ tier.points_multiplier }}x</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- My Vouchers Tab -->
                <div v-if="activeTab === 'vouchers'">
                    <div v-if="my_vouchers.length === 0" class="bg-white rounded-lg border border-gray-200 p-10 text-center text-gray-500">
                        <p class="text-sm">No active vouchers. Redeem rewards to get voucher codes!</p>
                    </div>
                    <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="v in my_vouchers"
                            :key="v.id"
                            class="bg-white rounded-lg border border-gray-200 p-5 flex flex-col gap-3"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-medium text-gray-700">{{ v.reward_name }}</p>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700">Active</span>
                            </div>
                            <div class="flex items-center gap-2 rounded-md border border-dashed border-gray-300 bg-gray-50 px-3 py-2">
                                <span class="flex-1 font-mono text-base font-bold tracking-widest text-gray-900">{{ v.code }}</span>
                                <button
                                    type="button"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                                    @click="navigator.clipboard.writeText(v.code)"
                                >Copy</button>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>{{ v.type === 'discount_percentage' ? (v.value / 100) + '% discount' : 'RM ' + (v.value / 100).toFixed(2) + ' off' }}</span>
                                <span>Expires {{ v.expires_at }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ModernLayout>
</template>
