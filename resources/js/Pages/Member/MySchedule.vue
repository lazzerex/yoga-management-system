<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faBellConcierge, faCalendarWeek, faChartSimple, faCircleInfo } from '@fortawesome/free-solid-svg-icons';
import AppLayout from '@/Layouts/AppLayout.vue';

const weeklySchedule = [
    {
        day: 'Mon',
        date: 'Apr 27',
        sessions: [
            { time: '06:45', title: 'Sunrise Mobility', meta: 'Westside Â· Coach Lina' },
            { time: '18:30', title: 'Evening Yin', meta: 'Downtown Â· Coach Ari' },
        ],
    },
    {
        day: 'Tue',
        date: 'Apr 28',
        sessions: [
            { time: '07:00', title: 'Power Core', meta: 'Riverside Â· Coach Daniel' },
        ],
    },
    {
        day: 'Wed',
        date: 'Apr 29',
        sessions: [
            { time: '18:30', title: 'Evening Yin', meta: 'Downtown Â· Coach Ari' },
        ],
    },
    {
        day: 'Thu',
        date: 'Apr 30',
        sessions: [
            { time: '07:00', title: 'Power Core', meta: 'Riverside Â· Coach Daniel' },
            { time: '20:00', title: 'Breathwork Lab', meta: 'Online Â· Coach Noah' },
        ],
    },
    {
        day: 'Fri',
        date: 'May 01',
        sessions: [
            { time: '17:45', title: 'Mobility Reset', meta: 'Downtown Â· Coach Lina' },
        ],
    },
    {
        day: 'Sat',
        date: 'May 02',
        sessions: [
            { time: '09:00', title: 'Weekend Flow', meta: 'Uptown Â· Coach Mia' },
        ],
    },
    {
        day: 'Sun',
        date: 'May 03',
        sessions: [],
    },
];

const highlights = [
    { title: 'Form correction checkpoint', meta: 'Tue Â· Power Core', type: 'Coach Note' },
    { title: 'Breathwork mini workshop', meta: 'Thu Â· Online room B', type: 'Workshop' },
    { title: 'Monthly mobility assessment', meta: 'Sat Â· Uptown', type: 'Assessment' },
];

const weeklyFocus = [
    { label: 'Total Sessions Planned', note: 'Booked and confirmed sessions', value: '7' },
    { label: 'Intensity Balance', note: 'High vs recovery sessions', value: '3 : 4' },
    { label: 'Current Streak', note: 'Consecutive active weeks', value: '5 weeks' },
];
</script>

<template>
    <AppLayout title="My Schedule">
        <div class="ym-pane">
            <div class="ym-pane-head">
                <div class="ym-pane-title-wrap">
                    <FontAwesomeIcon :icon="faCalendarWeek" class="ym-pane-icon" />
                    <h2 class="ym-pane-title">Personal Weekly Calendar</h2>
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
                                <div v-if="!day.sessions.length" class="ym-timetable-empty">â€”</div>
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
                        <FontAwesomeIcon :icon="faBellConcierge" class="ym-pane-icon" />
                        <h2 class="ym-pane-title">Upcoming Highlights</h2>
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
                        <FontAwesomeIcon :icon="faChartSimple" class="ym-pane-icon" />
                        <h2 class="ym-pane-title">Weekly Focus</h2>
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
                        <FontAwesomeIcon :icon="faCircleInfo" class="ym-info-icon" />
                        <span>Keep at least one recovery day between high-intensity sessions for better consistency.</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>