<template>
    <AppLayout title="My Schedule">
        <section class="ym-surface ym-section ym-reveal">
            <h2 class="ym-title">Personal Weekly Calendar</h2>
            <p class="ym-subtitle">A week-at-a-glance view of your booked sessions, reminders, and personal practice blocks.</p>

            <div class="ym-week-grid">
                <article
                    v-for="(day, index) in weeklySchedule"
                    :key="day.day"
                    class="ym-week-day ym-stagger-item"
                    :style="{ '--ym-stagger': `${index * 60}ms` }"
                >
                    <header class="ym-week-day-head">
                        <p class="ym-week-day-name">{{ day.day }}</p>
                        <p class="ym-week-day-date">{{ day.date }}</p>
                    </header>

                    <div v-if="day.sessions.length" class="ym-week-day-slots">
                        <div v-for="session in day.sessions" :key="session.title" class="ym-week-slot">
                            <p class="ym-week-slot-time">{{ session.time }}</p>
                            <p class="ym-week-slot-title">{{ session.title }}</p>
                            <p class="ym-week-slot-meta">{{ session.meta }}</p>
                        </div>
                    </div>

                    <p v-else class="ym-empty-slot">No scheduled sessions</p>
                </article>
            </div>
        </section>

        <section class="ym-grid-split mt-4">
            <article class="ym-surface ym-section ym-reveal ym-reveal-delay-1">
                <h3 class="ym-subsection-title">Upcoming Highlights</h3>
                <div class="ym-list">
                    <div
                        v-for="(highlight, index) in highlights"
                        :key="highlight.title"
                        class="ym-list-item ym-stagger-item"
                        :style="{ '--ym-stagger': `${index * 70}ms` }"
                    >
                        <div>
                            <strong>{{ highlight.title }}</strong>
                            <p class="ym-list-meta">{{ highlight.meta }}</p>
                        </div>
                        <span class="ym-tag">{{ highlight.type }}</span>
                    </div>
                </div>
            </article>

            <article class="ym-surface ym-section ym-reveal ym-reveal-delay-2">
                <h3 class="ym-subsection-title">Weekly Focus</h3>
                <div class="ym-list">
                    <div v-for="focus in weeklyFocus" :key="focus.label" class="ym-list-item">
                        <div>
                            <strong>{{ focus.label }}</strong>
                            <p class="ym-list-meta">{{ focus.note }}</p>
                        </div>
                        <span class="ym-chip">{{ focus.value }}</span>
                    </div>
                </div>
                <p class="ym-note-banner mt-3">
                    Keep at least one recovery day between high-intensity sessions for better consistency.
                </p>
            </article>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

const weeklySchedule = [
    {
        day: 'Monday',
        date: 'Apr 27',
        sessions: [
            { time: '06:45', title: 'Sunrise Mobility', meta: 'Westside · Coach Lina' },
            { time: '18:30', title: 'Evening Yin', meta: 'Downtown · Coach Ari' },
        ],
    },
    {
        day: 'Tuesday',
        date: 'Apr 28',
        sessions: [
            { time: '07:00', title: 'Power Core', meta: 'Riverside · Coach Daniel' },
        ],
    },
    {
        day: 'Wednesday',
        date: 'Apr 29',
        sessions: [
            { time: '18:30', title: 'Evening Yin', meta: 'Downtown · Coach Ari' },
        ],
    },
    {
        day: 'Thursday',
        date: 'Apr 30',
        sessions: [
            { time: '07:00', title: 'Power Core', meta: 'Riverside · Coach Daniel' },
            { time: '20:00', title: 'Breathwork Lab', meta: 'Online · Coach Noah' },
        ],
    },
    {
        day: 'Friday',
        date: 'May 01',
        sessions: [
            { time: '17:45', title: 'Mobility Reset', meta: 'Downtown · Coach Lina' },
        ],
    },
    {
        day: 'Saturday',
        date: 'May 02',
        sessions: [
            { time: '09:00', title: 'Weekend Flow', meta: 'Uptown · Coach Mia' },
        ],
    },
    {
        day: 'Sunday',
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
    { label: 'Total Sessions Planned', note: 'Booked and confirmed sessions', value: '7' },
    { label: 'Intensity Balance', note: 'High vs recovery sessions', value: '3 : 4' },
    { label: 'Current Streak', note: 'Consecutive active weeks', value: '5 weeks' },
];
</script>
