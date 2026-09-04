<script setup>
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    height: { type: Number, default: 260 },
    colors: { type: Array, default: () => ['#4f9f5f', '#5f83c2', '#c28a3a'] },
    formatter: { type: Function, default: null },
});

const options = computed(() => ({
    chart: { type: 'line', toolbar: { show: false }, fontFamily: 'inherit', zoom: { enabled: false } },
    colors: props.colors,
    stroke: { curve: 'smooth', width: 2.5 },
    markers: { size: 3 },
    dataLabels: { enabled: false },
    grid: { borderColor: '#e6ebf2', strokeDashArray: 3 },
    xaxis: {
        categories: props.categories,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#7b8798', fontSize: '11px' } },
    },
    yaxis: {
        labels: { style: { colors: '#7b8798', fontSize: '11px' }, formatter: props.formatter ?? undefined },
    },
    legend: { show: props.series.length > 1, position: 'top', horizontalAlign: 'right', fontSize: '12px' },
}));
</script>

<template>
    <VueApexCharts type="line" :height="height" :options="options" :series="series" />
</template>
