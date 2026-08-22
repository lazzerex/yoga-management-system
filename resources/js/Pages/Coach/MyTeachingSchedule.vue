<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    sessions: Array,
});

const dayShortKeys = [
    'dashboard.sundayShort',
    'dashboard.mondayShort',
    'dashboard.tuesdayShort',
    'dashboard.wednesdayShort',
    'dashboard.thursdayShort',
    'dashboard.fridayShort',
    'dashboard.saturdayShort',
];

// `students` count per slot needs `enrollments` (not built yet) — omitted rather than faked.
const teachingSchedule = computed(() => {
    const days = [];
    for (let i = 0; i < 7; i++) {
        const date = new Date();
        date.setDate(date.getDate() + i);
        const isoDate = date.toISOString().slice(0, 10);

        days.push({
            day: t(dayShortKeys[date.getDay()]),
            date: date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
            classes: props.sessions
                .filter((session) => session.session_date === isoDate)
                .map((session) => ({
                    time: session.start_time,
                    title: session.class_type_name,
                    branch: `${session.branch_name} · ${session.room_name}`,
                })),
        });
    }
    return days;
});

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
                                    <p class="ym-timetable-sub">{{ item.branch }}</p>
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