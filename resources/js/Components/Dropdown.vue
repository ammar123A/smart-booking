<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    width: {
        type: String,
        default: '48',
    },
    contentClasses: {
        type: Array,
        default: () => ['py-1', 'bg-white'],
    },
    direction: {
        type: String,
        default: 'down',
    },
});

let open = ref(false);

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const widthClass = computed(() => {
    return {
        '48': 'w-48',
    }[props.width.toString()];
});

const alignmentClasses = computed(() => {
    const baseAlign = props.align === 'left' 
        ? 'start-0'
        : props.align === 'right' 
        ? 'end-0' 
        : '';
    
    if (props.direction === 'up') {
        const origin = props.align === 'left'
            ? 'ltr:origin-bottom-left rtl:origin-bottom-right'
            : props.align === 'right'
            ? 'ltr:origin-bottom-right rtl:origin-bottom-left'
            : 'origin-bottom';
        return `${origin} ${baseAlign} bottom-full mb-2`;
    }
    
    const origin = props.align === 'left'
        ? 'ltr:origin-top-left rtl:origin-top-right'
        : props.align === 'right'
        ? 'ltr:origin-top-right rtl:origin-top-left'
        : 'origin-top';
    return `${origin} ${baseAlign}`;
});
</script>

<template>
    <div class="relative">
        <div @click="open = ! open">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div v-show="open" class="fixed inset-0 z-40" @click="open = false" />

        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-show="open"
                class="absolute z-50 rounded-md shadow-lg"
                :class="[widthClass, alignmentClasses]"
                style="display: none;"
                @click="open = false"
            >
                <div class="rounded-md ring-1 ring-black ring-opacity-5" :class="contentClasses">
                    <slot name="content" />
                </div>
            </div>
        </transition>
    </div>
</template>
