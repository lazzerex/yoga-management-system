<script setup>
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    series: { type: Array, default: () => [] },
    height: { type: Number, default: 300 },
});

const options = computed(() => ({
    chart: { type: 'heatmap', toolbar: { show: false }, fontFamily: 'inherit' },
    dataLabels: { enabled: false },
    stroke: { width: 2, colors: ['#ffffff'] },
    grid: { borderColor: '#e6ebf2' },
    xaxis: {
        type: 'category',
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#7b8798', fontSize: '11px' } },
    },
    yaxis: { labels: { style: { colors: '#7b8798', fontSize: '11px' } } },
    legend: { show: false },
    plotOptions: {
        heatmap: {
            radius: 3,
            colorScale: {
                ranges: [
                    { from: -1, to: 0, color: '#eef2f7', name: '0%' },
                    { from: 1, to: 49, color: '#cfe4d6' },
                    { from: 50, to: 74, color: '#95c9a8' },
                    { from: 75, to: 89, color: '#5faa77' },
                    { from: 90, to: 100, color: '#3e8b4e' },
                ],
            },
        },
    },
    tooltip: { y: { formatter: (value) => `${value}%` } },
}));
</script>

<template>
    <VueApexCharts type="heatmap" :height="height" :options="options" :series="series" />
</template>
