<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { currentLocale, trans as t } from 'laravel-vue-i18n';
import Modal from '@/Components/UI/Modal.vue';
import TabBar from '@/Components/UI/TabBar.vue';
import FilterBar from '@/Components/UI/FilterBar.vue';
import DateRange from '@/Components/UI/DateRange.vue';
import SortTh from '@/Components/UI/SortTh.vue';
import WeekCalendar from '@/Components/UI/WeekCalendar.vue';
import { useFilters } from '@/composables/useFilters.js';

const props = defineProps({
    sessions: Object,
    calendar: Array,
    week: String,
    schedules: Object,
    stats: Object,
    filters: Object,
    options: Object,
    endpoints: Object,
    canManage: Boolean,
});

const { filters, active, filterCount, reset, toggleSort } = useFilters(props.endpoints.index, props.filters);

const tabs = computed(() => {
    currentLocale.value;
    return [
        { key: 'calendar', label: t('operations.calendar') },
        { key: 'sessions', label: t('operations.sessions') },
        { key: 'schedules', label: t('operations.schedules') },
    ];
});
const activeTab = ref('calendar');

const fillPercent = (row) => (row.capacity ? Math.min(100, Math.round((row.booked_count / row.capacity) * 100)) : 0);
const fillTone = (row) => (row.booked_count >= row.capacity ? 'is-full' : fillPercent(row) >= 80 ? 'is-high' : '');

const detail = ref(null);
const detailLoading = ref(false);
const detailError = ref(false);
const confirmingCancel = ref(false);
const cancelling = ref(false);

const openSession = async (id) => {
    detail.value = { id };
    detailLoading.value = true;
    detailError.value = false;
    confirmingCancel.value = false;

    try {
        const { data } = await window.axios.get(props.endpoints.showSession.replace('__ID__', id));
        if (detail.value?.id === id) detail.value = data;
    } catch {
        detailError.value = true;
    } finally {
        detailLoading.value = false;
    }
};

const closeSession = () => {
    detail.value = null;
    confirmingCancel.value = false;
};

const cancelSession = () => {
    cancelling.value = true;
    router.post(detail.value.endpoints.cancel, {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => closeSession(),
        onFinish: () => (cancelling.value = false),
    });
};

const enrollmentLabel = (status) => t(`operations.enrollmentStatus${status.charAt(0).toUpperCase()}${status.slice(1)}`);
const enrollmentTone = { booked: 'ok', waitlisted: 'warn', cancelled: 'neutral' };
const attendanceLabel = (status) => t(`operations.roster${status.charAt(0).toUpperCase()}${status.slice(1)}`);

const dayNames = [
    'operations.daySunday',
    'operations.dayMonday',
    'operations.dayTuesday',
    'operations.dayWednesday',
    'operations.dayThursday',
    'operations.dayFriday',
    'operations.daySaturday',
];

const dayLabel = (dayOfWeek) => t(dayNames[dayOfWeek]);

const statusLabel = (status) => t(`operations.status${status.charAt(0).toUpperCase()}${status.slice(1)}`);

const statusTone = {
    scheduled: 'info',
    done: 'ok',
    cancelled: 'danger',
};

const pendingDeleteSchedule = ref(null);

const confirmDeleteSchedule = () => {
    router.delete(route('operations.class-schedules.destroy', pendingDeleteSchedule.value.id), {
        preserveScroll: true,
        onSuccess: () => (pendingDeleteSchedule.value = null),
    });
};

const generatingSessions = ref(false);

const generateSessions = () => {
    generatingSessions.value = true;
    router.post(props.endpoints.generateSessions, {}, {
        preserveScroll: true,
        onFinish: () => (generatingSessions.value = false),
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.academy') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <TabBar v-model="activeTab" :tabs="tabs" />

        <template v-if="activeTab === 'calendar'">
            <header class="ym-page-head">
                <div>
                    <h1 class="ym-page-title">{{ $t('operations.centreCalendar') }}</h1>
                    <p class="ym-page-sub">{{ $t('operations.centreCalendarSub') }}</p>
                </div>
            </header>

            <WeekCalendar
                :sessions="calendar"
                :week="week"
                :reload-only="['calendar', 'week']"
                :empty-text="$t('operations.noSessionsThisWeek')"
                @select="openSession($event.id)"
            >
                <template #event="{ session }">
                    <span class="ym-wcal-title">{{ session.class_type_name }}</span>
                    <span class="ym-wcal-meta">{{ session.coach_name }}</span>
                    <span class="ym-wcal-meta">{{ session.room_name }} · {{ session.booked_count }}/{{ session.capacity }}</span>
                </template>
            </WeekCalendar>
        </template>

        <template v-else-if="activeTab === 'sessions'">
            <header class="ym-page-head">
                <div>
                    <h1 class="ym-page-title">
                        {{ $t('operations.sessions') }}
                        <span class="ym-count">{{ sessions.total }}</span>
                    </h1>
                </div>
            </header>

            <div class="ym-stats">
                <div class="ym-stat-card">
                    <p class="ym-stat-card-label">{{ $t('operations.upcomingSessionsCount') }}</p>
                    <p class="ym-stat-card-value">{{ stats.upcomingSessions }}</p>
                </div>
                <div class="ym-stat-card ym-stat-card--info">
                    <p class="ym-stat-card-label">{{ $t('operations.activeSchedules') }}</p>
                    <p class="ym-stat-card-value">{{ stats.activeSchedules }}</p>
                </div>
            </div>

            <section class="ym-card">
                <div class="ym-filter-band">
                    <FilterBar
                        v-model:search="filters.search"
                        :search-placeholder="$t('operations.searchSessions')"
                        :count="filterCount"
                        :active="active"
                        @reset="reset"
                    >
                        <label class="ym-filter-field">
                            <span>{{ $t('operations.class') }}</span>
                            <select v-model="filters.class_type_id" class="ym-log-filter-select">
                                <option value="">{{ $t('operations.allClassTypes') }}</option>
                                <option v-for="type in options.classTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                            </select>
                        </label>
                        <label class="ym-filter-field">
                            <span>{{ $t('operations.coach') }}</span>
                            <select v-model="filters.coach_profile_id" class="ym-log-filter-select">
                                <option value="">{{ $t('operations.allCoaches') }}</option>
                                <option v-for="coach in options.coaches" :key="coach.id" :value="coach.id">{{ coach.name }}</option>
                            </select>
                        </label>
                        <label class="ym-filter-field">
                            <span>{{ $t('operations.status') }}</span>
                            <select v-model="filters.status" class="ym-log-filter-select">
                                <option value="">{{ $t('operations.allStatuses') }}</option>
                                <option value="scheduled">{{ statusLabel('scheduled') }}</option>
                                <option value="cancelled">{{ statusLabel('cancelled') }}</option>
                                <option value="done">{{ statusLabel('done') }}</option>
                            </select>
                        </label>
                        <DateRange v-model:from="filters.from" v-model:to="filters.to" :label="$t('operations.date')" />
                    </FilterBar>
                </div>

                <div class="ym-table-scroll">
                    <table class="ym-grid-table">
                        <thead>
                            <tr>
                                <th>{{ $t('operations.sessionCode') }}</th>
                                <SortTh field="session_date" :label="$t('operations.date')" :state="filters" @sort="toggleSort" />
                                <th>{{ $t('operations.time') }}</th>
                                <th>{{ $t('operations.class') }}</th>
                                <th>{{ $t('operations.coach') }}</th>
                                <th>{{ $t('operations.branch') }}</th>
                                <th>{{ $t('operations.room') }}</th>
                                <th>{{ $t('operations.booked') }}</th>
                                <SortTh field="status" :label="$t('operations.status')" :state="filters" @sort="toggleSort" />
                                <th class="is-actions">{{ $t('operations.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="session in sessions.data" :key="session.id" class="ym-clickable-row" @click="openSession(session.id)">
                                <td class="ym-num is-muted">{{ session.reference }}</td>
                                <td class="ym-num">{{ session.session_date }}</td>
                                <td class="ym-num">{{ session.start_time }}-{{ session.end_time }}</td>
                                <td class="is-strong">{{ session.class_type_name }}</td>
                                <td class="is-muted">{{ session.coach_name }}</td>
                                <td class="is-muted">{{ session.branch_name }}</td>
                                <td class="is-muted">{{ session.room_name }}</td>
                                <td>
                                    <span class="ym-fill" :class="fillTone(session)">
                                        <span class="ym-fill-bar"><span :style="{ width: `${fillPercent(session)}%` }" /></span>
                                        <span class="ym-fill-text">
                                            {{ session.booked_count }}/{{ session.capacity }}
                                            <template v-if="session.waitlist_count"> · +{{ session.waitlist_count }}</template>
                                        </span>
                                    </span>
                                </td>
                                <td>
                                    <span class="ym-tag" :class="`ym-tag--${statusTone[session.status] ?? 'neutral'}`">
                                        {{ statusLabel(session.status) }}
                                    </span>
                                    <span v-if="session.is_overridden" class="ym-tag ym-tag--neutral ml-1">
                                        {{ $t('operations.overridden') }}
                                    </span>
                                </td>
                                <td class="is-actions">
                                    <div class="ym-row-actions">
                                        <button type="button" class="ym-btn ym-btn--outline ym-btn--sm" @click.stop="openSession(session.id)">
                                            {{ $t('operations.details') }}
                                        </button>
                                        <Link
                                            v-if="canManage"
                                            class="ym-btn ym-btn--outline ym-btn--sm"
                                            :href="route('operations.class-sessions.edit', session.id)"
                                            @click.stop
                                        >
                                            {{ $t('operations.edit') }}
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!sessions.data.length" class="ym-empty">
                    <i class="bi bi-calendar-x" />
                    <p>{{ $t('operations.noSessions') }}</p>
                </div>

                <div v-if="sessions.links.length > 3" class="ym-card-foot">
                    <div class="ym-pagination">
                        <Link
                            v-for="link in sessions.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                            preserve-scroll
                            preserve-state
                        />
                    </div>
                </div>
            </section>
        </template>

        <template v-else>
            <header class="ym-page-head">
                <div>
                    <h1 class="ym-page-title">
                        {{ $t('operations.schedules') }}
                        <span class="ym-count">{{ schedules.total }}</span>
                    </h1>
                    <p class="ym-page-sub">{{ $t('operations.manageSchedules') }}</p>
                </div>
                <div class="ym-page-actions">
                    <button
                        v-if="endpoints.generateSessions"
                        type="button"
                        class="ym-btn ym-btn--outline"
                        :disabled="generatingSessions"
                        @click="generateSessions"
                    >
                        <i class="bi bi-arrow-repeat" :class="{ 'ym-spin': generatingSessions }" />
                        {{ $t('operations.generateSessions') }}
                    </button>
                    <Link v-if="endpoints.createSchedule" :href="endpoints.createSchedule" class="ym-btn ym-btn--primary">
                        <i class="bi bi-plus-lg" /> {{ $t('operations.createSchedule') }}
                    </Link>
                </div>
            </header>

            <div class="ym-stats">
                <div class="ym-stat-card">
                    <p class="ym-stat-card-label">{{ $t('operations.upcomingSessionsCount') }}</p>
                    <p class="ym-stat-card-value">{{ stats.upcomingSessions }}</p>
                </div>
                <div class="ym-stat-card ym-stat-card--info">
                    <p class="ym-stat-card-label">{{ $t('operations.activeSchedules') }}</p>
                    <p class="ym-stat-card-value">{{ stats.activeSchedules }}</p>
                </div>
            </div>

            <section class="ym-card">
                <div class="ym-table-scroll">
                    <table class="ym-grid-table">
                        <thead>
                            <tr>
                                <th>{{ $t('operations.dayOfWeek') }}</th>
                                <th>{{ $t('operations.time') }}</th>
                                <th>{{ $t('operations.class') }}</th>
                                <th>{{ $t('operations.coach') }}</th>
                                <th>{{ $t('operations.branch') }}</th>
                                <th>{{ $t('operations.room') }}</th>
                                <th class="is-num">{{ $t('operations.capacity') }}</th>
                                <th class="is-num">{{ $t('operations.upcomingSessionsCount') }}</th>
                                <th>{{ $t('operations.generatedUntil') }}</th>
                                <th>{{ $t('operations.status') }}</th>
                                <th v-if="canManage" class="is-actions">{{ $t('operations.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="schedule in schedules.data" :key="schedule.id">
                                <td class="is-strong">{{ dayLabel(schedule.day_of_week) }}</td>
                                <td class="ym-num">
                                    {{ schedule.start_time }}-{{ schedule.end_time }}
                                    <span class="is-muted">· {{ $t('member.durationMinutes', { count: schedule.duration_minutes }) }}</span>
                                </td>
                                <td>{{ schedule.class_type_name }}</td>
                                <td class="is-muted">{{ schedule.coach_name }}</td>
                                <td class="is-muted">{{ schedule.branch_name }}</td>
                                <td class="is-muted">{{ schedule.room_name }}</td>
                                <td class="is-num is-muted">{{ schedule.capacity }}</td>
                                <td class="is-num">{{ schedule.upcoming_count }}</td>
                                <td class="ym-num is-muted">{{ schedule.last_session_date ?? '-' }}</td>
                                <td>
                                    <span class="ym-tag" :class="schedule.is_active ? 'ym-tag--ok' : 'ym-tag--neutral'">
                                        {{ schedule.is_active ? $t('operations.active') : $t('operations.inactive') }}
                                    </span>
                                </td>
                                <td v-if="canManage" class="is-actions">
                                    <div class="ym-row-actions">
                                        <Link
                                            class="ym-btn ym-btn--outline ym-btn--sm"
                                            :href="route('operations.class-schedules.edit', schedule.id)"
                                        >
                                            {{ $t('operations.edit') }}
                                        </Link>
                                        <button
                                            type="button"
                                            class="ym-btn ym-btn--danger-quiet ym-btn--sm"
                                            @click="pendingDeleteSchedule = schedule"
                                        >
                                            {{ $t('operations.delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!schedules.data.length" class="ym-empty">
                    <i class="bi bi-calendar-week" />
                    <p>{{ $t('operations.noSchedules') }}</p>
                </div>

                <div v-if="schedules.links.length > 3" class="ym-card-foot">
                    <div class="ym-pagination">
                        <Link
                            v-for="link in schedules.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                            preserve-scroll
                            preserve-state
                        />
                    </div>
                </div>
            </section>
        </template>

        <Modal :show="!!detail" :title="detail?.class_type_name ?? $t('operations.sessionDetails')" @close="closeSession">
            <div v-if="detail" class="ym-stack">
                <p v-if="detailLoading" class="ym-note"><i class="bi bi-arrow-repeat ym-spin" /> {{ $t('common.loading') }}</p>
                <p v-else-if="detailError" class="ym-callout ym-callout--danger">
                    <i class="bi bi-exclamation-triangle" /> <span>{{ $t('operations.sessionLoadFailed') }}</span>
                </p>
                <template v-else>
                    <dl class="ym-sd-facts">
                        <div>
                            <dt>{{ $t('operations.date') }}</dt>
                            <dd>{{ detail.session_date }}, {{ detail.start_time }}-{{ detail.end_time }}</dd>
                        </div>
                        <div>
                            <dt>{{ $t('operations.status') }}</dt>
                            <dd>
                                <span class="ym-tag" :class="`ym-tag--${statusTone[detail.status] ?? 'neutral'}`">{{ statusLabel(detail.status) }}</span>
                                <span v-if="detail.is_overridden" class="ym-tag ym-tag--neutral ml-1">{{ $t('operations.overridden') }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt>{{ $t('operations.coach') }}</dt>
                            <dd>{{ detail.coach_name }}</dd>
                        </div>
                        <div>
                            <dt>{{ $t('operations.room') }}</dt>
                            <dd>{{ detail.branch_name }} · {{ detail.room_name }}</dd>
                        </div>
                        <div>
                            <dt>{{ $t('operations.booked') }}</dt>
                            <dd>
                                <span class="ym-fill" :class="fillTone(detail)">
                                    <span class="ym-fill-bar"><span :style="{ width: `${fillPercent(detail)}%` }" /></span>
                                    <span class="ym-fill-text">{{ detail.booked_count }}/{{ detail.capacity }}</span>
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt>{{ $t('operations.spotsLeft') }}</dt>
                            <dd>{{ Math.max(0, detail.capacity - detail.booked_count) }}</dd>
                        </div>
                        <div>
                            <dt>{{ $t('operations.waitlist') }}</dt>
                            <dd>{{ detail.waitlist_count }}</dd>
                        </div>
                        <div>
                            <dt>{{ $t('operations.sessionCode') }}</dt>
                            <dd class="ym-num">{{ detail.reference }}</dd>
                        </div>
                    </dl>

                    <div v-if="detail.roster">
                        <p class="ym-label">{{ $t('operations.rosterTitle') }}</p>
                        <ul v-if="detail.roster.length" class="ym-sd-roster">
                            <li v-for="row in detail.roster" :key="row.id">
                                <span>
                                    {{ row.name }}
                                    <span class="ym-sd-roster-ref">{{ row.reference }}</span>
                                </span>
                                <span>
                                    <span v-if="row.attendance" class="ym-tag ym-tag--info mr-1">{{ attendanceLabel(row.attendance) }}</span>
                                    <span class="ym-tag" :class="`ym-tag--${enrollmentTone[row.status] ?? 'neutral'}`">{{ enrollmentLabel(row.status) }}</span>
                                </span>
                            </li>
                        </ul>
                        <p v-else class="ym-note">{{ $t('operations.rosterEmpty') }}</p>
                    </div>

                    <div v-if="confirmingCancel" class="ym-callout ym-callout--danger">
                        <i class="bi bi-exclamation-triangle" />
                        <span>{{ $t('operations.confirmCancelSession', { count: detail.booked_count }) }}</span>
                    </div>

                    <div class="ym-sd-actions">
                        <template v-if="confirmingCancel">
                            <button type="button" class="ym-btn ym-btn--outline" :disabled="cancelling" @click="confirmingCancel = false">
                                {{ $t('operations.keepSession') }}
                            </button>
                            <button type="button" class="ym-btn ym-btn--danger" :disabled="cancelling" @click="cancelSession">
                                <i v-if="cancelling" class="bi bi-arrow-repeat ym-spin" /> {{ $t('operations.cancelSession') }}
                            </button>
                        </template>
                        <template v-else>
                            <button v-if="detail.endpoints.cancel" type="button" class="ym-btn ym-btn--danger-quiet" @click="confirmingCancel = true">
                                {{ $t('operations.cancelSession') }}
                            </button>
                            <Link v-if="detail.endpoints.roster" :href="detail.endpoints.roster" class="ym-btn ym-btn--outline">
                                <i class="bi bi-person-check" /> {{ $t('coach.openRoster') }}
                            </Link>
                            <Link v-if="detail.endpoints.edit" :href="detail.endpoints.edit" class="ym-btn ym-btn--primary">
                                {{ $t('operations.edit') }}
                            </Link>
                        </template>
                    </div>
                </template>
            </div>
        </Modal>

        <Modal :show="!!pendingDeleteSchedule" :title="$t('operations.deleteScheduleTitle')" @close="pendingDeleteSchedule = null">
            <p class="ym-note">{{ $t('operations.confirmDeleteSchedule') }}</p>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="pendingDeleteSchedule = null">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn ym-btn--danger" @click="confirmDeleteSchedule">{{ $t('operations.delete') }}</button>
            </div>
        </Modal>
    </div>
</template>
