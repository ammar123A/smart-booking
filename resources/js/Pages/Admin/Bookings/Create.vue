<script setup>
import ModernLayout from '@/Layouts/ModernLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    servicePrices: {
        type: Array,
        required: true,
    },
    staff: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    customer_email: '',
    service_price_id: props.servicePrices[0]?.id ?? null,
    starts_at: '',
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
    staff_id: null,
});

function submit() {
    form.post(route('admin.bookings.store'));
}

function priceLabel(p) {
    const serviceName = p.service?.name || 'Service';
    const duration = p.duration_min || 0;
    const amount = ((Number(p.amount || 0) / 100).toFixed(2)) + ' ' + (p.currency || '');
    return `${serviceName} · ${p.name} · ${duration} min · ${amount}`;
}
</script>

<template>
    <ModernLayout title="Admin · Create Booking">
        <template #header>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Admin · Create booking</h1>
                    <p class="mt-1 text-sm text-gray-600">Creates a confirmed booking (no payment).</p>
                </div>
                <Link :href="route('admin.bookings.index')" class="text-sm font-medium text-gray-700 hover:text-gray-900">← Back</Link>
            </div>
        </template>

        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <form class="grid grid-cols-1 gap-4 sm:grid-cols-6" @submit.prevent="submit">
                <div class="sm:col-span-3">
                    <InputLabel for="email" value="Customer email" />
                    <TextInput id="email" v-model="form.customer_email" type="email" class="mt-1 block w-full" placeholder="customer@example.com" />
                    <InputError class="mt-2" :message="form.errors.customer_email" />
                </div>

                <div class="sm:col-span-3">
                    <InputLabel for="price" value="Service price" />
                    <select id="price" v-model.number="form.service_price_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option v-for="p in props.servicePrices" :key="p.id" :value="p.id">{{ priceLabel(p) }}</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.service_price_id" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel for="starts" value="Start" />
                    <TextInput id="starts" v-model="form.starts_at" type="datetime-local" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.starts_at" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel for="tz" value="Timezone" />
                    <TextInput id="tz" v-model="form.timezone" type="text" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.timezone" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel for="staff" value="Staff (optional)" />
                    <select id="staff" v-model.number="form.staff_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option :value="null">Auto assign</option>
                        <option v-for="s in props.staff" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.staff_id" />
                </div>

                <div class="sm:col-span-6 mt-2 flex gap-2">
                    <PrimaryButton :disabled="form.processing">Create booking</PrimaryButton>
                    <SecondaryButton type="button" :disabled="form.processing" @click="form.reset()">Reset</SecondaryButton>
                </div>
            </form>
        </div>
    </ModernLayout>
</template>
