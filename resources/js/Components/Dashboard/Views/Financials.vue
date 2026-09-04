<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import BarChart from '@/Components/Charts/BarChart.vue';
import DonutChart from '@/Components/Charts/DonutChart.vue';
import { formatVnd } from '@/composables/useMoney.js';

const props = defineProps({ data: { type: Object, required: true } });

const revenue = computed(() => props.data.revenue);
const ageing = computed(() => props.data.ageing);
const topPlans = computed(() => props.data.topPlans);
const methods = computed(() => props.data.methodSplit.filter((entry) => entry.amount > 0));
const methodShare = computed(() => {
    const total = methods.value.reduce((sum, entry) => sum + entry.amount, 0);
    const cash = methods.value.find((entry) => entry.method === 'cash')?.amount ?? 0;

    return total > 0 ? Math.round((cash / total) * 100) : 0;
});
const yearTotal = computed(() => revenue.value.reduce((sum, month) => sum + month.amount, 0));
const outstanding = computed(() => ageing.value.reduce((sum, bucket) => sum + bucket.amount, 0));
const millions = (value) => `${Math.round(value / 1000000)}M`;
</script>

<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.revenueWindow', { months: data.months }) }}</p>
            <p class="ym-stat-value">{{ formatVnd(yearTotal) }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.outstanding') }}</p>
            <p class="ym-stat-value">{{ formatVnd(outstanding) }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.voidedThisMonth') }}</p>
            <p class="ym-stat-value">{{ data.voidedThisMonth }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.paidInCash') }}</p>
            <p class="ym-stat-value">{{ methodShare }}%</p>
            <p class="ym-stat-note">{{ $t('dashboard.restByTransfer') }}</p>
        </div>
    </div>

    <div class="ym-analytics-grid mt-3">
        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-8">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.revenueByMonth') }}</h2>
            </header>
            <div class="ym-section">
                <BarChart
                    :categories="revenue.map((month) => month.month)"
                    :series="[{ name: t('dashboard.revenue'), data: revenue.map((month) => month.amount) }]"
                    :formatter="millions"
                    :tooltip-formatter="formatVnd"
                />
            </div>
        </section>

        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-4">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.revenueByPlan') }}</h2>
            </header>
            <div class="ym-section">
                <p v-if="!topPlans.length" class="ym-card-note">{{ $t('dashboard.noPlansInvoicedYet') }}</p>
                <DonutChart
                    v-else
                    :labels="topPlans.map((plan) => plan.name)"
                    :series="topPlans.map((plan) => plan.amount)"
                    :formatter="formatVnd"
                    :height="300"
                />
            </div>
        </section>

        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-6">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.overdueAgeing') }}</h2>
            </header>
            <div class="ym-section">
                <BarChart
                    :categories="ageing.map((bucket) => t(`dashboard.ageing${bucket.label.charAt(0).toUpperCase()}${bucket.label.slice(1)}`))"
                    :series="[{ name: t('dashboard.outstanding'), data: ageing.map((bucket) => bucket.amount) }]"
                    :formatter="millions"
                    :tooltip-formatter="formatVnd"
                    color="#c28a3a"
                />
            </div>
        </section>

        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-6">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.invoicesByPlan') }}</h2>
            </header>
            <div class="ym-section">
                <p v-if="!topPlans.length" class="ym-card-note">{{ $t('dashboard.noPlansInvoicedYet') }}</p>
                <BarChart
                    v-else
                    horizontal
                    :categories="topPlans.map((plan) => plan.name)"
                    :series="[{ name: t('dashboard.invoicesCount'), data: topPlans.map((plan) => plan.count) }]"
                    color="#8469bf"
                />
            </div>
        </section>
    </div>
</template>
