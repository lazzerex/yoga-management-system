<script setup>
import { computed, defineAsyncComponent, onBeforeUnmount, onMounted, ref } from 'vue';
import { route } from 'ziggy-js';
import { trans as t, currentLocale } from 'laravel-vue-i18n';
import Draggable from 'vuedraggable';
import Modal from '@/Components/UI/Modal.vue';
import { pushToast } from '@/composables/useToasts.js';
import MembershipSummary from '@/Components/Dashboard/MembershipSummary.vue';
import MoneyKpis from '@/Components/Dashboard/MoneyKpis.vue';
import MyPlans from '@/Components/Dashboard/MyPlans.vue';
import NewStudents from '@/Components/Dashboard/NewStudents.vue';
import PendingPlans from '@/Components/Dashboard/PendingPlans.vue';
import TeachingHours from '@/Components/Dashboard/TeachingHours.vue';
import TodaySessions from '@/Components/Dashboard/TodaySessions.vue';
import TodayTimeline from '@/Components/Dashboard/TodayTimeline.vue';
import UpcomingSessions from '@/Components/Dashboard/UpcomingSessions.vue';
import WeeklyTimeline from '@/Components/Dashboard/WeeklyTimeline.vue';

const AttendanceRate = defineAsyncComponent(() => import('@/Components/Dashboard/AttendanceRate.vue'));
const Occupancy = defineAsyncComponent(() => import('@/Components/Dashboard/Occupancy.vue'));
const RevenueChart = defineAsyncComponent(() => import('@/Components/Dashboard/RevenueChart.vue'));

const ANALYTICS_VIEWS = {
    overview: defineAsyncComponent(() => import('@/Components/Dashboard/Views/CentreOverview.vue')),
    classes: defineAsyncComponent(() => import('@/Components/Dashboard/Views/StudioPulse.vue')),
    people: defineAsyncComponent(() => import('@/Components/Dashboard/Views/People.vue')),
    attendance: defineAsyncComponent(() => import('@/Components/Dashboard/Views/AttendanceTrends.vue')),
    financials: defineAsyncComponent(() => import('@/Components/Dashboard/Views/Financials.vue')),
    'my-teaching': defineAsyncComponent(() => import('@/Components/Dashboard/Views/MyTeaching.vue')),
    'my-progress': defineAsyncComponent(() => import('@/Components/Dashboard/Views/MyProgress.vue')),
};

const props = defineProps({
    auth: Object,
    persona: String,
    view: { type: String, default: 'dashboard' },
    tabs: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
    widgets: Object,
    analytics: { type: Object, default: null },
});

const analyticsView = computed(() => (props.view === 'dashboard' ? null : ANALYTICS_VIEWS[props.view] ?? null));

const overviewEndpoint = route('cms.dashboard', { view: 'overview' });

const getCurrentUrl = () => `${window.location.pathname}${window.location.search}`;

const maxTopTabs = 6;

const canAccessAdmin = computed(() => props.auth?.user?.canAccessAdmin ?? false);

// Widget id must match the key the controller sends; a widget the viewer's permissions
// did not fill simply never reaches the catalog.
const WIDGET_TYPES = {
    money: { component: MoneyKpis, titleKey: 'dashboard.widgetMoney', span: 12 },
    revenue: { component: RevenueChart, titleKey: 'dashboard.widgetRevenue', span: 8 },
    occupancy: { component: Occupancy, titleKey: 'dashboard.widgetOccupancy', span: 4 },
    timeline: { component: TodayTimeline, titleKey: 'dashboard.widgetTimeline', span: 6 },
    pendingPlans: { component: PendingPlans, titleKey: 'dashboard.widgetPendingPlans', span: 6 },
    todaySessions: { component: TodaySessions, titleKey: 'dashboard.widgetTodaySessions', span: 12 },
    newStudents: { component: NewStudents, titleKey: 'dashboard.widgetNewStudents', span: 6 },
    teachingHours: { component: TeachingHours, titleKey: 'dashboard.widgetTeachingHours', span: 6 },
    attendanceRate: { component: AttendanceRate, titleKey: 'dashboard.widgetAttendanceRate', span: 6 },
    myPlans: { component: MyPlans, titleKey: 'dashboard.widgetMyPlans', span: 6 },
    weekTimeline: { component: WeeklyTimeline, titleKey: 'dashboard.widgetWeekTimeline', span: 12 },
    upcoming: { component: UpcomingSessions, titleKey: 'dashboard.widgetUpcoming', span: 6 },
    membership: { component: MembershipSummary, titleKey: 'dashboard.widgetMembership', span: 6 },
};

const DEFAULT_LAYOUT = {
    admin: ['money', 'revenue', 'occupancy', 'timeline', 'pendingPlans'],
    coach: ['todaySessions', 'teachingHours', 'attendanceRate', 'myPlans', 'weekTimeline'],
    member: ['upcoming', 'attendanceRate', 'membership'],
};

const availableWidgets = computed(() => Object.keys(props.widgets ?? {}).filter((id) => id in WIDGET_TYPES));

// A dashlet stores what it is, never how it reads: switching language re-labels the
// board in place instead of rebuilding it and losing the viewer's arrangement.
const buildWidget = (id) => ({ id, span: WIDGET_TYPES[id].span });

const widgetTitle = (id) => {
    void currentLocale.value;

    return t(WIDGET_TYPES[id].titleKey);
};

const catalog = computed(() => availableWidgets.value.map(buildWidget));

const buildDefaultDashlets = () => (DEFAULT_LAYOUT[props.persona] ?? [])
    .filter((id) => availableWidgets.value.includes(id))
    .map(buildWidget);

const dashlets = ref(buildDefaultDashlets());
const lockDashboard = ref(false);
const isDraggingDashlet = ref(false);
const showAddDashletModal = ref(false);
const dashletSearchQuery = ref('');

const widgetComponent = (id) => WIDGET_TYPES[id].component;
const widgetData = (id) => props.widgets[id];

const filteredCatalog = computed(() => {
    const query = dashletSearchQuery.value.trim().toLowerCase();

    return query
        ? catalog.value.filter((item) => widgetTitle(item.id).toLowerCase().includes(query))
        : catalog.value;
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

    dashlets.value = [...dashlets.value, { ...dashlet }];
    showAddDashletModal.value = false;
    pushToast('dashboard.dashletAdded', { params: { name: widgetTitle(dashlet.id) } });
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

// The server owns the view keys; the editor renames the label shown for one, and a
// rename is remembered as an override so a language switch cannot wipe it.
const showEditDashboardModal = ref(false);
const dashboardTabError = ref('');
const dashboardTabsDraft = ref([]);

const createTabsDraft = (tabs) => tabs.slice(0, maxTopTabs).map((tab) => ({
    viewKey: tab.viewKey,
    labelKey: tab.labelKey,
    label: tab.label ?? t(tab.labelKey),
    hidden: tab.hidden ?? false,
}));

const hydrateDashboardTabs = (incomingTabs = []) => createTabsDraft(
    incomingTabs.length ? incomingTabs : props.tabs,
);

const removeDashboardTab = (index) => {
    dashboardTabsDraft.value = dashboardTabsDraft.value.filter((_, tabIndex) => tabIndex !== index);
    dashboardTabError.value = '';
};

const emitDashboardTabsUpdated = (overrides, hidden) => {
    window.dispatchEvent(new CustomEvent('ym-dashboard-tabs-updated', { detail: { overrides, hidden } }));
};

const saveDashboardTabs = () => {
    dashboardTabError.value = '';

    if (dashboardTabsDraft.value.some((tab) => ! tab.label.trim())) {
        dashboardTabError.value = t('dashboard.tabLabelRequiredError');
        return;
    }

    const overrides = {};
    dashboardTabsDraft.value.forEach((tab) => {
        const label = tab.label.trim();

        // Only a genuine rename is stored: a tab left alone keeps translating itself.
        if (label !== t(tab.labelKey)) {
            overrides[tab.viewKey] = label;
        }
    });

    const kept = dashboardTabsDraft.value.map((tab) => tab.viewKey);
    const hidden = props.tabs.map((tab) => tab.viewKey).filter((viewKey) => ! kept.includes(viewKey));

    emitDashboardTabsUpdated(overrides, hidden);
    pushToast('dashboard.tabsUpdatedNotice');
    showEditDashboardModal.value = false;
};

const resetDashboardTabs = () => {
    dashboardTabsDraft.value = hydrateDashboardTabs();
    emitDashboardTabsUpdated({}, []);
};

const handleDashboardAction = (event) => {
    const action = event?.detail?.action;
    const tabs = event?.detail?.tabs;

    if (action === 'edit-dashboard') {
        dashboardTabsDraft.value = hydrateDashboardTabs(Array.isArray(tabs) ? tabs.filter((tab) => ! tab.hidden) : []);
        showEditDashboardModal.value = true;
        return;
    }

    if (action === 'add-dashlet') {
        if (canAccessAdmin.value) {
            showAddDashletModal.value = true;
        } else {
            pushToast('dashboard.dashletAdminOnly', { kind: 'error' });
        }
        return;
    }

    if (action === 'reset-dashboard') {
        if (canAccessAdmin.value) {
            dashlets.value = buildDefaultDashlets();
        }
        resetDashboardTabs();
    }
};

const openActionFromUrl = () => {
    const currentUrl = getCurrentUrl();
    if (!currentUrl.startsWith(route('cms.dashboard', undefined, false))) {
        return;
    }

    const params = new URLSearchParams(currentUrl.split('?')[1] ?? '');
    const action = params.get('dashboardAction');

    if (action) {
        handleDashboardAction({ detail: { action } });
        params.delete('dashboardAction');
        const nextQuery = params.toString();
        const dashboardPath = route('cms.dashboard', undefined, false);
        window.history.replaceState({}, '', nextQuery ? `${dashboardPath}?${nextQuery}` : dashboardPath);
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
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';

export default {
    layout: (h, page) => {
        const user = page.props.auth?.user;
        const title = user?.canAccessAdmin
            ? t('dashboard.viewDashboard')
            : user?.canViewCoachDashboard
                ? t('dashboard.coachDashboard')
                : t('dashboard.memberDashboard');
        return h(AppLayout, { title }, () => page);
    },
};
</script>

<template>
    <component
        :is="analyticsView"
        v-if="analyticsView"
        :key="view"
        :data="analytics"
        v-bind="view === 'overview' ? { filters: props.filters, options: props.options, endpoint: overviewEndpoint } : {}"
    />

    <p v-else-if="!dashlets.length" class="ym-card-note">{{ $t('dashboard.noWidgetsAvailable') }}</p>

    <section v-if="!analyticsView" class="ym-dashlet-grid">
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
            :disabled="lockDashboard || !canAccessAdmin"
            @start="handleDashletSortStart"
            @end="handleDashletSortEnd"
        >
            <template #item="{ element: dashlet }">
                <article :class="dashletClasses(dashlet)">
                    <header class="ym-panel-head">
                        <div class="ym-dashlet-head">
                            <span v-if="canAccessAdmin" class="ym-drag-handle" aria-hidden="true">
                                <i class="bi bi-grip-vertical" />
                            </span>
                            <h2 class="ym-panel-title">{{ widgetTitle(dashlet.id) }}</h2>
                        </div>

                        <button
                            v-if="canAccessAdmin"
                            type="button"
                            class="ym-dashlet-remove"
                            :disabled="lockDashboard"
                            :aria-label="$t('dashboard.removeDashlet')"
                            @click="removeDashlet(dashlet.id)"
                        >
                            <i class="bi bi-x-lg" />
                        </button>
                    </header>

                    <div class="ym-section">
                        <component :is="widgetComponent(dashlet.id)" :data="widgetData(dashlet.id)" />
                    </div>
                </article>
            </template>
        </Draggable>
    </section>

    <Modal :show="showEditDashboardModal" :title="$t('dashboard.editDashboardTabs')" @close="showEditDashboardModal = false">
        <div class="ym-dashboard-modal-actions">
            <button type="button" class="ym-btn-sm" @click="saveDashboardTabs">{{ $t('common.save') }}</button>
            <button type="button" class="ym-btn-outline" @click="showEditDashboardModal = false">{{ $t('common.cancel') }}</button>
        </div>

        <p v-if="dashboardTabError" class="ym-field-error">{{ dashboardTabError }}</p>

        <p class="ym-card-note">{{ $t('dashboard.configureTabsNote') }}</p>

        <div class="ym-tab-editor-list mt-3">
            <div v-for="(tab, index) in dashboardTabsDraft" :key="tab.id" class="ym-tab-editor-row">
                <span class="ym-tab-handle" aria-hidden="true">
                    <i class="bi bi-grip-vertical" />
                </span>
                <input v-model="tab.label" type="text" class="ym-input" />
                <button
                    type="button"
                    class="ym-tab-remove"
                    :disabled="dashboardTabsDraft.length === 1"
                    @click="removeDashboardTab(index)"
                >
                    <i class="bi bi-x-lg" />
                </button>
            </div>

        </div>

        <label v-if="canAccessAdmin" class="ym-lock-toggle mt-3">
            <span>{{ $t('dashboard.lockDashboard') }}</span>
            <input v-model="lockDashboard" type="checkbox" />
        </label>
    </Modal>

    <Modal
        v-if="canAccessAdmin"
        :show="showAddDashletModal"
        :title="$t('dashboard.addDashlet')"
        @close="showAddDashletModal = false"
    >
        <label class="ym-search-wrap ym-dashboard-modal-search" :aria-label="$t('common.search')">
            <i class="bi bi-search ym-search-icon" />
            <input
                v-model="dashletSearchQuery"
                type="search"
                class="ym-search"
                :placeholder="$t('common.search')"
                :aria-label="$t('common.search')"
            />
        </label>

        <div class="ym-dashlet-catalog">
            <button
                v-for="item in filteredCatalog"
                :key="item.id"
                type="button"
                class="ym-dashlet-catalog-item"
                :disabled="isDashletActive(item.id)"
                @click="addDashlet(item)"
            >
                <span>{{ widgetTitle(item.id) }}</span>
                <small>{{ isDashletActive(item.id) ? $t('dashboard.added') : $t('dashboard.add') }}</small>
            </button>
        </div>
    </Modal>
</template>
