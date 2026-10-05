<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { getActiveLanguage } from 'laravel-vue-i18n';
import Modal from '@/Components/UI/Modal.vue';
import WeekCalendar from '@/Components/UI/WeekCalendar.vue';

const props = defineProps({
    sessions: Array,
    week: String,
    endpoints: Object,
});

const locale = computed(() => (getActiveLanguage() === 'vi' ? 'vi-VN' : 'en-GB'));

const totals = computed(() => {
    const minutes = props.sessions.reduce((sum, s) =>
        sum + (Number(s.end_time.slice(0, 2)) * 60 + Number(s.end_time.slice(3)))
            - (Number(s.start_time.slice(0, 2)) * 60 + Number(s.start_time.slice(3))), 0);
    const seats = props.sessions.reduce((sum, s) => sum + s.capacity, 0);
    const students = props.sessions.reduce((sum, s) => sum + s.students, 0);

    return {
        classes: props.sessions.length,
        hours: Math.round((minutes / 60) * 10) / 10,
        students,
        fill: seats ? Math.round((students / seats) * 100) : 0,
    };
});

const selected = ref(null);

const fillPercent = (s) => (s.capacity ? Math.min(100, Math.round((s.students / s.capacity) * 100)) : 0);
const fillTone = (s) => (s.students >= s.capacity ? 'is-full' : fillPercent(s) >= 80 ? 'is-high' : '');

const formatDate = (iso) =>
    new Intl.DateTimeFormat(locale.value, { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date(`${iso}T00:00:00`));
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('coach.teachingSchedule') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('coach.weeklySchedule') }}</h1>
                <p class="ym-page-sub">{{ $t('coach.weekCalendarSub') }}</p>
            </div>
        </header>

        <div class="ym-stats">
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('coach.classesThisWeek') }}</p>
                <p class="ym-stat-card-value">{{ totals.classes }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--info">
                <p class="ym-stat-card-label">{{ $t('coach.totalHours') }}</p>
                <p class="ym-stat-card-value">{{ $t('coach.hours', { count: totals.hours }) }}</p>
            </div>
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('coach.totalStudents') }}</p>
                <p class="ym-stat-card-value">{{ totals.students }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--warn">
                <p class="ym-stat-card-label">{{ $t('coach.avgFillRate') }}</p>
                <p class="ym-stat-card-value">{{ totals.fill }}%</p>
            </div>
        </div>

        <WeekCalendar
            :sessions="sessions"
            :week="week"
            :reload-only="['sessions', 'week']"
            :empty-text="$t('coach.noClassesThisWeek')"
            @select="selected = $event"
        >
            <template #event="{ session }">
                <span class="ym-wcal-title">{{ session.class_type_name }}</span>
                <span class="ym-wcal-meta">{{ session.room_name }} · {{ session.students }}/{{ session.capacity }}</span>
            </template>
        </WeekCalendar>

        <Modal :show="!!selected" :title="selected?.class_type_name" @close="selected = null">
            <div v-if="selected" class="ym-stack">
                <dl class="ym-sd-facts">
                    <div>
                        <dt>{{ $t('member.scheduleSection') }}</dt>
                        <dd>{{ formatDate(selected.session_date) }}, {{ selected.start_time }}-{{ selected.end_time }}</dd>
                    </div>
                    <div>
                        <dt>{{ $t('member.locationSection') }}</dt>
                        <dd>{{ selected.branch_name }} · {{ selected.room_name }}</dd>
                    </div>
                    <div>
                        <dt>{{ $t('operations.capacity') }}</dt>
                        <dd>
                            <span class="ym-fill" :class="fillTone(selected)">
                                <span class="ym-fill-bar"><span :style="{ width: `${fillPercent(selected)}%` }" /></span>
                                <span class="ym-fill-text">{{ selected.students }}/{{ selected.capacity }}</span>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt>{{ $t('coach.waitlist') }}</dt>
                        <dd>{{ selected.waitlist_count }}</dd>
                    </div>
                    <div>
                        <dt>{{ $t('operations.sessionCode') }}</dt>
                        <dd class="ym-num">{{ selected.reference }}</dd>
                    </div>
                </dl>
                <div v-if="selected.rosterUrl" class="ym-sd-actions">
                    <Link :href="selected.rosterUrl" class="ym-btn ym-btn--primary">
                        <i class="bi bi-person-check" /> {{ $t('coach.openRoster') }}
                    </Link>
                </div>
            </div>
        </Modal>
    </div>
</template>
