<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';

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

const iso = (date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

const todayIso = iso(new Date());

const teachingSchedule = computed(() => {
    const days = [];
    for (let i = 0; i < 7; i++) {
        const date = new Date();
        date.setDate(date.getDate() + i);
        const isoDate = iso(date);

        days.push({
            day: t(dayShortKeys[date.getDay()]),
            date: date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
            classes: props.sessions
                .filter((session) => session.session_date === isoDate)
                .map((session) => ({
                    time: session.start_time,
                    title: session.class_type_name,
                    branch: `${session.branch_name} · ${session.room_name}`,
                    students: session.students,
                })),
        });
    }
    return days;
});

const todayClasses = computed(() => props.sessions.filter((session) => session.session_date === todayIso));

// The same seven days the timetable covers, so the two blocks always agree.
const weekTotals = computed(() => {
    const students = props.sessions.reduce((sum, session) => sum + (session.students ?? 0), 0);
    const branches = new Set(props.sessions.map((session) => session.branch_name));

    return {
        classes: props.sessions.length,
        students,
        branches: branches.size,
    };
});
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('coach.teachingSchedule') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <div class="ym-stats">
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('coach.classesThisWeek') }}</p>
                <p class="ym-stat-card-value">{{ weekTotals.classes }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--info">
                <p class="ym-stat-card-label">{{ $t('coach.totalStudents') }}</p>
                <p class="ym-stat-card-value">{{ weekTotals.students }}</p>
            </div>
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('operations.branches') }}</p>
                <p class="ym-stat-card-value">{{ weekTotals.branches }}</p>
            </div>
        </div>

        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('coach.weeklySchedule') }}</h1>
            </div>
        </header>

        <div class="ym-split">
            <section class="ym-card">
                <div class="ym-card-body ym-timetable-scroll">
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
                                    <p class="ym-timetable-sub">{{ $t('coach.studentsCount', { count: item.students }) }}</p>
                                </div>
                                <div v-if="!day.classes.length" class="ym-timetable-empty">&mdash;</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="ym-split-rail">
                <section class="ym-card ym-card--accent">
                    <div class="ym-card-head">
                        <h2 class="ym-card-title">{{ $t('coach.today') }}</h2>
                        <span v-if="todayClasses.length" class="ym-tag ym-tag--neutral">{{ todayClasses.length }}</span>
                    </div>

                    <ul v-if="todayClasses.length" class="ym-timeline">
                        <li v-for="session in todayClasses" :key="session.id ?? session.start_time" class="ym-timeline-item">
                            <span class="ym-timeline-mark"><i class="bi bi-clock" /></span>
                            <div class="ym-timeline-body">
                                <div class="ym-timeline-row">
                                    <span class="ym-timeline-amount">{{ session.class_type_name }}</span>
                                    <span class="ym-tag ym-tag--info ym-num">{{ session.start_time }}</span>
                                </div>
                                <p class="ym-timeline-meta">
                                    {{ session.branch_name }} · {{ session.room_name }}
                                    · {{ $t('coach.studentsCount', { count: session.students }) }}
                                </p>
                            </div>
                        </li>
                    </ul>

                    <div v-else class="ym-empty">
                        <i class="bi bi-cup-hot" />
                        <p>{{ $t('coach.noClasses') }}</p>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>
