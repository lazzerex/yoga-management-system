<script setup>
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    labels: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    height: { type: Number, default: 260 },
    // Twelve distinct hues: a ten-slice donut must not repeat a colour.
    colors: {
        type: Array,
        default: () => [
            '#4f9f5f', '#5f83c2', '#c28a3a', '#8469bf', '#c64f57', '#3f9c9c',
            '#a4713c', '#6d8f3f', '#b3568c', '#4a6ea8', '#8a8f3f', '#8c5140',
        ],
    },
    formatter: { type: Function, default: null },
});

const options = computed(() => ({
    chart: { type: 'donut', toolbar: { show: false }, fontFamily: 'inherit' },
    colors: props.colors,
    labels: props.labels,
    dataLabels: { enabled: false },
    stroke: { width: 0 },
    plotOptions: { pie: { donut: { size: '64%' } } },
    legend: { position: 'bottom', fontSize: '12px' },
    tooltip: { y: { formatter: props.formatter ?? undefined } },
}));
</script>

<template>
    <VueApexCharts type="donut" :height="height" :options="options" :series="series" />
</template>
