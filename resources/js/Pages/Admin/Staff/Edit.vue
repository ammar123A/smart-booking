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
import WeeklyScheduleEditor from '@/Components/WeeklyScheduleEditor.vue';

const props = defineProps({
    staffMember: Object,
});

const staffForm = useForm({
    name: props.staffMember.name ?? '',
    timezone: props.staffMember.timezone ?? 'Asia/Kuala_Lumpur',
    active: !!props.staffMember.active,
});

const newScheduleForm = useForm({
    day_of_week: 1,
    start_time: '09:00',
    end_time: '17:00',
    timezone: props.staffMember.timezone ?? 'Asia/Kuala_Lumpur',
    active: true,
});

const bulkSchedulesForm = useForm({
    schedules: (props.staffMember.schedules ?? []).map((s) => ({
        day_of_week: s.day_of_week,
        start_time: String(s.start_time || '').slice(0, 5),
        end_time: String(s.end_time || '').slice(0, 5),
        timezone: s.timezone || (props.staffMember.timezone ?? 'Asia/Kuala_Lumpur'),
        active: !!s.active,
    })),
});

const saveStaff = () => {
    staffForm.patch(route('admin.staff.update', props.staffMember.id));
};

const addSchedule = () => {
    newScheduleForm.post(route('admin.staff.schedules.store', props.staffMember.id), { preserveScroll: true });
};

const dayLabel = (d) => {
    return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][d] ?? String(d);
};

const destroySchedule = (id) => {
    router.delete(route('admin.schedules.destroy', id), { preserveScroll: true });
};

const saveWeeklyGrid = () => {
    bulkSchedulesForm.put(route('admin.staff.schedules.bulk', props.staffMember.id), { preserveScroll: true });
};

const newTimeOffForm = useForm({
    starts_at: '',
    ends_at: '',
    reason: '',
    timezone: props.staffMember.timezone ?? 'Asia/Kuala_Lumpur',
    active: true,
});

const addTimeOff = () => {
    newTimeOffForm.post(route('admin.staff.time_offs.store', props.staffMember.id), { preserveScroll: true });
};

const destroyTimeOff = (id) => {
    router.delete(route('admin.time_offs.destroy', id), { preserveScroll: true });
};
</script>

<template>
    <ModernLayout title="Admin · Edit Staff">

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin · Edit Staff</h2>
                <Link class="text-sm text-gray-600 hover:text-gray-900" :href="route('admin.staff.index')">← Back</Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">Staff</h3>
                <form class="mt-4 space-y-4" @submit.prevent="saveStaff">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="staffForm.name" type="text" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="staffForm.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="timezone" value="Timezone" />
                        <TextInput id="timezone" v-model="staffForm.timezone" type="text" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="staffForm.errors.timezone" />
                    </div>

                    <label class="flex items-center">
                        <Checkbox v-model:checked="staffForm.active" />
                        <span class="ms-2 text-sm text-gray-600">Active</span>
                    </label>

                    <div>
                        <PrimaryButton :disabled="staffForm.processing">Save</PrimaryButton>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">Schedules</h3>

                <div class="mt-4">
                    <WeeklyScheduleEditor
                        v-model="bulkSchedulesForm.schedules"
                        :timezone="staffForm.timezone"
                    />
                    <InputError class="mt-2" :message="bulkSchedulesForm.errors.schedules" />
                    <div class="mt-4">
                        <PrimaryButton :disabled="bulkSchedulesForm.processing" @click="saveWeeklyGrid" :type="'button'">
                            Save weekly schedule
                        </PrimaryButton>
                        <SecondaryButton type="button" class="ms-2" @click="bulkSchedulesForm.reset()">
                            Reset
                        </SecondaryButton>
                    </div>
                </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Day</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Start</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">End</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Timezone</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="sch in props.staffMember.schedules" :key="sch.id">
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ dayLabel(sch.day_of_week) }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ sch.start_time }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ sch.end_time }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ sch.timezone }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ sch.active ? 'Yes' : 'No' }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <DangerButton as="button" type="button" @click="destroySchedule(sch.id)">Delete</DangerButton>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 border-t border-gray-200 pt-6">
                        <h4 class="text-md font-medium text-gray-900">Add schedule</h4>
                        <form class="mt-4 grid grid-cols-1 sm:grid-cols-6 gap-4" @submit.prevent="addSchedule">
                            <div>
                                <InputLabel for="day" value="Day" />
                                <select id="day" v-model.number="newScheduleForm.day_of_week" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option v-for="d in [0,1,2,3,4,5,6]" :key="d" :value="d">{{ dayLabel(d) }}</option>
                                </select>
                                <InputError class="mt-2" :message="newScheduleForm.errors.day_of_week" />
                            </div>
                            <div>
                                <InputLabel for="start" value="Start" />
                                <TextInput id="start" v-model="newScheduleForm.start_time" type="time" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="newScheduleForm.errors.start_time" />
                            </div>
                            <div>
                                <InputLabel for="end" value="End" />
                                <TextInput id="end" v-model="newScheduleForm.end_time" type="time" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="newScheduleForm.errors.end_time" />
                            </div>
                            <div class="sm:col-span-2">
                                <InputLabel for="tz" value="Timezone" />
                                <TextInput id="tz" v-model="newScheduleForm.timezone" type="text" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="newScheduleForm.errors.timezone" />
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center">
                                    <Checkbox v-model:checked="newScheduleForm.active" />
                                    <span class="ms-2 text-sm text-gray-600">Active</span>
                                </label>
                            </div>
                            <div class="sm:col-span-6">
                                <PrimaryButton :disabled="newScheduleForm.processing">Add schedule</PrimaryButton>
                                <SecondaryButton type="button" class="ms-2" @click="newScheduleForm.reset()">Reset</SecondaryButton>
                            </div>
                        </form>
                    </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">Time off</h3>
                <p class="mt-1 text-sm text-gray-600">Blocks availability and auto-assignment during this period.</p>

                <div class="mt-4 overflow-x-auto" v-if="(props.staffMember.time_offs ?? []).length">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Start (UTC)</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">End (UTC)</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="t in props.staffMember.time_offs" :key="t.id">
                                <td class="px-3 py-2 text-sm text-gray-900">{{ t.starts_at }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">{{ t.ends_at }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">{{ t.reason || '-' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">{{ t.active ? 'Yes' : 'No' }}</td>
                                <td class="px-3 py-2 text-right">
                                    <DangerButton as="button" type="button" @click="destroyTimeOff(t.id)">Delete</DangerButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 border-t border-gray-200 pt-6">
                    <h4 class="text-md font-medium text-gray-900">Add time off</h4>
                    <form class="mt-4 grid grid-cols-1 sm:grid-cols-6 gap-4" @submit.prevent="addTimeOff">
                        <div>
                            <InputLabel for="to_start" value="Start" />
                            <TextInput id="to_start" v-model="newTimeOffForm.starts_at" type="datetime-local" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="newTimeOffForm.errors.starts_at" />
                        </div>
                        <div>
                            <InputLabel for="to_end" value="End" />
                            <TextInput id="to_end" v-model="newTimeOffForm.ends_at" type="datetime-local" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="newTimeOffForm.errors.ends_at" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="to_reason" value="Reason" />
                            <TextInput id="to_reason" v-model="newTimeOffForm.reason" type="text" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="newTimeOffForm.errors.reason" />
                        </div>
                        <div class="sm:col-span-1">
                            <InputLabel for="to_tz" value="Timezone" />
                            <TextInput id="to_tz" v-model="newTimeOffForm.timezone" type="text" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="newTimeOffForm.errors.timezone" />
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center">
                                <Checkbox v-model:checked="newTimeOffForm.active" />
                                <span class="ms-2 text-sm text-gray-600">Active</span>
                            </label>
                        </div>
                        <div class="sm:col-span-6">
                            <PrimaryButton :disabled="newTimeOffForm.processing">Add time off</PrimaryButton>
                            <SecondaryButton type="button" class="ms-2" @click="newTimeOffForm.reset()">Reset</SecondaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </ModernLayout>
</template>
