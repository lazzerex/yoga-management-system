<script setup>
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    value: { type: Number, default: 0 },
    label: { type: String, default: '' },
    height: { type: Number, default: 220 },
    color: { type: String, default: '#4f9f5f' },
});

const options = computed(() => ({
    chart: { type: 'radialBar', toolbar: { show: false }, fontFamily: 'inherit' },
    colors: [props.color],
    plotOptions: {
        radialBar: {
            hollow: { size: '62%' },
            track: { background: '#eef2f7' },
            dataLabels: {
                name: { fontSize: '12px', color: '#7b8798', offsetY: 22 },
                value: { fontSize: '1.5rem', fontWeight: 700, color: '#273447', offsetY: -12 },
            },
        },
    },
    labels: [props.label],
}));

const series = computed(() => [props.value]);
</script>

<template>
    <VueApexCharts type="radialBar" :height="height" :options="options" :series="series" />
</template>
