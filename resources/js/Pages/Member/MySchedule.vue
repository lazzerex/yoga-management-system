<script setup>
import { computed } from 'vue';
import { trans as t, getActiveLanguage } from 'laravel-vue-i18n';

const props = defineProps({
    sessions: Array,
});

const locale = computed(() => (getActiveLanguage() === 'vi' ? 'vi-VN' : 'en-US'));

const dayShortKeys = [
    'dashboard.sundayShort', 'dashboard.mondayShort', 'dashboard.tuesdayShort', 'dashboard.wednesdayShort',
    'dashboard.thursdayShort', 'dashboard.fridayShort', 'dashboard.saturdayShort',
];

const ymd = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const weeklySchedule = computed(() => {
    const days = [];
    for (let i = 0; i < 7; i++) {
        const date = new Date();
        date.setDate(date.getDate() + i);
        const isoDate = ymd(date);

        days.push({
            day: t(dayShortKeys[date.getDay()]),
            date: date.toLocaleDateString(locale.value, { month: 'short', day: 'numeric' }),
            sessions: props.sessions
                .filter((session) => session.session_date === isoDate)
                .map((session) => ({
                    time: `${session.start_time}–${session.end_time}`,
                    title: session.class_type_name,
                    meta: `${session.branch_name} · ${session.room_name}`,
                })),
        });
    }
    return days;
});

const nextSession = computed(() => props.sessions[0] ?? null);
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('member.mySchedule') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('member.personalWeeklyCalendar') }}</h1>
            </div>
        </header>

        <div class="ym-stats">
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('member.totalSessionsPlanned') }}</p>
                <p class="ym-stat-card-value">{{ sessions.length }}</p>
                <p class="ym-stat-card-note">{{ $t('member.bookedAndConfirmed') }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--info">
                <p class="ym-stat-card-label">{{ $t('member.nextSession') }}</p>
                <p class="ym-stat-card-value">{{ nextSession ? `${nextSession.start_time}` : '—' }}</p>
                <p v-if="nextSession" class="ym-stat-card-note">{{ nextSession.class_type_name }}</p>
            </div>
        </div>

        <section class="ym-card">
            <div v-if="sessions.length" class="ym-card-body ym-timetable-scroll">
                <div class="ym-timetable">
                    <div v-for="day in weeklySchedule" :key="day.day" class="ym-timetable-col">
                        <div class="ym-timetable-head">
                            <p class="ym-timetable-day">{{ day.day }}</p>
                            <p class="ym-timetable-date">{{ day.date }}</p>
                        </div>
                        <div class="ym-timetable-body">
                            <div
                                v-for="session in day.sessions"
                                :key="session.title"
                                class="ym-timetable-slot"
                            >
                                <p class="ym-timetable-time">{{ session.time }}</p>
                                <p class="ym-timetable-name">{{ session.title }}</p>
                                <p class="ym-timetable-sub">{{ session.meta }}</p>
                            </div>
                            <div v-if="!day.sessions.length" class="ym-timetable-empty">&mdash;</div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="ym-empty">
                <i class="bi bi-calendar-week" />
                <p>{{ $t('member.noClasses') }}</p>
            </div>
        </section>
    </div>
</template>
