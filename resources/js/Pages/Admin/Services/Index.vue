<script setup>
import ModernLayout from '@/Layouts/ModernLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    services: Array,
});

const form = useForm({
    name: '',
    description: '',
    active: true,
});

const submit = () => {
    form.post(route('admin.services.store'), { preserveScroll: true });
};

const destroyService = (id) => {
    router.delete(route('admin.services.destroy', id), { preserveScroll: true });
};
</script>

<template>
    <ModernLayout title="Admin · Services">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin · Services</h2>
        </template>

        <div class="space-y-6">
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">Create service</h3>

                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" autofocus />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" />
                        <TextInput id="description" v-model="form.description" type="text" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <label class="flex items-center">
                        <Checkbox v-model:checked="form.active" />
                        <span class="ms-2 text-sm text-gray-600">Active</span>
                    </label>

                    <div>
                        <PrimaryButton :disabled="form.processing">Create</PrimaryButton>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Services</h3>
                        <Link class="text-sm text-gray-600 hover:text-gray-900" :href="route('admin.staff.index')">Manage staff →</Link>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Prices</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Staff</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="service in props.services" :key="service.id">
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ service.name }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ service.active ? 'Yes' : 'No' }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ service.prices_count }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ service.staff_count }}</td>
                                    <td class="px-3 py-2 text-right space-x-2">
                                        <Link class="text-sm font-medium text-gray-700 hover:text-gray-900" :href="route('admin.services.edit', service.id)">Edit</Link>
                                        <DangerButton as="button" type="button" @click="destroyService(service.id)">
                                            Delete
                                        </DangerButton>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </ModernLayout>
</template>
