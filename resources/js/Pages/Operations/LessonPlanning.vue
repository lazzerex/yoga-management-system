<script setup>
import { Link } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';

defineProps({
    plans: Object,
    stats: Object,
    canManage: Boolean,
    endpoints: Object,
});

const statusLabel = (status) => t(`operations.status${status.charAt(0).toUpperCase()}${status.slice(1)}`);
const levelLabel = (level) => t(`operations.level${level.charAt(0).toUpperCase()}${level.slice(1)}`);
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.lessonPlans') }, () => page),
};
</script>


<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.statusDraft') }}</p>
            <p class="ym-stat-value">{{ stats.draft }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.statusPending') }}</p>
            <p class="ym-stat-value">{{ stats.pending }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.statusApproved') }}</p>
            <p class="ym-stat-value">{{ stats.approved }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.statusRejected') }}</p>
            <p class="ym-stat-value">{{ stats.rejected }}</p>
        </div>
    </div>

    <section class="ym-surface ym-section">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="ym-title">
                    {{ $t('operations.lessonPlans') }}
                    <span class="ym-count-badge">{{ plans.total }}</span>
                </h2>
                <p class="ym-subtitle">{{ $t('operations.lessonPlansSubtitle') }}</p>
            </div>
            <div class="ym-inline-actions">
                <Link v-if="endpoints.pending" :href="endpoints.pending" class="ym-btn-outline">
                    {{ $t('operations.approvalQueue') }}
                </Link>
                <Link v-if="endpoints.create" :href="endpoints.create" class="ym-btn-sm">
                    {{ $t('operations.createLessonPlan') }}
                </Link>
            </div>
        </div>

        <div class="ym-table-wrap">
            <table class="ym-table">
                <thead>
                    <tr>
                        <th class="ym-th">{{ $t('operations.planTitle') }}</th>
                        <th class="ym-th">{{ $t('operations.planClassType') }}</th>
                        <th class="ym-th">{{ $t('operations.planCoach') }}</th>
                        <th class="ym-th">{{ $t('operations.planBranch') }}</th>
                        <th class="ym-th">{{ $t('operations.planLevel') }}</th>
                        <th class="ym-th">{{ $t('operations.status') }}</th>
                        <th class="ym-th">{{ $t('operations.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="plan in plans.data" :key="plan.id" class="ym-tr">
                        <td class="ym-td font-medium">{{ plan.title }}</td>
                        <td class="ym-td text-neutral-500">{{ plan.class_type_name }}</td>
                        <td class="ym-td text-neutral-500">{{ plan.coach_name }}</td>
                        <td class="ym-td text-neutral-500">{{ plan.branch_name }}</td>
                        <td class="ym-td text-neutral-500">{{ levelLabel(plan.level) }}</td>
                        <td class="ym-td">
                            <span :class="['ym-plan-status', `ym-plan-status--${plan.status}`]">{{ statusLabel(plan.status) }}</span>
                        </td>
                        <td class="ym-td">
                            <Link class="ym-btn-outline" :href="plan.showUrl">{{ $t('operations.planDetails') }}</Link>
                        </td>
                    </tr>
                    <tr v-if="!plans.data.length">
                        <td class="ym-td text-neutral-500" colspan="7">{{ $t('operations.noLessonPlans') }}</td>
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
