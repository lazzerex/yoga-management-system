<template>
    <AppLayout title="Homepage">
        <section class="ym-dashboard-grid">
            <article class="ym-surface ym-activity-panel">
                <header class="ym-panel-head">
                    <h2 class="ym-panel-title">My Activities</h2>
                    <span class="ym-panel-dot">...</span>
                </header>

                <div class="ym-activity-list">
                    <article v-for="activity in activities" :key="activity.title" class="ym-activity-item">
                        <a href="#" class="ym-activity-title">{{ activity.title }}</a>
                        <p class="ym-activity-meta">
                            <span
                                :class="[
                                    'ym-status-pill',
                                    activity.stateClass === 'pending' ? 'ym-status-pill--pending' : '',
                                    activity.stateClass === 'started' ? 'ym-status-pill--started' : '',
                                ]"
                            >
                                {{ activity.state }}
                            </span>
                            <span>{{ activity.when }}</span>
                            <span>{{ activity.context }}</span>
                        </p>
                    </article>
                </div>

                <a href="#" class="ym-show-more">Show more</a>
            </article>

            <div class="grid gap-4">
                <article class="ym-surface ym-calendar-wrap">
                    <header class="ym-calendar-header">
                        <h3 class="ym-calendar-title">Calendar / April 2026</h3>
                        <span class="ym-panel-dot">&lt; &gt;</span>
                    </header>

                    <div class="ym-calendar-grid">
                        <div v-for="day in weekDays" :key="day" class="ym-calendar-day-name">{{ day }}</div>

                        <div
                            v-for="cell in calendarCells"
                            :key="`${cell.date}-${cell.muted ? 'm' : 'a'}`"
                            :class="['ym-calendar-cell', { 'ym-calendar-cell--muted': cell.muted }]"
                        >
                            <span class="ym-calendar-date">{{ cell.date }}</span>
                            <span
                                v-for="event in cell.events"
                                :key="event.text"
                                :class="['ym-event-chip', event.colorClass]"
                            >
                                {{ event.text }}
                            </span>
                        </div>
                    </div>
                </article>

                <div class="ym-grid-2">
                    <article class="ym-surface ym-section">
                        <div class="ym-panel-head !px-0 !pt-0 !pb-3 !border-b-0">
                            <h3 class="ym-panel-title">My Cases</h3>
                            <span class="ym-panel-dot">...</span>
                        </div>

                        <div class="ym-case-list">
                            <article v-for="caseItem in cases" :key="caseItem.id" class="ym-case-row">
                                <span class="ym-case-id">{{ caseItem.id }}</span>
                                <div>
                                    <p class="ym-case-name">{{ caseItem.title }}</p>
                                    <p class="ym-case-meta">
                                        <span :class="['ym-status-pill', caseItem.priority === 'High' ? 'ym-status-pill--pending' : '']">
                                            {{ caseItem.priority }}
                                        </span>
                                        <span>{{ caseItem.type }}</span>
                                        <span>{{ caseItem.customer }}</span>
                                    </p>
                                </div>
                            </article>
                        </div>
                    </article>

                    <article class="ym-surface ym-section">
                        <h3 class="ym-panel-title">Opportunities by Lead Source</h3>
                        <div class="ym-pie-wrap">
                            <div class="ym-pie" role="img" aria-label="Lead source distribution chart" />
                            <div class="ym-legend">
                                <div v-for="item in leadSources" :key="item.name" class="ym-legend-item">
                                    <span class="ym-legend-dot" :style="{ background: item.color }" />
                                    <span>{{ item.name }} ({{ item.value }}%)</span>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../Layouts/AppLayout.vue';

const activities = [
    {
        title: 'Handling trial-class schedules for this week',
        state: 'Not Started',
        stateClass: 'pending',
        when: 'Apr 20 11:00',
        context: 'Downtown Studio',
    },
    {
        title: 'Analyze attendance drop in evening classes',
        state: 'Planned',
        stateClass: 'default',
        when: 'Apr 21',
        context: 'Weekly review',
    },
    {
        title: 'Send monthly updates to management',
        state: 'Planned',
        stateClass: 'default',
        when: 'Apr 22 16:30',
        context: 'Head office',
    },
    {
        title: 'Prepare kids yoga class onboarding pack',
        state: 'Started',
        stateClass: 'started',
        when: 'Apr 23',
        context: 'Uptown Branch',
    },
    {
        title: 'Review teacher substitution requests',
        state: 'Not Started',
        stateClass: 'pending',
        when: 'Apr 24',
        context: 'Staffing board',
    },
];

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const calendarCells = [
    { date: '29', muted: true, events: [] },
    { date: '30', muted: true, events: [] },
    { date: '31', muted: true, events: [] },
    { date: '1', muted: false, events: [] },
    { date: '2', muted: false, events: [] },
    { date: '3', muted: false, events: [] },
    { date: '4', muted: false, events: [] },
    {
        date: '5',
        muted: false,
        events: [
            { text: '10:00 Discussion offe...', colorClass: 'ym-event-chip--rose' },
        ],
    },
    { date: '6', muted: false, events: [] },
    { date: '7', muted: false, events: [] },
    {
        date: '8',
        muted: false,
        events: [
            { text: '08:15 Price discuss...', colorClass: 'ym-event-chip--rose' },
            { text: '10:30 Proposal rev...', colorClass: 'ym-event-chip--rose' },
        ],
    },
    {
        date: '9',
        muted: false,
        events: [
            { text: 'Handing trial class', colorClass: 'ym-event-chip--green' },
            { text: '16:15 Team call', colorClass: 'ym-event-chip--blue' },
        ],
    },
    { date: '10', muted: false, events: [] },
    {
        date: '11',
        muted: false,
        events: [
            { text: 'Analyze attendance', colorClass: 'ym-event-chip--green' },
            { text: '10:30 Mgmt sync', colorClass: 'ym-event-chip--blue' },
        ],
    },
];

const cases = [
    { id: 11, title: 'Asking for compensation', priority: 'High', type: 'Problem', customer: 'Lotus Branch' },
    { id: 7, title: 'Discount issue', priority: 'Normal', type: 'Incident', customer: 'Westside Studio' },
    { id: 6, title: 'Delivery status check', priority: 'Low', type: 'Question', customer: 'Riverside Branch' },
    { id: 5, title: 'Product support question', priority: 'Normal', type: 'Question', customer: 'Downtown Studio' },
];

const leadSources = [
    { name: 'Call', value: 20, color: '#5d8fc0' },
    { name: 'Email', value: 32, color: '#4968a8' },
    { name: 'Existing Customer', value: 16, color: '#e2bb4e' },
    { name: 'Public Relations', value: 6, color: '#e47d61' },
    { name: 'Website', value: 22, color: '#78bb9d' },
    { name: 'Campaign', value: 4, color: '#8d7bc9' },
];
</script>