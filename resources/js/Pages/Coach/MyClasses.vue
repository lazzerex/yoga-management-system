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
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('coach.classesThisWeek') }}</p>
            <p class="ym-stat-value">{{ stats.classesThisWeek }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('coach.totalStudents') }}</p>
            <p class="ym-stat-value">{{ stats.totalStudents }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('coach.avgFillRate') }}</p>
            <p class="ym-stat-value">{{ stats.avgFillRate }}%</p>
        </div>
    </div>

    <div class="ym-pane">
        <div class="ym-pane-head">
            <div class="ym-pane-title-wrap">
                <i class="bi bi-easel ym-pane-icon" />
                <h2 class="ym-pane-title">{{ $t('coach.classRosterSummary') }}</h2>
            </div>
        </div>
        <div class="ym-pane-body">
            <div class="ym-table-wrap">
                <table class="ym-table">
                    <thead>
                        <tr>
                            <th class="ym-th">{{ $t('operations.class') }}</th>
                            <th class="ym-th">{{ $t('operations.branch') }}</th>
                            <th class="ym-th">{{ $t('operations.students') }}</th>
                            <th class="ym-th">{{ $t('coach.waitlist') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in classes" :key="item.id" class="ym-tr">
                            <td class="ym-td font-medium">{{ item.name }}</td>
                            <td class="ym-td">{{ item.branch }}</td>
                            <td class="ym-td">{{ item.students }}</td>
                            <td class="ym-td">{{ item.waitlist }}</td>
                        </tr>
                        <tr v-if="!classes.length">
                            <td class="ym-td text-neutral-500" colspan="4">{{ $t('coach.noClasses') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
