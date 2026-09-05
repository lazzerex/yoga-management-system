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
import { useFilters } from '@/composables/useFilters.js';

const props = defineProps({
    sessions: Object,
    schedules: Object,
    stats: Object,
    filters: Object,
    options: Object,
    endpoints: Object,
    canManage: Boolean,
});

const { filters, active, filterCount, reset, toggleSort } = useFilters(props.endpoints.index, props.filters, []);

const tabs = computed(() => {
    currentLocale.value;
    return [
        { key: 'sessions', label: t('operations.sessions') },
        { key: 'schedules', label: t('operations.schedules') },
    ];
});
const activeTab = ref('sessions');

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

        <template v-if="activeTab === 'sessions'">
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
                    <FilterBar :count="filterCount" :active="active" @reset="reset">
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
                                <SortTh field="session_date" :label="$t('operations.date')" :state="filters" @sort="toggleSort" />
                                <th>{{ $t('operations.time') }}</th>
                                <th>{{ $t('operations.class') }}</th>
                                <th>{{ $t('operations.coach') }}</th>
                                <th>{{ $t('operations.branch') }}</th>
                                <th>{{ $t('operations.room') }}</th>
                                <SortTh field="status" :label="$t('operations.status')" :state="filters" @sort="toggleSort" />
                                <th v-if="canManage" class="is-actions">{{ $t('operations.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="session in sessions.data" :key="session.id">
                                <td class="ym-num">{{ session.session_date }}</td>
                                <td class="ym-num">{{ session.start_time }}–{{ session.end_time }}</td>
                                <td class="is-strong">{{ session.class_type_name }}</td>
                                <td class="is-muted">{{ session.coach_name }}</td>
                                <td class="is-muted">{{ session.branch_name }}</td>
                                <td class="is-muted">{{ session.room_name }}</td>
                                <td>
                                    <span class="ym-tag" :class="`ym-tag--${statusTone[session.status] ?? 'neutral'}`">
                                        {{ statusLabel(session.status) }}
                                    </span>
                                    <span v-if="session.is_overridden" class="ym-tag ym-tag--neutral ml-1">
                                        {{ $t('operations.overridden') }}
                                    </span>
                                </td>
                                <td v-if="canManage" class="is-actions">
                                    <div class="ym-row-actions">
                                        <Link
                                            class="ym-btn ym-btn--outline ym-btn--sm"
                                            :href="route('operations.class-sessions.edit', session.id)"
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
                                <th>{{ $t('operations.startTime') }}</th>
                                <th>{{ $t('operations.class') }}</th>
                                <th>{{ $t('operations.coach') }}</th>
                                <th>{{ $t('operations.branch') }}</th>
                                <th>{{ $t('operations.room') }}</th>
                                <th class="is-num">{{ $t('operations.capacity') }}</th>
                                <th>{{ $t('operations.status') }}</th>
                                <th v-if="canManage" class="is-actions">{{ $t('operations.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="schedule in schedules.data" :key="schedule.id">
                                <td class="is-strong">{{ dayLabel(schedule.day_of_week) }}</td>
                                <td class="ym-num">{{ schedule.start_time }}</td>
                                <td>{{ schedule.class_type_name }}</td>
                                <td class="is-muted">{{ schedule.coach_name }}</td>
                                <td class="is-muted">{{ schedule.branch_name }}</td>
                                <td class="is-muted">{{ schedule.room_name }}</td>
                                <td class="is-num is-muted">{{ schedule.capacity }}</td>
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
                        />
                    </div>
                </div>
            </section>
        </template>

        <Modal :show="!!pendingDeleteSchedule" :title="$t('operations.deleteScheduleTitle')" @close="pendingDeleteSchedule = null">
            <p class="ym-note">{{ $t('operations.confirmDeleteSchedule') }}</p>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="pendingDeleteSchedule = null">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn ym-btn--danger" @click="confirmDeleteSchedule">{{ $t('operations.delete') }}</button>
            </div>
        </Modal>
    </div>
</template>
