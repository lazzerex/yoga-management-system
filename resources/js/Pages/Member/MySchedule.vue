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

const weeklySchedule = computed(() => {
    const days = [];
    for (let i = 0; i < 7; i++) {
        const date = new Date();
        date.setDate(date.getDate() + i);
        const isoDate = date.toISOString().slice(0, 10);

        days.push({
            day: t(dayShortKeys[date.getDay()]),
            date: date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
            sessions: props.sessions
                .filter((session) => session.session_date === isoDate)
                .map((session) => ({
                    time: session.start_time,
                    title: session.class_type_name,
                    meta: `${session.branch_name} · ${session.room_name}`,
                })),
        });
    }
    return days;
});

const highlights = computed(() => [
    { title: t('member.scheduleHighlightForm'), meta: t('member.scheduleHighlightFormMeta'), type: t('member.coachNoteLabel') },
    { title: t('member.scheduleHighlightBreathwork'), meta: t('member.scheduleHighlightBreathworkMeta'), type: t('member.workshopLabel') },
    { title: t('member.scheduleHighlightAssessment'), meta: t('member.scheduleHighlightAssessmentMeta'), type: t('member.assessmentLabel') },
]);

const weeklyFocus = computed(() => [
    { label: t('member.totalSessionsPlanned'), note: t('member.bookedAndConfirmed'), value: '7' },
    { label: t('member.intensityBalance'), note: t('member.highVsRecovery'), value: '3 : 4' },
    { label: t('member.currentStreak'), note: t('member.consecutiveWeeks'), value: t('member.fiveWeeks') },
]);
</script>

<template>
    <AppLayout :title="$t('member.mySchedule')">
        <div class="ym-pane">
            <div class="ym-pane-head">
                <div class="ym-pane-title-wrap">
                    <i class="bi bi-calendar-week ym-pane-icon" />
                    <h2 class="ym-pane-title">{{ $t('member.personalWeeklyCalendar') }}</h2>
                </div>
            </div>
            <div class="ym-pane-body">
                <div class="ym-timetable-scroll">
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
            </div>
        </div>

        <div class="ym-page-cols mt-4">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-bell ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('member.upcomingHighlights') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="highlight in highlights" :key="highlight.title" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ highlight.title }}</p>
                                <p class="ym-row-meta">{{ highlight.meta }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-tag">{{ highlight.type }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-bar-chart ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('member.weeklyFocus') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="focus in weeklyFocus" :key="focus.label" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ focus.label }}</p>
                                <p class="ym-row-meta">{{ focus.note }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-chip">{{ focus.value }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="ym-info-row">
                        <i class="bi bi-info-circle ym-info-icon" />
                        <span>{{ $t('member.recoveryTip') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>