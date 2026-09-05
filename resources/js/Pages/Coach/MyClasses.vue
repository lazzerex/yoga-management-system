<script setup>

const props = defineProps({
    classes: Array,
    stats: Object,
});
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('coach.myClasses') }, () => page),
};
</script>


<template>
    <div class="ym-ui">
        <div class="ym-stats">
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('coach.classesThisWeek') }}</p>
                <p class="ym-stat-card-value">{{ stats.classesThisWeek }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--info">
                <p class="ym-stat-card-label">{{ $t('coach.totalStudents') }}</p>
                <p class="ym-stat-card-value">{{ stats.totalStudents }}</p>
            </div>
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('coach.avgFillRate') }}</p>
                <p class="ym-stat-card-value">{{ stats.avgFillRate }}%</p>
            </div>
        </div>

        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('coach.classRosterSummary') }}</h1>
            </div>
        </header>

        <section class="ym-card">
            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <th>{{ $t('operations.class') }}</th>
                            <th>{{ $t('operations.branch') }}</th>
                            <th class="is-num">{{ $t('operations.students') }}</th>
                            <th class="is-num">{{ $t('coach.waitlist') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in classes" :key="item.id">
                            <td class="is-strong">{{ item.name }}</td>
                            <td class="is-muted">{{ item.branch }}</td>
                            <td class="is-num">{{ item.students }}</td>
                            <td class="is-num is-muted">{{ item.waitlist }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!classes.length" class="ym-empty">
                <i class="bi bi-easel" />
                <p>{{ $t('coach.noClasses') }}</p>
            </div>
        </section>
    </div>
</template>
