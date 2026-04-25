<template>
    <AppLayout title="My Teaching Schedule">
        <section class="ym-surface ym-section ym-reveal">
            <h2 class="ym-title">Weekly Teaching Calendar</h2>
            <p class="ym-subtitle">Your upcoming teaching blocks, branch coverage, and class focus for each day.</p>

            <div class="ym-week-grid ym-week-grid--coach">
                <article
                    v-for="(day, index) in teachingSchedule"
                    :key="day.day"
                    class="ym-week-day ym-stagger-item"
                    :style="{ '--ym-stagger': `${index * 60}ms` }"
                >
                    <header class="ym-week-day-head">
                        <p class="ym-week-day-name">{{ day.day }}</p>
                        <p class="ym-week-day-date">{{ day.date }}</p>
                    </header>

                    <div v-if="day.classes.length" class="ym-week-day-slots">
                        <div v-for="item in day.classes" :key="item.title" class="ym-week-slot ym-week-slot--coach">
                            <p class="ym-week-slot-time">{{ item.time }}</p>
                            <p class="ym-week-slot-title">{{ item.title }}</p>
                            <p class="ym-week-slot-meta">{{ item.branch }} · {{ item.students }} students</p>
                        </div>
                    </div>

                    <p v-else class="ym-empty-slot">No classes assigned</p>
                </article>
            </div>
        </section>

        <section class="ym-grid-split mt-4">
            <article class="ym-surface ym-section ym-reveal ym-reveal-delay-1">
                <h3 class="ym-subsection-title">Today At A Glance</h3>
                <div class="ym-list">
                    <div v-for="item in todayHighlights" :key="item.title" class="ym-list-item">
                        <div>
                            <strong>{{ item.title }}</strong>
                            <p class="ym-list-meta">{{ item.meta }}</p>
                        </div>
                        <span class="ym-tag">{{ item.badge }}</span>
                    </div>
                </div>
            </article>

            <article class="ym-surface ym-section ym-reveal ym-reveal-delay-2">
                <h3 class="ym-subsection-title">Teaching Load</h3>
                <div class="ym-list">
                    <div v-for="load in teachingLoad" :key="load.label" class="ym-list-item">
                        <div>
                            <strong>{{ load.label }}</strong>
                            <p class="ym-list-meta">{{ load.note }}</p>
                        </div>
                        <span class="ym-chip">{{ load.value }}</span>
                    </div>
                </div>
            </article>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

const teachingSchedule = [
    {
        day: 'Monday',
        date: 'Apr 27',
        classes: [
            { time: '06:45', title: 'Sunrise Mobility', branch: 'Westside', students: 18 },
            { time: '18:30', title: 'Evening Yin', branch: 'Downtown', students: 21 },
        ],
    },
    {
        day: 'Tuesday',
        date: 'Apr 28',
        classes: [
            { time: '07:00', title: 'Power Core', branch: 'Riverside', students: 24 },
            { time: '19:30', title: 'Breathwork Lab', branch: 'Online', students: 32 },
        ],
    },
    {
        day: 'Wednesday',
        date: 'Apr 29',
        classes: [
            { time: '18:30', title: 'Evening Yin', branch: 'Downtown', students: 20 },
        ],
    },
    {
        day: 'Thursday',
        date: 'Apr 30',
        classes: [
            { time: '07:00', title: 'Power Core', branch: 'Riverside', students: 23 },
            { time: '12:30', title: 'Prenatal Flow', branch: 'Westside', students: 14 },
        ],
    },
    {
        day: 'Friday',
        date: 'May 01',
        classes: [
            { time: '17:45', title: 'Mobility Reset', branch: 'Downtown', students: 16 },
        ],
    },
    {
        day: 'Saturday',
        date: 'May 02',
        classes: [
            { time: '09:00', title: 'Weekend Flow', branch: 'Uptown', students: 19 },
        ],
    },
    {
        day: 'Sunday',
        date: 'May 03',
        classes: [],
    },
];

const todayHighlights = [
    { title: 'Evening Yin', meta: '18:30 · Downtown · 21 students', badge: 'Today' },
    { title: 'Post-class notes due', meta: 'Submit by 21:00 for attendance sync', badge: 'Reminder' },
    { title: 'Substitute request', meta: 'Backup coach for Saturday workshop', badge: 'Pending' },
];

const teachingLoad = [
    { label: 'Total Weekly Sessions', note: 'Scheduled classes this week', value: '14' },
    { label: 'Branch Coverage', note: 'Distinct branch locations', value: '4' },
    { label: 'Total Student Touchpoints', note: 'Projected attendance sum', value: '182' },
];
</script>
