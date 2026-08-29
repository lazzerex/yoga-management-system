<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { currentLocale, trans as t } from 'laravel-vue-i18n';
import Modal from '@/Components/UI/Modal.vue';
import TabBar from '@/Components/UI/TabBar.vue';

const props = defineProps({
    sessions: Object,
    schedules: Object,
    stats: Object,
    endpoints: Object,
    canManage: Boolean,
});

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
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.upcomingSessionsCount') }}</p>
            <p class="ym-stat-value">{{ stats.upcomingSessions }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.activeSchedules') }}</p>
            <p class="ym-stat-value">{{ stats.activeSchedules }}</p>
        </div>
    </div>

    <TabBar v-model="activeTab" :tabs="tabs" />

    <template v-if="activeTab === 'sessions'">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">
                {{ $t('operations.sessions') }}
                <span class="ym-count-badge">{{ sessions.total }}</span>
            </h2>

            <div class="ym-table-wrap">
                <table class="ym-table">
                    <thead>
                        <tr>
                            <th class="ym-th">{{ $t('operations.date') }}</th>
                            <th class="ym-th">{{ $t('operations.time') }}</th>
                            <th class="ym-th">{{ $t('operations.class') }}</th>
                            <th class="ym-th">{{ $t('operations.coach') }}</th>
                            <th class="ym-th">{{ $t('operations.branch') }}</th>
                            <th class="ym-th">{{ $t('operations.room') }}</th>
                            <th class="ym-th">{{ $t('operations.status') }}</th>
                            <th v-if="canManage" class="ym-th">{{ $t('operations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="session in sessions.data" :key="session.id" class="ym-tr">
                            <td class="ym-td">{{ session.session_date }}</td>
                            <td class="ym-td">{{ session.start_time }}–{{ session.end_time }}</td>
                            <td class="ym-td font-medium">{{ session.class_type_name }}</td>
                            <td class="ym-td text-neutral-500">{{ session.coach_name }}</td>
                            <td class="ym-td text-neutral-500">{{ session.branch_name }}</td>
                            <td class="ym-td text-neutral-500">{{ session.room_name }}</td>
                            <td class="ym-td">
                                <span class="ym-status-pill">{{ statusLabel(session.status) }}</span>
                                <span v-if="session.is_overridden" class="ym-tag ml-1">{{ $t('operations.overridden') }}</span>
                            </td>
                            <td v-if="canManage" class="ym-td">
                                <Link class="ym-btn-outline" :href="route('operations.class-sessions.edit', session.id)">
                                    {{ $t('operations.edit') }}
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!sessions.data.length">
                            <td class="ym-td text-neutral-500" :colspan="canManage ? 8 : 7">{{ $t('operations.noSessions') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="sessions.links.length > 3" class="ym-pagination">
                <Link
                    v-for="link in sessions.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                    preserve-scroll
                />
            </div>
        </section>
    </template>

    <template v-else>
        <section class="ym-surface ym-section">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="ym-title">
                        {{ $t('operations.schedules') }}
                        <span class="ym-count-badge">{{ schedules.total }}</span>
                    </h2>
                    <p class="ym-subtitle">{{ $t('operations.manageSchedules') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        v-if="endpoints.generateSessions"
                        type="button"
                        class="ym-btn-outline"
                        :disabled="generatingSessions"
                        @click="generateSessions"
                    >
                        {{ $t('operations.generateSessions') }}
                    </button>
                    <Link v-if="endpoints.createSchedule" :href="endpoints.createSchedule" class="ym-btn-sm">
                        {{ $t('operations.createSchedule') }}
                    </Link>
                </div>
            </div>

            <div class="ym-table-wrap">
                <table class="ym-table">
                    <thead>
                        <tr>
                            <th class="ym-th">{{ $t('operations.dayOfWeek') }}</th>
                            <th class="ym-th">{{ $t('operations.startTime') }}</th>
                            <th class="ym-th">{{ $t('operations.class') }}</th>
                            <th class="ym-th">{{ $t('operations.coach') }}</th>
                            <th class="ym-th">{{ $t('operations.branch') }}</th>
                            <th class="ym-th">{{ $t('operations.room') }}</th>
                            <th class="ym-th">{{ $t('operations.capacity') }}</th>
                            <th class="ym-th">{{ $t('operations.status') }}</th>
                            <th v-if="canManage" class="ym-th">{{ $t('operations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="schedule in schedules.data" :key="schedule.id" class="ym-tr">
                            <td class="ym-td font-medium">{{ dayLabel(schedule.day_of_week) }}</td>
                            <td class="ym-td">{{ schedule.start_time }}</td>
                            <td class="ym-td">{{ schedule.class_type_name }}</td>
                            <td class="ym-td text-neutral-500">{{ schedule.coach_name }}</td>
                            <td class="ym-td text-neutral-500">{{ schedule.branch_name }}</td>
                            <td class="ym-td text-neutral-500">{{ schedule.room_name }}</td>
                            <td class="ym-td text-neutral-500">{{ schedule.capacity }}</td>
                            <td class="ym-td">
                                <span :class="['ym-role-badge', schedule.is_active ? 'ym-role-coach' : 'ym-role-member']">
                                    {{ schedule.is_active ? $t('operations.active') : $t('operations.inactive') }}
                                </span>
                            </td>
                            <td v-if="canManage" class="ym-td">
                                <div class="ym-inline-actions">
                                    <Link class="ym-btn-outline" :href="route('operations.class-schedules.edit', schedule.id)">
                                        {{ $t('operations.edit') }}
                                    </Link>
                                    <button type="button" class="ym-btn-danger" @click="pendingDeleteSchedule = schedule">
                                        {{ $t('operations.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!schedules.data.length">
                            <td class="ym-td text-neutral-500" :colspan="canManage ? 9 : 8">{{ $t('operations.noSchedules') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="schedules.links.length > 3" class="ym-pagination">
                <Link
                    v-for="link in schedules.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                    preserve-scroll
                />
            </div>
        </section>
    </template>

    <Modal :show="!!pendingDeleteSchedule" :title="$t('operations.deleteScheduleTitle')" @close="pendingDeleteSchedule = null">
        <p class="ym-card-note">{{ $t('operations.confirmDeleteSchedule') }}</p>
        <div class="ym-confirm-modal-actions">
            <button type="button" class="ym-btn-outline" @click="pendingDeleteSchedule = null">{{ $t('common.cancel') }}</button>
            <button type="button" class="ym-btn-danger" @click="confirmDeleteSchedule">{{ $t('operations.delete') }}</button>
        </div>
    </Modal>
</template>
