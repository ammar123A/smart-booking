<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        required: true,
    },
    timezone: {
        type: String,
        default: 'UTC',
    },
    startHour: {
        type: Number,
        default: 6,
    },
    endHour: {
        type: Number,
        default: 22,
    },
    stepMinutes: {
        type: Number,
        default: 30,
    },
});

const emit = defineEmits(['update:modelValue']);

const days = [
    { id: 0, label: 'Sun' },
    { id: 1, label: 'Mon' },
    { id: 2, label: 'Tue' },
    { id: 3, label: 'Wed' },
    { id: 4, label: 'Thu' },
    { id: 5, label: 'Fri' },
    { id: 6, label: 'Sat' },
];

const minutesStart = computed(() => props.startHour * 60);
const minutesEnd = computed(() => props.endHour * 60);

const timeRows = computed(() => {
    const rows = [];
    for (let m = minutesStart.value; m <= minutesEnd.value; m += props.stepMinutes) rows.push(m);
    return rows;
});

function clampMinutes(m) {
    return Math.min(minutesEnd.value, Math.max(minutesStart.value, m));
}

function minutesToTime(m) {
    const h = Math.floor(m / 60);
    const mm = String(m % 60).padStart(2, '0');
    return `${String(h).padStart(2, '0')}:${mm}`;
}

function timeToMinutes(t) {
    const [h, m] = String(t || '').split(':').map((x) => Number(x));
    return h * 60 + m;
}

function normalize(blocks) {
    // Merge overlapping/adjacent blocks per day.
    const byDay = new Map();
    for (const b of blocks) {
        const day = Number(b.day_of_week);
        const start = clampMinutes(timeToMinutes(b.start_time));
        const end = clampMinutes(timeToMinutes(b.end_time));
        if (!Number.isFinite(day) || end <= start) continue;
        if (!byDay.has(day)) byDay.set(day, []);
        byDay.get(day).push({ day_of_week: day, start, end });
    }

    const out = [];
    for (const [day, list] of byDay.entries()) {
        list.sort((a, b) => a.start - b.start);
        const merged = [];
        for (const cur of list) {
            const last = merged[merged.length - 1];
            if (!last) {
                merged.push({ ...cur });
                continue;
            }
            if (cur.start <= last.end) {
                last.end = Math.max(last.end, cur.end);
            } else if (cur.start === last.end) {
                last.end = cur.end;
            } else {
                merged.push({ ...cur });
            }
        }

        for (const m of merged) {
            out.push({
                day_of_week: day,
                start_time: minutesToTime(m.start),
                end_time: minutesToTime(m.end),
                timezone: props.timezone,
                active: true,
            });
        }
    }

    out.sort((a, b) => (a.day_of_week - b.day_of_week) || (timeToMinutes(a.start_time) - timeToMinutes(b.start_time)));
    return out;
}

const blocks = computed(() => normalize(props.modelValue));

function updateBlocks(next) {
    emit('update:modelValue', normalize(next));
}

function blocksForDay(dayId) {
    return blocks.value.filter((b) => Number(b.day_of_week) === dayId);
}

function blockStyle(b) {
    const start = timeToMinutes(b.start_time);
    const end = timeToMinutes(b.end_time);
    const top = ((start - minutesStart.value) / (minutesEnd.value - minutesStart.value)) * 100;
    const height = ((end - start) / (minutesEnd.value - minutesStart.value)) * 100;
    return {
        top: `${top}%`,
        height: `${height}%`,
    };
}

function removeBlock(dayId, start_time, end_time) {
    updateBlocks(props.modelValue.filter((b) => !(Number(b.day_of_week) === dayId && b.start_time === start_time && b.end_time === end_time)));
}

const selected = ref(null); // { day_of_week, start_time, end_time }

const dragging = ref(false);
const dragDay = ref(null);
const dragStartMin = ref(null);
const dragEndMin = ref(null);

const dragMode = ref(null); // 'create' | 'move' | 'resize-start' | 'resize-end'
const dragOriginal = ref(null); // { day, startMin, endMin }
const dragOffsetMin = ref(0);

function gridMinutesFromEvent(e, colEl) {
    const col = colEl ?? e.currentTarget;
    const rect = col.getBoundingClientRect();
    const y = e.clientY - rect.top;
    const ratio = rect.height > 0 ? y / rect.height : 0;
    const minutes = minutesStart.value + ratio * (minutesEnd.value - minutesStart.value);
    const stepped = Math.round(minutes / props.stepMinutes) * props.stepMinutes;
    return clampMinutes(stepped);
}

function startCreate(dayId, e) {
    dragging.value = true;
    dragMode.value = 'create';
    dragDay.value = dayId;
    const m = gridMinutesFromEvent(e);
    dragStartMin.value = m;
    dragEndMin.value = m + props.stepMinutes;
    dragOriginal.value = null;

    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp);
}

function startMove(b, e) {
    const dayId = Number(b.day_of_week);
    dragging.value = true;
    dragMode.value = 'move';
    dragDay.value = dayId;

    const startMin = timeToMinutes(b.start_time);
    const endMin = timeToMinutes(b.end_time);

    dragOriginal.value = { day: dayId, startMin, endMin };

    const cols = document.querySelectorAll('[data-weekly-col]');
    const col = Array.from(cols).find((el) => Number(el.getAttribute('data-day')) === dayId);
    const pointerMin = col ? gridMinutesFromEvent(e, col) : startMin;
    dragOffsetMin.value = pointerMin - startMin;

    dragStartMin.value = startMin;
    dragEndMin.value = endMin;

    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp);
}

function startResize(b, which, e) {
    const dayId = Number(b.day_of_week);
    dragging.value = true;
    dragMode.value = which === 'start' ? 'resize-start' : 'resize-end';
    dragDay.value = dayId;

    const startMin = timeToMinutes(b.start_time);
    const endMin = timeToMinutes(b.end_time);
    dragOriginal.value = { day: dayId, startMin, endMin };

    dragStartMin.value = startMin;
    dragEndMin.value = endMin;

    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp);
}

function onMove(e) {
    if (!dragging.value) return;
    const cols = document.querySelectorAll('[data-weekly-col]');
    const col = Array.from(cols).find((el) => Number(el.getAttribute('data-day')) === Number(dragDay.value));
    if (!col) return;

    const pointerMin = gridMinutesFromEvent(e, col);

    if (dragMode.value === 'create') {
        dragEndMin.value = pointerMin;
        return;
    }

    if (!dragOriginal.value) return;

    if (dragMode.value === 'move') {
        const duration = dragOriginal.value.endMin - dragOriginal.value.startMin;
        const nextStart = clampMinutes(pointerMin - dragOffsetMin.value);
        const nextEnd = clampMinutes(nextStart + duration);

        // keep duration; clamp if hitting end
        if (nextEnd - nextStart !== duration) {
            const clampedStart = clampMinutes(minutesEnd.value - duration);
            dragStartMin.value = clampedStart;
            dragEndMin.value = clampedStart + duration;
        } else {
            dragStartMin.value = nextStart;
            dragEndMin.value = nextEnd;
        }
        return;
    }

    if (dragMode.value === 'resize-start') {
        const nextStart = clampMinutes(pointerMin);
        const minLen = props.stepMinutes;
        dragStartMin.value = Math.min(nextStart, dragEndMin.value - minLen);
        return;
    }

    if (dragMode.value === 'resize-end') {
        const nextEnd = clampMinutes(pointerMin);
        const minLen = props.stepMinutes;
        dragEndMin.value = Math.max(nextEnd, dragStartMin.value + minLen);
    }
}

function onUp() {
    if (!dragging.value) return;

    const dayId = Number(dragDay.value);
    const a = clampMinutes(dragStartMin.value);
    const b = clampMinutes(dragEndMin.value);
    const start = Math.min(a, b);
    const end = Math.max(a, b);

    const mode = dragMode.value;
    const original = dragOriginal.value;

    dragging.value = false;
    dragMode.value = null;
    dragOriginal.value = null;
    dragOffsetMin.value = 0;
    dragDay.value = null;
    dragStartMin.value = null;
    dragEndMin.value = null;

    window.removeEventListener('mousemove', onMove);
    window.removeEventListener('mouseup', onUp);

    if (end <= start) return;

    if (mode === 'create') {
        const next = [
            ...props.modelValue,
            {
                day_of_week: dayId,
                start_time: minutesToTime(start),
                end_time: minutesToTime(end),
                timezone: props.timezone,
                active: true,
            },
        ];
        updateBlocks(next);
        selected.value = { day_of_week: dayId, start_time: minutesToTime(start), end_time: minutesToTime(end) };
        return;
    }

    if ((mode === 'move' || mode === 'resize-start' || mode === 'resize-end') && original) {
        const next = props.modelValue.map((x) => {
            if (Number(x.day_of_week) !== original.day) return x;
            if (x.start_time !== minutesToTime(original.startMin)) return x;
            if (x.end_time !== minutesToTime(original.endMin)) return x;
            return {
                ...x,
                day_of_week: dayId,
                start_time: minutesToTime(start),
                end_time: minutesToTime(end),
                timezone: props.timezone,
                active: true,
            };
        });
        updateBlocks(next);
        selected.value = { day_of_week: dayId, start_time: minutesToTime(start), end_time: minutesToTime(end) };
    }
}

const preview = computed(() => {
    if (!dragging.value || dragDay.value === null) return null;
    const a = clampMinutes(dragStartMin.value);
    const b = clampMinutes(dragEndMin.value);
    const start = Math.min(a, b);
    const end = Math.max(a, b);
    if (end <= start) return null;
    return {
        day_of_week: Number(dragDay.value),
        start_time: minutesToTime(start),
        end_time: minutesToTime(end),
    };
});

onBeforeUnmount(() => {
    window.removeEventListener('mousemove', onMove);
    window.removeEventListener('mouseup', onUp);
});
</script>

<template>
    <div>
        <div class="flex items-end justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-gray-900">Weekly schedule</div>
                <div class="mt-1 text-sm text-gray-600">Drag inside a day to add a block. Click a block to remove.</div>
            </div>
            <div class="text-xs text-gray-500">Timezone: {{ timezone }}</div>
        </div>

        <div class="mt-4 grid grid-cols-8 gap-2">
            <div class="text-xs text-gray-500">
                <div class="h-8" />
                <div class="relative" :style="{ height: '520px' }">
                    <div v-for="m in timeRows" :key="m" class="absolute left-0 w-full" :style="{ top: ((m - minutesStart) / (minutesEnd - minutesStart)) * 100 + '%' }">
                        <div class="-translate-y-1/2 text-[11px] text-gray-500">{{ minutesToTime(m) }}</div>
                    </div>
                </div>
            </div>

            <div v-for="d in days" :key="d.id" class="min-w-0">
                <div class="h-8 flex items-center justify-center text-xs font-medium text-gray-700">{{ d.label }}</div>

                <div
                    class="relative rounded-md border border-gray-200 bg-white"
                    :style="{ height: '520px' }"
                    data-weekly-col
                    :data-day="d.id"
                    @mousedown.left.prevent="startCreate(d.id, $event)"
                >
                    <!-- grid lines -->
                    <div v-for="m in timeRows" :key="m" class="absolute left-0 right-0 border-t border-gray-100" :style="{ top: ((m - minutesStart) / (minutesEnd - minutesStart)) * 100 + '%' }" />

                    <!-- blocks -->
                    <div
                        v-for="b in blocksForDay(d.id)"
                        :key="b.start_time + '-' + b.end_time"
                        class="absolute left-1 right-1 rounded-md border px-2 py-1 text-left text-xs"
                        :class="(
                            selected && selected.day_of_week === d.id && selected.start_time === b.start_time && selected.end_time === b.end_time
                        ) ? 'border-gray-800 bg-gray-100 text-gray-900' : 'border-gray-300 bg-gray-50 text-gray-800'"
                        :style="blockStyle(b)"
                        @mousedown.left.stop.prevent="(e) => { selected = { day_of_week: d.id, start_time: b.start_time, end_time: b.end_time }; startMove(b, e); }"
                        :title="b.start_time + '–' + b.end_time"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="font-medium">{{ b.start_time }}–{{ b.end_time }}</div>
                            <button
                                type="button"
                                class="rounded px-1 text-gray-500 hover:text-gray-900"
                                title="Remove"
                                @click.stop.prevent="removeBlock(d.id, b.start_time, b.end_time)"
                            >
                                ×
                            </button>
                        </div>

                        <div
                            class="absolute left-0 right-0 -top-1 h-2 cursor-ns-resize"
                            @mousedown.left.stop.prevent="(e) => startResize(b, 'start', e)"
                            title="Resize start"
                        />
                        <div
                            class="absolute left-0 right-0 -bottom-1 h-2 cursor-ns-resize"
                            @mousedown.left.stop.prevent="(e) => startResize(b, 'end', e)"
                            title="Resize end"
                        />
                    </div>

                    <!-- drag preview -->
                    <div
                        v-if="preview && preview.day_of_week === d.id"
                        class="absolute left-1 right-1 rounded-md border border-gray-800 bg-gray-800/10"
                        :style="blockStyle(preview)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
