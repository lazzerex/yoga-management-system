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
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">
                    {{ $t('operations.approvalQueue') }}
                    <span class="ym-count">{{ plans.total }}</span>
                </h1>
                <p class="ym-page-sub">{{ $t('operations.approvalQueueSubtitle') }}</p>
            </div>
            <div class="ym-page-actions">
                <Link :href="endpoints.index" class="ym-btn ym-btn--outline">{{ $t('operations.backToPlans') }}</Link>
            </div>
        </header>

        <section class="ym-card">
            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <th>{{ $t('operations.planTitle') }}</th>
                            <th>{{ $t('operations.planCoach') }}</th>
                            <th>{{ $t('operations.planClassType') }}</th>
                            <th>{{ $t('operations.planLevel') }}</th>
                            <th>{{ $t('operations.planSubmittedAt') }}</th>
                            <th class="is-actions">{{ $t('operations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="plan in plans.data" :key="plan.id">
                            <td class="is-strong">{{ plan.title }}</td>
                            <td class="is-muted">{{ plan.coach_name }}</td>
                            <td class="is-muted">{{ plan.class_type_name }}</td>
                            <td class="is-muted">{{ levelLabel(plan.level) }}</td>
                            <td class="is-muted ym-num">{{ waitingSince(plan.submitted_at) }}</td>
                            <td class="is-actions">
                                <div class="ym-row-actions">
                                    <Link class="ym-btn ym-btn--primary ym-btn--sm" :href="plan.showUrl">
                                        {{ $t('operations.reviewDecision') }}
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!plans.data.length" class="ym-empty">
                <i class="bi bi-check2-circle" />
                <p>{{ $t('operations.noPendingPlans') }}</p>
            </div>

            <div v-if="plans.links.length > 3" class="ym-card-foot">
                <div class="ym-pagination">
                    <Link
                        v-for="link in plans.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                        preserve-scroll
                    />
                </div>
            </div>
        </section>
    </div>
</template>
