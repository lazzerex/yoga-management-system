<template>
    <AppLayout :title="pageTitle">
        <template v-if="isAdmin">
            <section :class="['ym-dashlet-grid', { 'ym-dashlet-grid--dragging': isDraggingDashlet }]">
                <Draggable
                    v-model="dashlets"
                    item-key="id"
                    tag="div"
                    class="ym-dashlet-grid-inner"
                    handle=".ym-drag-handle"
                    ghost-class="ym-dashlet-card--placeholder"
                    chosen-class="ym-dashlet-card--dragging"
                    drag-class="ym-dashlet-card--sorting"
                    :animation="240"
                    :disabled="lockDashboard"
                    @start="handleDashletSortStart"
                    @end="handleDashletSortEnd"
                >
                    <template #item="{ element: dashlet }">
                        <article :class="dashletClasses(dashlet)">
                            <header class="ym-panel-head">
                                <div class="ym-dashlet-head">
                                    <span class="ym-drag-handle" aria-hidden="true">
                                        <FontAwesomeIcon :icon="faGripVertical" />
                                    </span>
                                    <h2 class="ym-panel-title">{{ dashlet.title }}</h2>
                                </div>

                                <button
                                    type="button"
                                    class="ym-dashlet-remove"
                                    :disabled="lockDashboard"
                                    aria-label="Remove dashlet"
                                    @click="removeDashlet(dashlet.id)"
                                >
                                    <FontAwesomeIcon :icon="faXmark" />
                                </button>
                            </header>

                            <div v-if="dashlet.type === 'activities'" class="ym-activity-list">
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
                                <a href="#" class="ym-show-more">Show more</a>
                            </div>

                            <div v-else-if="dashlet.type === 'calendar'">
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
                            </div>

                            <div v-else-if="dashlet.type === 'cases'" class="ym-section">
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
                            </div>

                            <div v-else-if="dashlet.type === 'lead-source'" class="ym-section">
                                <div class="ym-pie-wrap">
                                    <div class="ym-pie" role="img" aria-label="Lead source distribution chart" />
                                    <div class="ym-legend">
                                        <div v-for="item in leadSources" :key="item.name" class="ym-legend-item">
                                            <span class="ym-legend-dot" :style="{ background: item.color }" />
                                            <span>{{ item.name }} ({{ item.value }}%)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="dashlet.type === 'memo'" class="ym-section">
                                <p class="ym-card-note">Keep short reminders visible to your team directly from this dashboard card.</p>
                                <div class="ym-note-banner">
                                    Team reminder: bring April referral stats to Monday leadership sync.
                                </div>
                            </div>

                            <div v-else class="ym-section">
                                <p class="ym-card-note">{{ dashlet.description }}</p>
                                <div class="ym-list">
                                    <div v-for="item in dashlet.previewRows" :key="item" class="ym-list-item">
                                        <span>{{ item }}</span>
                                        <span class="ym-list-meta">Preview</span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </template>
                </Draggable>
            </section>
        </template>

        <template v-else>
            <section class="ym-surface ym-section ym-reveal">
                <h2 class="ym-title">{{ dashboardConfig.headline }}</h2>
                <p class="ym-subtitle">{{ dashboardConfig.subtitle }}</p>

                <p v-if="dashboardNotice" class="ym-note-banner mt-3">
                    {{ dashboardNotice }}
                </p>

                <div class="ym-kpi-grid">
                    <article
                        v-for="(metric, index) in dashboardConfig.metrics"
                        :key="metric.label"
                        class="ym-kpi-card ym-stagger-item"
                        :style="{ '--ym-stagger': `${index * 70}ms` }"
                    >
                        <p class="ym-kpi-label">{{ metric.label }}</p>
                        <p class="ym-kpi-value">{{ metric.value }}</p>
                        <p class="ym-kpi-note">{{ metric.note }}</p>
                    </article>
                </div>
            </section>

            <section class="ym-grid-split mt-4">
                <article class="ym-surface ym-section ym-reveal ym-reveal-delay-1">
                    <h3 class="ym-subsection-title">{{ dashboardConfig.primaryPanel.title }}</h3>
                    <div class="ym-list">
                        <div
                            v-for="(item, index) in dashboardConfig.primaryPanel.rows"
                            :key="`${item.title}-${item.badge}`"
                            class="ym-list-item ym-stagger-item"
                            :style="{ '--ym-stagger': `${index * 70}ms` }"
                        >
                            <div>
                                <strong>{{ item.title }}</strong>
                                <p class="ym-list-meta">{{ item.meta }}</p>
                            </div>
                            <span :class="badgeClass(item.tone)">{{ item.badge }}</span>
                        </div>
                    </div>
                </article>

                <article class="ym-surface ym-section ym-reveal ym-reveal-delay-2">
                    <h3 class="ym-subsection-title">{{ dashboardConfig.secondaryPanel.title }}</h3>
                    <div class="ym-list">
                        <div
                            v-for="(item, index) in dashboardConfig.secondaryPanel.rows"
                            :key="`${item.title}-${item.badge}`"
                            class="ym-list-item ym-stagger-item"
                            :style="{ '--ym-stagger': `${index * 70}ms` }"
                        >
                            <div>
                                <strong>{{ item.title }}</strong>
                                <p class="ym-list-meta">{{ item.meta }}</p>
                            </div>
                            <span :class="badgeClass(item.tone)">{{ item.badge }}</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="ym-surface ym-section mt-4 ym-reveal ym-reveal-delay-3">
                <h3 class="ym-subsection-title">{{ dashboardConfig.weeklyPanel.title }}</h3>

                <div class="ym-week-grid">
                    <article
                        v-for="(day, index) in dashboardConfig.weeklyPanel.days"
                        :key="`${day.day}-${day.date}`"
                        class="ym-week-day ym-stagger-item"
                        :style="{ '--ym-stagger': `${index * 55}ms` }"
                    >
                        <header class="ym-week-day-head">
                            <p class="ym-week-day-name">{{ day.day }}</p>
                            <p class="ym-week-day-date">{{ day.date }}</p>
                        </header>

                        <div v-if="day.entries.length" class="ym-week-day-slots">
                            <div v-for="entry in day.entries" :key="entry.title" class="ym-week-slot">
                                <p class="ym-week-slot-time">{{ entry.time }}</p>
                                <p class="ym-week-slot-title">{{ entry.title }}</p>
                                <p class="ym-week-slot-meta">{{ entry.meta }}</p>
                            </div>
                        </div>

                        <p v-else class="ym-empty-slot">{{ dashboardConfig.weeklyPanel.emptyMessage }}</p>
                    </article>
                </div>
            </section>
        </template>

        <Modal :show="showEditDashboardModal" title="Edit Dashboard Tabs" @close="showEditDashboardModal = false">
            <div class="ym-dashboard-modal-actions">
                <button type="button" class="ym-btn-sm" @click="saveDashboardTabs">Save</button>
                <button type="button" class="ym-btn-outline" @click="showEditDashboardModal = false">Cancel</button>
            </div>

            <p v-if="dashboardTabError" class="ym-field-error">{{ dashboardTabError }}</p>

            <p class="ym-card-note">
                Configure up to six dashboard tabs shown in the top navigation for this session.
            </p>

            <div class="ym-tab-editor-list mt-3">
                <div v-for="(tab, index) in dashboardTabsDraft" :key="tab.id" class="ym-tab-editor-row">
                    <span class="ym-tab-handle" aria-hidden="true">
                        <FontAwesomeIcon :icon="faGripVertical" />
                    </span>
                    <input v-model="tab.label" type="text" class="ym-input" />
                    <button
                        type="button"
                        class="ym-tab-remove"
                        :disabled="dashboardTabsDraft.length === 1"
                        @click="removeDashboardTab(index)"
                    >
                        <FontAwesomeIcon :icon="faXmark" />
                    </button>
                </div>

                <div class="ym-tab-editor-row ym-tab-editor-row--add">
                    <input
                        v-model="newDashboardTabLabel"
                        type="text"
                        class="ym-input"
                        placeholder="Type and press enter"
                        @keydown.enter.prevent="addDashboardTab"
                    />
                    <button type="button" class="ym-tab-add" :disabled="dashboardTabsDraft.length >= maxTopTabs" @click="addDashboardTab">
                        <FontAwesomeIcon :icon="faPlus" />
                    </button>
                </div>
            </div>

            <label v-if="isAdmin" class="ym-lock-toggle mt-3">
                <span>Lock Dashboard</span>
                <input v-model="lockDashboard" type="checkbox" />
            </label>
        </Modal>

        <Modal
            v-if="isAdmin"
            :show="showAddDashletModal"
            title="Add Dashlet"
            @close="showAddDashletModal = false"
        >
            <label class="ym-search-wrap ym-dashboard-modal-search" aria-label="Search dashlets">
                <FontAwesomeIcon :icon="faMagnifyingGlass" class="ym-search-icon" />
                <input
                    v-model="dashletSearchQuery"
                    type="search"
                    class="ym-search"
                    placeholder="Search"
                    aria-label="Search dashlets"
                />
            </label>

            <div class="ym-dashlet-catalog">
                <button
                    v-for="item in filteredDashletCatalog"
                    :key="item.id"
                    type="button"
                    class="ym-dashlet-catalog-item"
                    :disabled="isDashletActive(item.id)"
                    @click="addDashlet(item)"
                >
                    <span>{{ item.title }}</span>
                    <small>{{ isDashletActive(item.id) ? 'Added' : 'Add' }}</small>
                </button>
            </div>
        </Modal>
    </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faGripVertical,
    faMagnifyingGlass,
    faPlus,
    faXmark,
} from '@fortawesome/free-solid-svg-icons';
import Draggable from 'vuedraggable';
import Modal from '../Components/UI/Modal.vue';
import AppLayout from '../Layouts/AppLayout.vue';

const page = usePage();

const maxTopTabs = 6;

const userRole = computed(() => page.props.auth?.user?.role ?? 'member');
const isAdmin = computed(() => userRole.value === 'admin');

const roleDashboard = {
    admin: {
        pageTitle: 'Homepage',
        tabs: ['Homepage', 'My Schedule', 'Members', 'Attendance', 'Studio Reports', 'Financials'],
    },
    coach: {
        pageTitle: 'Coach Dashboard',
        headline: "Today's Teaching Snapshot",
        subtitle: 'Focus on current classes, student progress, and your upcoming teaching responsibilities.',
        metrics: [
            { label: 'Sessions Today', value: '4', note: '2 completed, 2 remaining' },
            { label: 'Students Today', value: '72', note: 'Across 3 branches and 1 online class' },
            { label: 'Average Attendance', value: '89%', note: 'Last 14 days' },
        ],
        primaryPanel: {
            title: "Today's Schedule",
            rows: [
                { title: '07:00 Power Core', meta: 'Riverside · 24 students', badge: 'Completed', tone: 'started' },
                { title: '12:30 Prenatal Flow', meta: 'Westside · 14 students', badge: 'Completed', tone: 'started' },
                { title: '18:30 Evening Yin', meta: 'Downtown · 21 students', badge: 'Up Next', tone: 'pending' },
                { title: '20:00 Breathwork Lab', meta: 'Online · 32 students', badge: 'Later', tone: 'default' },
            ],
        },
        secondaryPanel: {
            title: 'Student Snapshot',
            rows: [
                { title: 'High Consistency Students', meta: 'Attendance above 90%', badge: '23', tone: 'started' },
                { title: 'Follow-up Needed', meta: 'Attendance below 60%', badge: '6', tone: 'pending' },
                { title: 'New This Week', meta: 'First-time students', badge: '11', tone: 'default' },
                { title: 'Progress Assessments Due', meta: 'By end of week', badge: '8', tone: 'default' },
            ],
        },
        weeklyPanel: {
            title: 'Weekly Teaching Timeline',
            emptyMessage: 'No classes assigned',
            days: [
                {
                    day: 'Monday',
                    date: 'Apr 27',
                    entries: [
                        { time: '06:45', title: 'Sunrise Mobility', meta: 'Westside · 18 students' },
                        { time: '18:30', title: 'Evening Yin', meta: 'Downtown · 21 students' },
                    ],
                },
                {
                    day: 'Tuesday',
                    date: 'Apr 28',
                    entries: [
                        { time: '07:00', title: 'Power Core', meta: 'Riverside · 24 students' },
                        { time: '20:00', title: 'Breathwork Lab', meta: 'Online · 32 students' },
                    ],
                },
                {
                    day: 'Wednesday',
                    date: 'Apr 29',
                    entries: [
                        { time: '18:30', title: 'Evening Yin', meta: 'Downtown · 20 students' },
                    ],
                },
                {
                    day: 'Thursday',
                    date: 'Apr 30',
                    entries: [
                        { time: '07:00', title: 'Power Core', meta: 'Riverside · 23 students' },
                        { time: '12:30', title: 'Prenatal Flow', meta: 'Westside · 14 students' },
                    ],
                },
                {
                    day: 'Friday',
                    date: 'May 01',
                    entries: [
                        { time: '17:45', title: 'Mobility Reset', meta: 'Downtown · 16 students' },
                    ],
                },
                {
                    day: 'Saturday',
                    date: 'May 02',
                    entries: [
                        { time: '09:00', title: 'Weekend Flow', meta: 'Uptown · 19 students' },
                    ],
                },
                { day: 'Sunday', date: 'May 03', entries: [] },
            ],
        },
        tabs: ['Homepage', 'Today', 'My Classes', 'My Students', 'Schedule', 'Notes'],
    },
    member: {
        pageTitle: 'Member Dashboard',
        headline: 'Membership and Session Overview',
        subtitle: 'Stay on top of your membership status and upcoming classes for the week.',
        metrics: [
            { label: 'Membership Status', value: 'Active', note: 'Premium Flow Annual' },
            { label: 'Sessions This Week', value: '3 / 5', note: '2 sessions remaining to hit your goal' },
            { label: 'Next Session', value: 'Today 18:30', note: 'Evening Yin · Downtown' },
        ],
        primaryPanel: {
            title: 'Membership Status',
            rows: [
                { title: 'Plan Type', meta: 'Premium Flow Annual', badge: 'Premium', tone: 'started' },
                { title: 'Renewal Date', meta: 'Jan 5, 2027', badge: 'Auto', tone: 'default' },
                { title: 'Guest Passes', meta: 'Available this cycle', badge: '4 left', tone: 'default' },
                { title: 'Support Tickets', meta: 'Membership requests', badge: '0 open', tone: 'started' },
            ],
        },
        secondaryPanel: {
            title: 'Upcoming Sessions',
            rows: [
                { title: 'Evening Yin', meta: 'Today 18:30 · Downtown', badge: 'Booked', tone: 'started' },
                { title: 'Power Core', meta: 'Tue 07:00 · Riverside', badge: 'Booked', tone: 'started' },
                { title: 'Breathwork Lab', meta: 'Thu 20:00 · Online', badge: 'Waitlist', tone: 'pending' },
                { title: 'Weekend Flow', meta: 'Sat 09:00 · Uptown', badge: 'Booked', tone: 'started' },
            ],
        },
        weeklyPanel: {
            title: 'Personal Weekly Calendar',
            emptyMessage: 'Rest and recovery day',
            days: [
                {
                    day: 'Monday',
                    date: 'Apr 27',
                    entries: [
                        { time: '06:45', title: 'Sunrise Mobility', meta: 'Westside · Coach Lina' },
                        { time: '18:30', title: 'Evening Yin', meta: 'Downtown · Coach Ari' },
                    ],
                },
                {
                    day: 'Tuesday',
                    date: 'Apr 28',
                    entries: [
                        { time: '07:00', title: 'Power Core', meta: 'Riverside · Coach Daniel' },
                    ],
                },
                {
                    day: 'Wednesday',
                    date: 'Apr 29',
                    entries: [
                        { time: '18:30', title: 'Evening Yin', meta: 'Downtown · Coach Ari' },
                    ],
                },
                {
                    day: 'Thursday',
                    date: 'Apr 30',
                    entries: [
                        { time: '07:00', title: 'Power Core', meta: 'Riverside · Coach Daniel' },
                        { time: '20:00', title: 'Breathwork Lab', meta: 'Online · Coach Noah' },
                    ],
                },
                {
                    day: 'Friday',
                    date: 'May 01',
                    entries: [
                        { time: '17:45', title: 'Mobility Reset', meta: 'Downtown · Coach Lina' },
                    ],
                },
                {
                    day: 'Saturday',
                    date: 'May 02',
                    entries: [
                        { time: '09:00', title: 'Weekend Flow', meta: 'Uptown · Coach Mia' },
                    ],
                },
                { day: 'Sunday', date: 'May 03', entries: [] },
            ],
        },
        tabs: ['Homepage', 'Membership', 'My Classes', 'My Schedule', 'Progress', 'Wellness'],
    },
};

const dashboardConfig = computed(() => roleDashboard[userRole.value] ?? roleDashboard.member);
const pageTitle = computed(() => roleDashboard[userRole.value]?.pageTitle ?? 'Dashboard');
const dashboardNotice = ref('');

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

const dashletCatalog = [
    {
        id: 'calendar',
        title: 'Calendar',
        type: 'calendar',
        span: 8,
        required: true,
        description: 'Calendar and scheduling overview',
        previewRows: ['Today timeline', 'Upcoming sessions'],
    },
    {
        id: 'memo',
        title: 'Memo',
        type: 'memo',
        span: 6,
        description: 'Pin an editable note for your team.',
        previewRows: ['Meeting highlights', 'Quick reminders'],
    },
    {
        id: 'activities',
        title: 'My Activities',
        type: 'activities',
        span: 4,
        required: true,
        description: 'Your timeline and follow-ups.',
        previewRows: ['Upcoming tasks', 'Late follow-ups'],
    },
    {
        id: 'cases',
        title: 'My Cases',
        type: 'cases',
        span: 6,
        required: false,
        description: 'Open cases assigned to your team.',
        previewRows: ['Pending approvals', 'Escalated tickets'],
    },
    {
        id: 'my-leads',
        title: 'My Leads',
        type: 'generic',
        span: 4,
        description: 'Lead flow and assignment updates.',
        previewRows: ['New incoming leads', 'Qualified pipeline'],
    },
    {
        id: 'lead-source',
        title: 'Opportunities by Lead Source',
        type: 'lead-source',
        span: 6,
        required: false,
        description: 'Channel share of incoming opportunities.',
        previewRows: ['Top channels', 'Campaign performance'],
    },
];

const cloneDashlet = (dashlet) => ({
    ...dashlet,
    previewRows: Array.isArray(dashlet.previewRows) ? [...dashlet.previewRows] : [],
});

const createDashletFromId = (id) => {
    const definition = dashletCatalog.find((item) => item.id === id);
    return definition ? cloneDashlet(definition) : null;
};

const defaultDashletLayout = [
    { id: 'calendar', span: 8 },
    { id: 'lead-source', span: 4 },
    { id: 'activities', span: 6 },
    { id: 'cases', span: 6 },
];

const buildDefaultDashlets = () =>
    defaultDashletLayout
        .map(({ id, span }) => {
            const dashlet = createDashletFromId(id);
            return dashlet ? { ...dashlet, span } : null;
        })
        .filter(Boolean);

const dashlets = ref(buildDefaultDashlets());
const lockDashboard = ref(false);
const isDraggingDashlet = ref(false);
const showAddDashletModal = ref(false);
const dashletSearchQuery = ref('');

const filteredDashletCatalog = computed(() => {
    const query = dashletSearchQuery.value.trim().toLowerCase();
    if (!query) {
        return dashletCatalog;
    }

    return dashletCatalog.filter((item) => item.title.toLowerCase().includes(query));
});

const dashletClasses = (dashlet) => {
    const validSpan = [4, 6, 8, 12].includes(dashlet.span) ? dashlet.span : 6;

    return [
        'ym-surface',
        'ym-dashlet-card',
        `ym-dashlet-span-${validSpan}`,
        {
            'ym-dashlet-card--locked': lockDashboard.value,
            'ym-dashlet-card--reordering': isDraggingDashlet.value,
        },
    ];
};

const isDashletActive = (dashletId) => dashlets.value.some((item) => item.id === dashletId);

const addDashlet = (dashlet) => {
    if (isDashletActive(dashlet.id)) {
        return;
    }

    dashlets.value = [...dashlets.value, cloneDashlet(dashlet)];
    showAddDashletModal.value = false;
};

const removeDashlet = (dashletId) => {
    if (lockDashboard.value) {
        return;
    }

    dashlets.value = dashlets.value.filter((item) => item.id !== dashletId);
};

const handleDashletSortStart = () => {
    isDraggingDashlet.value = true;
};

const handleDashletSortEnd = () => {
    isDraggingDashlet.value = false;
};

const toViewKey = (value) => value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

const buildDefaultDashboardTabs = (role) => {
    const config = roleDashboard[role] ?? roleDashboard.member;

    return config.tabs.map((label, index) => {
        const viewKey = index === 0 ? 'homepage' : (toViewKey(label) || `view-${index + 1}`);
        return {
            label,
            viewKey,
            href: index === 0 ? '/cms/dashboard' : `/cms/dashboard?view=${viewKey}`,
        };
    });
};

const defaultDashboardTabs = computed(() => buildDefaultDashboardTabs(userRole.value));

const createTabsDraft = (tabs) => tabs.slice(0, maxTopTabs).map((tab, index) => ({
    id: `tab-${index}-${tab.viewKey ?? index}`,
    label: (tab.label ?? '').toString().trim(),
}));

const hydrateDashboardTabs = (incomingTabs = []) => {
    const sourceTabs = incomingTabs.length ? incomingTabs : defaultDashboardTabs.value;
    const tabs = createTabsDraft(sourceTabs);

    while (tabs.length < maxTopTabs) {
        const fallback = defaultDashboardTabs.value[tabs.length] ?? { label: `View ${tabs.length + 1}` };
        tabs.push({
            id: `tab-fill-${tabs.length}`,
            label: fallback.label,
        });
    }

    return tabs;
};

const showEditDashboardModal = ref(false);
const dashboardTabError = ref('');
const newDashboardTabLabel = ref('');
const dashboardTabsDraft = ref(hydrateDashboardTabs());

watch(userRole, () => {
    dashboardTabsDraft.value = hydrateDashboardTabs();
    if (!isAdmin.value) {
        showAddDashletModal.value = false;
    }
});

const removeDashboardTab = (index) => {
    dashboardTabsDraft.value = dashboardTabsDraft.value.filter((_, tabIndex) => tabIndex !== index);
    dashboardTabError.value = '';
};

const addDashboardTab = () => {
    if (dashboardTabsDraft.value.length >= maxTopTabs) {
        dashboardTabError.value = `Top navigation is limited to ${maxTopTabs} tabs.`;
        return;
    }

    const label = newDashboardTabLabel.value.trim();
    if (!label) {
        dashboardTabError.value = 'Enter a tab name before adding.';
        return;
    }

    dashboardTabsDraft.value = [
        ...dashboardTabsDraft.value,
        {
            id: `tab-new-${Date.now()}`,
            label,
        },
    ];

    newDashboardTabLabel.value = '';
    dashboardTabError.value = '';
};

const emitDashboardTabsUpdated = (tabs) => {
    window.dispatchEvent(new CustomEvent('ym-dashboard-tabs-updated', {
        detail: {
            tabs,
        },
    }));
};

const normalizeDashboardTabs = (draftTabs) => draftTabs.map((tab, index) => {
    const label = tab.label.trim();
    const viewKey = index === 0 ? 'homepage' : (toViewKey(label) || `view-${index + 1}`);

    return {
        label,
        viewKey,
        href: index === 0 ? '/cms/dashboard' : `/cms/dashboard?view=${viewKey}`,
    };
});

const saveDashboardTabs = () => {
    dashboardTabError.value = '';

    if (dashboardTabsDraft.value.length !== maxTopTabs) {
        dashboardTabError.value = `Top navigation needs exactly ${maxTopTabs} tabs.`;
        return;
    }

    for (let index = 0; index < dashboardTabsDraft.value.length; index += 1) {
        const currentLabel = dashboardTabsDraft.value[index].label.trim();
        if (!currentLabel) {
            dashboardTabError.value = 'Every tab needs a label before saving.';
            return;
        }
    }

    emitDashboardTabsUpdated(normalizeDashboardTabs(dashboardTabsDraft.value));
    dashboardNotice.value = 'Dashboard tabs updated for this session.';
    showEditDashboardModal.value = false;
};

const resetDashboardTabs = () => {
    const resetTabs = defaultDashboardTabs.value;
    dashboardTabsDraft.value = hydrateDashboardTabs(resetTabs);
    emitDashboardTabsUpdated(resetTabs);
};

const badgeClass = (tone) => [
    'ym-status-pill',
    tone === 'pending' ? 'ym-status-pill--pending' : '',
    tone === 'started' ? 'ym-status-pill--started' : '',
];

const handleDashboardAction = (event) => {
    const action = event?.detail?.action;
    const tabs = event?.detail?.tabs;

    if (Array.isArray(tabs) && tabs.length) {
        dashboardTabsDraft.value = hydrateDashboardTabs(tabs);
    }

    if (action === 'edit-dashboard') {
        showEditDashboardModal.value = true;
        return;
    }

    if (action === 'add-dashlet') {
        if (isAdmin.value) {
            showAddDashletModal.value = true;
        } else {
            dashboardNotice.value = 'Dashlet management is available on the admin dashboard.';
        }
        return;
    }

    if (action === 'reset-dashboard') {
        if (isAdmin.value) {
            dashlets.value = buildDefaultDashlets();
        }
        resetDashboardTabs();
    }
};

const openActionFromUrl = () => {
    if (!page.url.startsWith('/cms/dashboard')) {
        return;
    }

    const query = page.url.split('?')[1] ?? '';
    const params = new URLSearchParams(query);
    const action = params.get('dashboardAction');

    if (action) {
        handleDashboardAction({ detail: { action } });
        params.delete('dashboardAction');
        const nextQuery = params.toString();
        window.history.replaceState({}, '', nextQuery ? `/cms/dashboard?${nextQuery}` : '/cms/dashboard');
    }
};

onMounted(() => {
    window.addEventListener('ym-dashboard-action', handleDashboardAction);
    openActionFromUrl();
});

onBeforeUnmount(() => {
    window.removeEventListener('ym-dashboard-action', handleDashboardAction);
});
</script>
