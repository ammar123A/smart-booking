<template>
    <div class="relative" ref="container">
        <button
            @click.stop="toggleDropdown"
            class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10 transition-colors text-white"
            type="button"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
            </svg>
            <span class="text-sm font-medium">{{ currentLanguage.name }}</span>
            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute bottom-full left-0 mb-2 w-48 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 overflow-hidden z-50"
            >
                <div class="py-1">
                    <button
                        v-for="language in languages"
                        :key="language.code"
                        @click="switchLanguage(language.code)"
                        class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 transition-colors flex items-center justify-between"
                        :class="{ 'bg-gray-50 font-semibold': currentLocale === language.code }"
                        type="button"
                    >
                        <span class="flex items-center gap-2">
                            <span class="text-lg">{{ language.flag }}</span>
                            <span class="text-gray-900">{{ language.name }}</span>
                        </span>
                        <svg
                            v-if="currentLocale === language.code"
                            class="w-4 h-4 text-blue-600"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const isOpen = ref(false);
const container = ref(null);

const languages = [
    { code: 'en', name: 'English', flag: '🇬🇧' },
    { code: 'ms', name: 'Bahasa Melayu', flag: '🇲🇾' },
    { code: 'zh-CN', name: '中文', flag: '🇨🇳' },
];

const currentLocale = computed(() => page.props.locale || 'en');

const currentLanguage = computed(() => {
    return languages.find(lang => lang.code === currentLocale.value) || languages[0];
});

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const switchLanguage = (locale) => {
    router.post(route('locale.switch'), { locale }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
        },
    });
};

const handleClickOutside = (event) => {
    if (container.value && !container.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
