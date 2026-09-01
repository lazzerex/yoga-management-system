<script setup>
import { Link } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';

defineProps({
    plans: Object,
    endpoints: Object,
});

const levelLabel = (level) => t(`operations.level${level.charAt(0).toUpperCase()}${level.slice(1)}`);

const waitingSince = (value) => (value ? new Date(value).toLocaleDateString() : '-');
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.approvalQueue') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="ym-title">
                    {{ $t('operations.approvalQueue') }}
                    <span class="ym-count-badge">{{ plans.total }}</span>
                </h2>
                <p class="ym-subtitle">{{ $t('operations.approvalQueueSubtitle') }}</p>
            </div>
            <Link :href="endpoints.index" class="ym-btn-outline">{{ $t('operations.backToPlans') }}</Link>
        </div>

        <div class="ym-table-wrap">
            <table class="ym-table">
                <thead>
                    <tr>
                        <th class="ym-th">{{ $t('operations.planTitle') }}</th>
                        <th class="ym-th">{{ $t('operations.planCoach') }}</th>
                        <th class="ym-th">{{ $t('operations.planClassType') }}</th>
                        <th class="ym-th">{{ $t('operations.planLevel') }}</th>
                        <th class="ym-th">{{ $t('operations.planSubmittedAt') }}</th>
                        <th class="ym-th">{{ $t('operations.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="plan in plans.data" :key="plan.id" class="ym-tr">
                        <td class="ym-td font-medium">{{ plan.title }}</td>
                        <td class="ym-td text-neutral-500">{{ plan.coach_name }}</td>
                        <td class="ym-td text-neutral-500">{{ plan.class_type_name }}</td>
                        <td class="ym-td text-neutral-500">{{ levelLabel(plan.level) }}</td>
                        <td class="ym-td text-neutral-500">{{ waitingSince(plan.submitted_at) }}</td>
                        <td class="ym-td">
                            <Link class="ym-btn-outline" :href="plan.showUrl">{{ $t('operations.reviewDecision') }}</Link>
                        </td>
                    </tr>
                    <tr v-if="!plans.data.length">
                        <td class="ym-td text-neutral-500" colspan="6">{{ $t('operations.noPendingPlans') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="plans.links.length > 3" class="ym-pagination">
            <Link
                v-for="link in plans.links"
                :key="link.label"
                :href="link.url ?? '#'"
                v-html="link.label"
                :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                preserve-scroll
            />
        </div>
    </section>
</template>
