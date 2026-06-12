<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCircleInfo, faUserCheck, faUsers } from '@fortawesome/free-solid-svg-icons';
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
    <AppLayout title="Students, Classes & Teachers">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">Students</p>
                <p class="ym-stat-value">486</p>
                <p class="ym-stat-note">72 new this quarter</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Class Templates</p>
                <p class="ym-stat-value">18</p>
                <p class="ym-stat-note">Beginner to advanced</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Teachers</p>
                <p class="ym-stat-value">27</p>
                <p class="ym-stat-note">21 active this week</p>
            </div>
        </div>

        <div class="ym-page-cols ym-page-cols--6040">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <FontAwesomeIcon :icon="faUsers" class="ym-pane-icon" />
                        <h2 class="ym-pane-title">Class Schedule</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-table-wrap">
                        <table class="ym-table">
                            <thead>
                                <tr>
                                    <th class="ym-th">Class</th>
                                    <th class="ym-th">Teacher</th>
                                    <th class="ym-th">Branch</th>
                                    <th class="ym-th">Capacity</th>
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
                        <FontAwesomeIcon :icon="faUserCheck" class="ym-pane-icon" />
                        <h2 class="ym-pane-title">Assignment Queue</h2>
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
                        <FontAwesomeIcon :icon="faCircleInfo" class="ym-info-icon" />
                        <span>Assignment actions will be supported in a later phase.</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>