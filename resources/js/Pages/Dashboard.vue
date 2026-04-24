<template>
    <AppLayout title="Homepage">
        <section class="ym-dashlet-grid">
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

        <Modal
            :show="showEditDashboardModal"
            title="Edit Dashboard"
            @close="showEditDashboardModal = false"
        >
            <div class="ym-dashboard-modal-actions">
                <button type="button" class="ym-btn-sm" @click="saveDashboardTabs">Save</button>
                <button type="button" class="ym-btn-outline" @click="showEditDashboardModal = false">Cancel</button>
            </div>

            <p v-if="dashboardTabError" class="ym-field-error">{{ dashboardTabError }}</p>

            <div class="ym-dashboard-modal-grid">
                <div>
                    <p class="ym-modal-group-title">Tab List</p>
                    <div class="ym-tab-editor-list">
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
                </div>

                <label class="ym-lock-toggle">
                    <span>Lock Dashboard</span>
                    <input v-model="lockDashboard" type="checkbox" />
                </label>
            </div>
        </Modal>

        <Modal
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
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
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

const defaultDashboardTabs = [
    { label: 'Homepage', viewKey: 'homepage', href: '/cms/dashboard' },
    { label: 'My Schedule', viewKey: 'my-schedule', href: '/cms/dashboard?view=my-schedule' },
    { label: 'Members', viewKey: 'members', href: '/cms/dashboard?view=members' },
    { label: 'Attendance', viewKey: 'attendance', href: '/cms/dashboard?view=attendance' },
    { label: 'Studio Reports', viewKey: 'studio-reports', href: '/cms/dashboard?view=studio-reports' },
    { label: 'Financials', viewKey: 'financials', href: '/cms/dashboard?view=financials' },
];

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
        id: 'iframe',
        title: 'Iframe',
        type: 'generic',
        span: 6,
        description: 'Embed a lightweight external panel.',
        previewRows: ['Add URL source', 'Set embed height'],
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
        id: 'my-calls',
        title: 'My Calls',
        type: 'generic',
        span: 4,
        description: 'Track active call queues and callbacks.',
        previewRows: ['Today call queue', 'Missed callbacks'],
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
        id: 'my-inbox',
        title: 'My Inbox',
        type: 'generic',
        span: 4,
        description: 'Unread and flagged inbox entries.',
        previewRows: ['Unread items', 'Flagged threads'],
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
        id: 'my-meetings',
        title: 'My Meetings',
        type: 'generic',
        span: 4,
        description: 'Meeting prep and pending actions.',
        previewRows: ['Next 5 meetings', 'Pending minutes'],
    },
    {
        id: 'my-opportunities',
        title: 'My Opportunities',
        type: 'generic',
        span: 6,
        description: 'Revenue opportunities in progress.',
        previewRows: ['Pipeline by stage', 'High value deals'],
    },
    {
        id: 'project-tasks',
        title: 'My Project Tasks',
        type: 'generic',
        span: 6,
        description: 'Project delivery tasks and milestones.',
        previewRows: ['Due this week', 'Blocked tasks'],
    },
    {
        id: 'my-tasks',
        title: 'My Tasks',
        type: 'generic',
        span: 4,
        description: 'Personal task board summary.',
        previewRows: ['Due today', 'Assigned recently'],
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
    {
        id: 'opportunities-stage',
        title: 'Opportunities by Stage',
        type: 'generic',
        span: 6,
        description: 'Pipeline distribution by stage.',
        previewRows: ['Stage distribution', 'Velocity trend'],
    },
    {
        id: 'process-user-tasks',
        title: 'Process User Tasks',
        type: 'generic',
        span: 6,
        description: 'Workflow task processing summary.',
        previewRows: ['Tasks by owner', 'SLA breaches'],
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
    { id: 'calendar',     span: 8 },
    { id: 'lead-source',  span: 4 },
    { id: 'activities',   span: 6 },
    { id: 'cases',        span: 6 },
];

const buildDefaultDashlets = () =>
    defaultDashletLayout
        .map(({ id, span }) => {
            const d = createDashletFromId(id);
            return d ? { ...d, span } : null;
        })
        .filter(Boolean);

const dashlets = ref(buildDefaultDashlets());

const lockDashboard = ref(false);
const isDraggingDashlet = ref(false);

const showEditDashboardModal = ref(false);
const showAddDashletModal = ref(false);
const dashletSearchQuery = ref('');
const dashboardTabError = ref('');
const newDashboardTabLabel = ref('');

const dashboardTabsDraft = ref(
    defaultDashboardTabs.map((tab, index) => ({
        id: `tab-${index}`,
        label: tab.label,
    })),
);

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

const hydrateDashboardTabs = (incomingTabs = []) => {
    const tabs = incomingTabs
        .slice(0, maxTopTabs)
        .map((tab, index) => ({
            id: `tab-${index}-${tab.viewKey ?? index}`,
            label: (tab.label ?? '').toString().trim() || defaultDashboardTabs[index]?.label || `View ${index + 1}`,
        }));

    while (tabs.length < maxTopTabs) {
        const fallback = defaultDashboardTabs[tabs.length];
        tabs.push({
            id: `tab-fill-${tabs.length}`,
            label: fallback.label,
        });
    }

    return tabs;
};

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

const toViewKey = (value) => value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

const saveDashboardTabs = () => {
    dashboardTabError.value = '';

    if (dashboardTabsDraft.value.length !== maxTopTabs) {
        dashboardTabError.value = `Top navigation needs exactly ${maxTopTabs} tabs.`;
        return;
    }

    const normalizedTabs = [];

    for (let index = 0; index < dashboardTabsDraft.value.length; index += 1) {
        const currentLabel = dashboardTabsDraft.value[index].label.trim();
        if (!currentLabel) {
            dashboardTabError.value = 'Every tab needs a label before saving.';
            return;
        }

        const viewKey = index === 0 ? 'homepage' : (toViewKey(currentLabel) || `view-${index + 1}`);
        normalizedTabs.push({
            label: currentLabel,
            viewKey,
            href: index === 0 ? '/cms/dashboard' : `/cms/dashboard?view=${viewKey}`,
        });
    }

    window.dispatchEvent(new CustomEvent('ym-dashboard-tabs-updated', {
        detail: {
            tabs: normalizedTabs,
        },
    }));

    showEditDashboardModal.value = false;
};

const handleDashboardAction = (event) => {
    const action = event?.detail?.action;
    const tabs = event?.detail?.tabs;

    if (Array.isArray(tabs) && tabs.length) {
        dashboardTabsDraft.value = hydrateDashboardTabs(tabs);
    }

    if (action === 'edit-dashboard') {
        showEditDashboardModal.value = true;
    }

    if (action === 'add-dashlet') {
        showAddDashletModal.value = true;
    }

    if (action === 'reset-dashboard') {
        dashlets.value = buildDefaultDashlets();
    }
};

const openActionFromUrl = () => {
    if (!page.url.startsWith('/cms/dashboard')) {
        return;
    }

    const query = page.url.split('?')[1] ?? '';
    const params = new URLSearchParams(query);
    const action = params.get('dashboardAction');

    if (action === 'edit-dashboard') {
        showEditDashboardModal.value = true;
    }

    if (action === 'add-dashlet') {
        showAddDashletModal.value = true;
    }

    if (action) {
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
