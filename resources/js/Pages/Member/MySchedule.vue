<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const weeklySchedule = [
    {
        day: 'Mon',
        date: 'Apr 27',
        sessions: [
            { time: '06:45', title: 'Sunrise Mobility', meta: 'Westside · Coach Lina' },
            { time: '18:30', title: 'Evening Yin', meta: 'Downtown · Coach Ari' },
        ],
    },
    {
        day: 'Tue',
        date: 'Apr 28',
        sessions: [
            { time: '07:00', title: 'Power Core', meta: 'Riverside · Coach Daniel' },
        ],
    },
    {
        day: 'Wed',
        date: 'Apr 29',
        sessions: [
            { time: '18:30', title: 'Evening Yin', meta: 'Downtown · Coach Ari' },
        ],
    },
    {
        day: 'Thu',
        date: 'Apr 30',
        sessions: [
            { time: '07:00', title: 'Power Core', meta: 'Riverside · Coach Daniel' },
            { time: '20:00', title: 'Breathwork Lab', meta: 'Online · Coach Noah' },
        ],
    },
    {
        day: 'Fri',
        date: 'May 01',
        sessions: [
            { time: '17:45', title: 'Mobility Reset', meta: 'Downtown · Coach Lina' },
        ],
    },
    {
        day: 'Sat',
        date: 'May 02',
        sessions: [
            { time: '09:00', title: 'Weekend Flow', meta: 'Uptown · Coach Mia' },
        ],
    },
    {
        day: 'Sun',
        date: 'May 03',
        sessions: [],
    },
];

const highlights = [
    { title: 'Form correction checkpoint', meta: 'Tue · Power Core', type: 'Coach Note' },
    { title: 'Breathwork mini workshop', meta: 'Thu · Online room B', type: 'Workshop' },
    { title: 'Monthly mobility assessment', meta: 'Sat · Uptown', type: 'Assessment' },
];

const weeklyFocus = [
    { label: $t('member.totalSessionsPlanned'), note: $t('member.bookedAndConfirmed'), value: '7' },
    { label: $t('member.intensityBalance'), note: $t('member.highVsRecovery'), value: '3 : 4' },
    { label: $t('member.currentStreak'), note: $t('member.consecutiveWeeks'), value: '5 weeks' },
];
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
                                <div v-if="!day.sessions.length" class="ym-timetable-empty">—</div>
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