<script setup>
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    height: { type: Number, default: 240 },
    color: { type: String, default: '#4f9f5f' },
    horizontal: { type: Boolean, default: false },
    stacked: { type: Boolean, default: false },
    formatter: { type: Function, default: null },
    tooltipFormatter: { type: Function, default: null },
});

const options = computed(() => ({
    chart: {
        type: 'bar',
        stacked: props.stacked,
        toolbar: { show: false },
        fontFamily: 'inherit',
        animations: { speed: 320 },
    },
    colors: [props.color, '#5f83c2', '#c28a3a'],
    plotOptions: { bar: { horizontal: props.horizontal, borderRadius: 3, columnWidth: '55%' } },
    dataLabels: { enabled: false },
    grid: { borderColor: '#e6ebf2', strokeDashArray: 3 },
    xaxis: {
        categories: props.categories,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#7b8798', fontSize: '11px' } },
    },
    yaxis: {
        labels: {
            style: { colors: '#7b8798', fontSize: '11px' },
            formatter: props.formatter ?? undefined,
        },
    },
    legend: { show: props.series.length > 1, position: 'top', horizontalAlign: 'right', fontSize: '12px' },
    tooltip: { y: { formatter: props.tooltipFormatter ?? props.formatter ?? undefined } },
}));
</script>

<template>
    <VueApexCharts type="bar" :height="height" :options="options" :series="series" />
</template>
