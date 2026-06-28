<script setup>
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const { t } = useI18n();

const branches = [
    { name: 'Downtown Studio', address: '1 Main St', classes: 12 },
    { name: 'Westside Branch', address: '28 Lake Ave', classes: 9 },
    { name: 'Riverside Center', address: '19 River Blvd', classes: 7 },
    { name: 'Uptown Loft', address: '53 Summit Rd', classes: 6 },
];

const notices = [
    { title: 'Mat storage renovation', meta: 'Downtown Studio · Apr 22', priority: 'High' },
    { title: 'HVAC maintenance window', meta: 'Westside Branch · Apr 24', priority: 'Medium' },
    { title: 'Equipment restock', meta: 'All branches · Apr 26', priority: 'Normal' },
];
</script>

<template>
    <AppLayout :title="t('operations.yogaCenter')">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ t('operations.activeStudents') }}</p>
                <p class="ym-stat-value">486</p>
                <p class="ym-stat-note">+18 {{ t('operations.thisMonth') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ t('operations.classesToday') }}</p>
                <p class="ym-stat-value">34</p>
                <p class="ym-stat-note">5 {{ t('common.noData') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ t('operations.activeTeachers') }}</p>
                <p class="ym-stat-value">27</p>
                <p class="ym-stat-note">2 {{ t('common.noData') }}</p>
            </div>
        </div>

        <div class="ym-page-cols">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-building ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ t('operations.branchPerformance') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="branch in branches" :key="branch.name" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ branch.name }}</p>
                                <p class="ym-row-meta">{{ branch.address }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-chip">{{ branch.classes }} {{ t('operations.classes') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-exclamation-triangle ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ t('operations.facilityNotices') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="notice in notices" :key="notice.title" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ notice.title }}</p>
                                <p class="ym-row-meta">{{ notice.meta }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-tag">{{ notice.priority }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="ym-info-row">
                        <i class="bi bi-info-circle ym-info-icon" />
                        <span>{{ t('operations.placeholder') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>