<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    message: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'success', // success, error, info, warning
    },
    duration: {
        type: Number,
        default: 3000,
    },
});

const emit = defineEmits(['close']);

const show = ref(false);

onMounted(() => {
    if (props.message) {
        show.value = true;
        if (props.duration > 0) {
            setTimeout(() => {
                close();
            }, props.duration);
        }
    }
});

watch(() => props.message, (newMessage) => {
    if (newMessage) {
        show.value = true;
        if (props.duration > 0) {
            setTimeout(() => {
                close();
            }, props.duration);
        }
    }
});

const close = () => {
    show.value = false;
    setTimeout(() => {
        emit('close');
    }, 300);
};

const typeClasses = {
    success: 'bg-green-50 text-green-800 border-green-200',
    error: 'bg-red-50 text-red-800 border-red-200',
    info: 'bg-blue-50 text-blue-800 border-blue-200',
    warning: 'bg-yellow-50 text-yellow-800 border-yellow-200',
};

const iconPaths = {
    success: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    error: 'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    info: 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
    warning: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
};
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
    >
        <div
            v-if="show && message"
            :class="[
                'fixed top-4 right-4 z-50 max-w-md w-full shadow-lg rounded-lg border p-4 flex items-start gap-3',
                typeClasses[type]
            ]"
        >
            <svg class="size-5 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" :d="iconPaths[type]" />
            </svg>
            
            <div class="flex-1 text-sm font-medium">
                {{ message }}
            </div>
            
            <button
                type="button"
                class="flex-shrink-0 text-current opacity-60 hover:opacity-100"
                @click="close"
            >
                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </Transition>
</template>
