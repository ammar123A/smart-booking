<script setup>
import ModernLayout from '@/Layouts/ModernLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    service: Object,
    allStaff: Array,
    assignedStaffIds: Array,
});

const serviceForm = useForm({
    name: props.service.name ?? '',
    description: props.service.description ?? '',
    active: !!props.service.active,
});

const staffForm = useForm({
    staff_ids: [...(props.assignedStaffIds ?? [])],
});

const newPriceForm = useForm({
    name: '',
    duration_min: 30,
    amount: 10000,
    currency: 'MYR',
    active: true,
});

const saveService = () => {
    serviceForm.patch(route('admin.services.update', props.service.id));
};

const saveStaff = () => {
    staffForm.put(route('admin.services.staff.sync', props.service.id));
};

const addPrice = () => {
    newPriceForm.post(route('admin.services.prices.store', props.service.id), { preserveScroll: true });
};

const destroyPrice = (id) => {
    router.delete(route('admin.prices.destroy', id), { preserveScroll: true });
};

const priceFormFor = (price) => {
    return useForm({
        name: price.name,
        duration_min: price.duration_min,
        amount: price.amount,
        currency: price.currency,
        active: !!price.active,
    });
};
</script>

<template>
    <ModernLayout title="Admin · Edit Service">

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin · Edit Service</h2>
                <Link class="text-sm text-gray-600 hover:text-gray-900" :href="route('admin.services.index')">← Back</Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">Service</h3>
                <form class="mt-4 space-y-4" @submit.prevent="saveService">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="serviceForm.name" type="text" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="serviceForm.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" />
                        <TextInput id="description" v-model="serviceForm.description" type="text" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="serviceForm.errors.description" />
                    </div>

                    <label class="flex items-center">
                        <Checkbox v-model:checked="serviceForm.active" />
                        <span class="ms-2 text-sm text-gray-600">Active</span>
                    </label>

                    <div>
                        <PrimaryButton :disabled="serviceForm.processing">Save</PrimaryButton>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">Assigned staff</h3>
                <form class="mt-4 space-y-4" @submit.prevent="saveStaff">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label v-for="s in props.allStaff" :key="s.id" class="flex items-center">
                            <Checkbox :checked="staffForm.staff_ids.includes(s.id)" @update:checked="(v) => {
                                if (v) {
                                    if (!staffForm.staff_ids.includes(s.id)) staffForm.staff_ids.push(s.id);
                                } else {
                                    staffForm.staff_ids = staffForm.staff_ids.filter(id => id !== s.id);
                                }
                            }" />
                            <span class="ms-2 text-sm text-gray-700">{{ s.name }}</span>
                        </label>
                    </div>
                    <InputError class="mt-2" :message="staffForm.errors.staff_ids" />
                    <div>
                        <PrimaryButton :disabled="staffForm.processing">Save staff</PrimaryButton>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">Prices</h3>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Currency</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="price in props.service.prices" :key="price.id">
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ price.name }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ price.duration_min }} min</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ price.amount }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ price.currency }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ price.active ? 'Yes' : 'No' }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <DangerButton as="button" type="button" @click="destroyPrice(price.id)">Delete</DangerButton>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 border-t border-gray-200 pt-6">
                        <h4 class="text-md font-medium text-gray-900">Add price</h4>
                        <form class="mt-4 grid grid-cols-1 sm:grid-cols-6 gap-4" @submit.prevent="addPrice">
                            <div class="sm:col-span-2">
                                <InputLabel for="p_name" value="Name" />
                                <TextInput id="p_name" v-model="newPriceForm.name" type="text" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="newPriceForm.errors.name" />
                            </div>
                            <div>
                                <InputLabel for="p_duration" value="Duration (min)" />
                                <TextInput id="p_duration" v-model="newPriceForm.duration_min" type="number" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="newPriceForm.errors.duration_min" />
                            </div>
                            <div>
                                <InputLabel for="p_amount" value="Amount (cents)" />
                                <TextInput id="p_amount" v-model="newPriceForm.amount" type="number" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="newPriceForm.errors.amount" />
                            </div>
                            <div>
                                <InputLabel for="p_currency" value="Currency" />
                                <TextInput id="p_currency" v-model="newPriceForm.currency" type="text" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="newPriceForm.errors.currency" />
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center">
                                    <Checkbox v-model:checked="newPriceForm.active" />
                                    <span class="ms-2 text-sm text-gray-600">Active</span>
                                </label>
                            </div>
                            <div class="sm:col-span-6">
                                <PrimaryButton :disabled="newPriceForm.processing">Add price</PrimaryButton>
                                <SecondaryButton type="button" class="ms-2" @click="newPriceForm.reset()">Reset</SecondaryButton>
                            </div>
                        </form>
                    </div>
            </div>
        </div>
    </ModernLayout>
</template>
