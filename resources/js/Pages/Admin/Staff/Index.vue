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
    staff: Array,
});

const form = useForm({
    name: '',
    timezone: 'Asia/Kuala_Lumpur',
    active: true,
});

const submit = () => {
    form.post(route('admin.staff.store'), { preserveScroll: true });
};

const destroyStaff = (id) => {
    router.delete(route('admin.staff.destroy', id), { preserveScroll: true });
};
</script>

<template>
    <ModernLayout title="Admin · Staff">

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin · Staff</h2>
        </template>

        <div class="space-y-6">
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Create staff</h3>
                    <Link class="text-sm text-gray-600 hover:text-gray-900" :href="route('admin.services.index')">Manage services →</Link>
                </div>

                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" autofocus />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="timezone" value="Timezone" />
                        <TextInput id="timezone" v-model="form.timezone" type="text" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.timezone" />
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
                    <h3 class="text-lg font-medium text-gray-900">Staff</h3>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Schedules</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Services</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="s in props.staff" :key="s.id">
                                        <td class="px-3 py-2 text-sm text-gray-900">{{ s.name }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900">{{ s.active ? 'Yes' : 'No' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900">{{ s.schedules_count }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900">{{ s.services_count }}</td>
                                        <td class="px-3 py-2 text-right space-x-2">
                                            <Link class="text-sm font-medium text-gray-700 hover:text-gray-900" :href="route('admin.staff.edit', s.id)">Edit</Link>
                                            <DangerButton as="button" type="button" @click="destroyStaff(s.id)">Delete</DangerButton>
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
