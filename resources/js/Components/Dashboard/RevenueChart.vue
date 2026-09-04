<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import BarChart from '@/Components/Charts/BarChart.vue';
import { formatVnd } from '@/composables/useMoney.js';

const props = defineProps({ data: { type: Object, required: true } });

const months = computed(() => props.data.months ?? []);
const isEmpty = computed(() => months.value.every((month) => month.amount === 0));
const categories = computed(() => months.value.map((month) => month.month));
const series = computed(() => [{ name: t('dashboard.revenue'), data: months.value.map((month) => month.amount) }]);

// Millions, so the axis stays readable next to VND amounts.
const axisFormatter = (value) => `${Math.round(value / 1000000)}M`;
</script>

<template>
    <p v-if="isEmpty" class="ym-card-note">{{ $t('dashboard.noRevenueYet') }}</p>
    <BarChart
        v-else
        :categories="categories"
        :series="series"
        :height="250"
        :formatter="axisFormatter"
        :tooltip-formatter="formatVnd"
    />
    <p v-if="!isEmpty" class="ym-card-note">{{ formatVnd(months[months.length - 1].amount) }} · {{ $t('dashboard.thisMonth') }}</p>
</template>
