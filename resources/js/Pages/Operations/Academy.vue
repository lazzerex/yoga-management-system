<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const classes = [
    { className: 'Morning Vinyasa', teacher: 'Mia Tran', branch: 'Downtown', capacity: '22 / 24' },
    { className: 'Prenatal Flow', teacher: 'Emily Rogers', branch: 'Westside', capacity: '12 / 16' },
    { className: 'Power Core', teacher: 'Daniel Park', branch: 'Riverside', capacity: '20 / 20' },
    { className: 'Evening Yin', teacher: 'Ari Gomez', branch: 'Uptown', capacity: '18 / 20' },
];

const assignments = [
    { title: 'Substitute for Evening Yin', meta: 'Uptown / Apr 22', status: 'Pending' },
    { title: 'New Intro to Ashtanga slot', meta: 'Downtown / Apr 25', status: 'Review' },
    { title: 'Weekend workshop staffing', meta: 'Westside / Apr 28', status: 'Planned' },
];
</script>

<template>
    <AppLayout :title="$t('operations.academy')">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.students') }}</p>
                <p class="ym-stat-value">486</p>
                <p class="ym-stat-note">72 {{ $t('operations.thisQuarter') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.classTemplates') }}</p>
                <p class="ym-stat-value">18</p>
                <p class="ym-stat-note">{{ $t('operations.beginnerToAdvanced') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.teachers') }}</p>
                <p class="ym-stat-value">27</p>
                <p class="ym-stat-note">21 {{ $t('operations.activeThisWeek') }}</p>
            </div>
        </div>

        <div class="ym-page-cols ym-page-cols--6040">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-people ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('operations.classSchedule') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-table-wrap">
                        <table class="ym-table">
                            <thead>
                                <tr>
                                    <th class="ym-th">{{ $t('operations.class') }}</th>
                                    <th class="ym-th">{{ $t('operations.teacher') }}</th>
                                    <th class="ym-th">{{ $t('operations.branch') }}</th>
                                    <th class="ym-th">{{ $t('operations.capacity') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in classes" :key="item.className" class="ym-tr">
                                    <td class="ym-td font-medium">{{ item.className }}</td>
                                    <td class="ym-td">{{ item.teacher }}</td>
                                    <td class="ym-td">{{ item.branch }}</td>
                                    <td class="ym-td">{{ item.capacity }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-person-check ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('operations.assignmentQueue') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="assignment in assignments" :key="assignment.title" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ assignment.title }}</p>
                                <p class="ym-row-meta">{{ assignment.meta }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-status-pill">{{ assignment.status }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="ym-info-row">
                        <i class="bi bi-info-circle ym-info-icon" />
                        <span>{{ $t('operations.academyPlaceholder') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>