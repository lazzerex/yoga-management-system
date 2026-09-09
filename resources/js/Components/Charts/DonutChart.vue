<script setup>
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { chartPalette } from '@/composables/useChartPalette.js';

const props = defineProps({
    labels: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    height: { type: Number, default: 260 },
    colors: { type: Array, default: () => chartPalette },
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
