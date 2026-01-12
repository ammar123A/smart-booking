<script setup>
import { Bar, Line, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Title, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Title, Tooltip, Legend);

defineProps({
    type: {
        type: String,
        required: true,
        validator: (value) => ['bar', 'line', 'doughnut'].includes(value)
    },
    data: {
        type: Object,
        required: true
    },
    options: {
        type: Object,
        default: () => ({})
    }
});

const components = {
    bar: Bar,
    line: Line,
    doughnut: Doughnut
};
</script>

<template>
    <component :is="components[type]" :data="data" :options="options" />
</template>
