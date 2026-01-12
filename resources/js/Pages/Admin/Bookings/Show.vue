<script setup>
import ModernLayout from '@/Layouts/ModernLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    booking: {
        type: Object,
        required: true,
    },
    staffOptions: {
        type: Array,
        default: () => [],
    },
});

const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

function fmtDateTime(iso) {
    if (!iso) return '';
    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: timezone,
    }).format(new Date(iso));
}

function fmtMoney(cents, currency) {
    const amount = (Number(cents || 0) / 100).toFixed(2);
    return `${amount} ${currency || ''}`.trim();
}

function setStatus(status) {
    router.patch(route('admin.bookings.status', { booking: props.booking.id }), { status }, { preserveScroll: true });
}

function isoToUtcInput(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    return d.toISOString().slice(0, 16);
}

const assignmentForm = useForm({
    staff_id: props.booking.staff?.id ?? null,
    starts_at: isoToUtcInput(props.booking.starts_at),
    timezone: 'UTC',
});

function saveAssignment() {
    assignmentForm.patch(
        route('admin.bookings.assignment', { booking: props.booking.id }),
        { preserveScroll: true }
    );
}
</script>

<template>
    <ModernLayout title="Admin · Booking">
        <template #header>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Admin · Booking #{{ booking.id }}</h1>
                    <p class="mt-1 text-sm text-gray-600">Status: {{ booking.status }}</p>
                </div>
                <Link :href="route('admin.bookings.index')" class="text-sm font-medium text-gray-700 hover:text-gray-900">← Back</Link>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="bg-white border border-gray-200 rounded-lg p-6 lg:col-span-2">
                <h2 class="text-sm font-semibold text-gray-900">Details</h2>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="text-sm text-gray-700">
                        <div class="text-xs font-medium text-gray-500">Customer</div>
                        <div class="mt-1 font-medium text-gray-900">{{ booking.customer?.name || '-' }}</div>
                        <div class="text-xs text-gray-500">{{ booking.customer?.email || '' }}</div>
                    </div>

                    <div class="text-sm text-gray-700">
                        <div class="text-xs font-medium text-gray-500">Service</div>
                        <div class="mt-1 font-medium text-gray-900">{{ booking.service?.name || '-' }}</div>
                        <div class="text-xs text-gray-500">{{ booking.service_price?.name || '' }} · {{ booking.service_price?.duration_min || 0 }} min</div>
                    </div>

                    <div class="text-sm text-gray-700">
                        <div class="text-xs font-medium text-gray-500">Staff</div>
                        <div class="mt-1 font-medium text-gray-900">{{ booking.staff?.name || '-' }}</div>
                    </div>

                    <div class="text-sm text-gray-700">
                        <div class="text-xs font-medium text-gray-500">Time</div>
                        <div class="mt-1 font-medium text-gray-900">{{ fmtDateTime(booking.starts_at) }} – {{ fmtDateTime(booking.ends_at) }}</div>
                        <div v-if="booking.expires_at" class="text-xs text-gray-500">Expires: {{ fmtDateTime(booking.expires_at) }}</div>
                    </div>

                    <div class="text-sm text-gray-700">
                        <div class="text-xs font-medium text-gray-500">Amount</div>
                        <div class="mt-1 font-medium text-gray-900">{{ fmtMoney(booking.total_amount, booking.currency) }}</div>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-200 pt-6">
                    <h3 class="text-sm font-semibold text-gray-900">Reschedule / Reassign</h3>
                    <form class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-6" @submit.prevent="saveAssignment">
                        <div class="sm:col-span-3">
                            <InputLabel for="staff_id" value="Staff" />
                            <select id="staff_id" v-model.number="assignmentForm.staff_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option v-for="s in props.staffOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="assignmentForm.errors.staff_id" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel for="starts_at" value="Start (UTC)" />
                            <TextInput id="starts_at" v-model="assignmentForm.starts_at" type="datetime-local" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="assignmentForm.errors.starts_at" />
                        </div>

                        <div class="sm:col-span-1">
                            <InputLabel for="tz" value="Timezone" />
                            <TextInput id="tz" v-model="assignmentForm.timezone" type="text" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="assignmentForm.errors.timezone" />
                        </div>

                        <div class="sm:col-span-6 flex gap-2">
                            <PrimaryButton :type="'submit'" :disabled="assignmentForm.processing">Save</PrimaryButton>
                            <SecondaryButton :type="'button'" :disabled="assignmentForm.processing" @click="assignmentForm.reset()">Reset</SecondaryButton>
                        </div>
                    </form>
                </div>
            </section>

            <section class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-sm font-semibold text-gray-900">Actions</h2>
                <div class="mt-4 space-y-2">
                    <PrimaryButton :type="'button'" class="w-full justify-center" @click="setStatus('confirmed')">
                        Mark confirmed
                    </PrimaryButton>
                    <SecondaryButton :type="'button'" class="w-full justify-center" @click="setStatus('cancelled')">
                        Mark cancelled
                    </SecondaryButton>
                    <SecondaryButton :type="'button'" class="w-full justify-center" @click="setStatus('refunded')">
                        Mark refunded
                    </SecondaryButton>
                    <SecondaryButton :type="'button'" class="w-full justify-center" @click="setStatus('expired')">
                        Mark expired
                    </SecondaryButton>
                </div>
            </section>
        </div>

        <section class="mt-6 bg-white border border-gray-200 rounded-lg p-6">
            <h2 class="text-sm font-semibold text-gray-900">Payments</h2>

            <div v-if="booking.payments.length === 0" class="mt-4 text-sm text-gray-600">No payments.</div>

            <div v-else class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ref</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Paid at</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Events</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="p in booking.payments" :key="p.id">
                            <td class="px-3 py-2 text-sm text-gray-900">#{{ p.id }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ p.provider }}</td>
                            <td class="px-3 py-2 text-sm text-gray-700">{{ p.provider_ref }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ p.status }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ fmtMoney(p.amount, p.currency) }}</td>
                            <td class="px-3 py-2 text-sm text-gray-700">{{ p.paid_at ? fmtDateTime(p.paid_at) : '-' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-700">
                                <span v-if="p.events && p.events.length">{{ p.events.length }}</span>
                                <span v-else class="text-gray-500">-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </ModernLayout>
</template>
