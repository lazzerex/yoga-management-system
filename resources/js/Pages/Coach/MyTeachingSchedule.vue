<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const teachingSchedule = [
    {
        day: 'Mon',
        date: 'Apr 27',
        classes: [
            { time: '06:45', title: 'Sunrise Mobility', branch: 'Westside', students: t('coach.studentsCount', { count: 18 }) },
            { time: '18:30', title: 'Evening Yin', branch: 'Downtown', students: t('coach.studentsCount', { count: 21 }) },
        ],
    },
    {
        day: 'Tue',
        date: 'Apr 28',
        classes: [
            { time: '07:00', title: 'Power Core', branch: 'Riverside', students: t('coach.studentsCount', { count: 24 }) },
            { time: '19:30', title: 'Breathwork Lab', branch: 'Online', students: t('coach.studentsCount', { count: 32 }) },
        ],
    },
    {
        day: 'Wed',
        date: 'Apr 29',
        classes: [
            { time: '18:30', title: 'Evening Yin', branch: 'Downtown', students: t('coach.studentsCount', { count: 20 }) },
        ],
    },
    {
        day: 'Thu',
        date: 'Apr 30',
        classes: [
            { time: '07:00', title: 'Power Core', branch: 'Riverside', students: t('coach.studentsCount', { count: 23 }) },
            { time: '12:30', title: 'Prenatal Flow', branch: 'Westside', students: t('coach.studentsCount', { count: 14 }) },
        ],
    },
    {
        day: 'Fri',
        date: 'May 01',
        classes: [
            { time: '17:45', title: 'Mobility Reset', branch: 'Downtown', students: t('coach.studentsCount', { count: 16 }) },
        ],
    },
    {
        day: 'Sat',
        date: 'May 02',
        classes: [
            { time: '09:00', title: 'Weekend Flow', branch: 'Uptown', students: t('coach.studentsCount', { count: 19 }) },
        ],
    },
    {
        day: 'Sun',
        date: 'May 03',
        classes: [],
    },
];

const todayHighlights = computed(() => [
    { title: 'Evening Yin', meta: t('coach.insightNearCapacityMeta'), badge: t('coach.today') },
    { title: t('coach.postClassNotesDue'), meta: t('coach.postClassNotesDueMeta'), badge: t('coach.reminderBadge') },
    { title: t('coach.substituteRequest'), meta: t('coach.substituteRequestMeta'), badge: t('coach.followUp') },
]);

const teachingLoad = computed(() => [
    { label: t('coach.classesThisWeek'), note: t('coach.weeklySchedule'), value: '14' },
    { label: t('coach.totalHours'), note: t('coach.acrossBranches', { count: 4 }), value: t('coach.hours', { count: 18 }) },
    { label: t('coach.totalStudents'), note: t('coach.eventsThisWeek'), value: '182' },
]);
</script>

<template>
    <AppLayout :title="$t('coach.teachingSchedule')">
        <div class="ym-pane">
            <div class="ym-pane-head">
                <div class="ym-pane-title-wrap">
                    <i class="bi bi-calendar-week ym-pane-icon" />
                    <h2 class="ym-pane-title">{{ $t('coach.weeklySchedule') }}</h2>
                </div>
            </div>
            <div class="ym-pane-body">
                <div class="ym-timetable-scroll">
                    <div class="ym-timetable">
                        <div v-for="day in teachingSchedule" :key="day.day" class="ym-timetable-col">
                            <div class="ym-timetable-head">
                                <p class="ym-timetable-day">{{ day.day }}</p>
                                <p class="ym-timetable-date">{{ day.date }}</p>
                            </div>
                            <div class="ym-timetable-body">
                                <div
                                    v-for="item in day.classes"
                                    :key="item.title"
                                    class="ym-timetable-slot ym-timetable-slot--coach"
                                >
                                    <p class="ym-timetable-time">{{ item.time }}</p>
                                    <p class="ym-timetable-name">{{ item.title }}</p>
                                    <p class="ym-timetable-sub">{{ item.branch }} · {{ item.students }}</p>
                                </div>
                                <div v-if="!day.classes.length" class="ym-timetable-empty">&mdash;</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ym-page-cols mt-4">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-clipboard-data ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('coach.today') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="item in todayHighlights" :key="item.title" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ item.title }}</p>
                                <p class="ym-row-meta">{{ item.meta }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-tag">{{ item.badge }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-bar-chart ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('coach.thisWeek') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="load in teachingLoad" :key="load.label" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ load.label }}</p>
                                <p class="ym-row-meta">{{ load.note }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-chip">{{ load.value }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>