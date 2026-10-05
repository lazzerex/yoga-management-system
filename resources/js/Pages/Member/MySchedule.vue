<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { getActiveLanguage, trans as t } from 'laravel-vue-i18n';
import Modal from '@/Components/UI/Modal.vue';
import WeekCalendar from '@/Components/UI/WeekCalendar.vue';

const props = defineProps({
    sessions: Array,
    week: String,
    endpoints: Object,
});

const locale = computed(() => (getActiveLanguage() === 'vi' ? 'vi-VN' : 'en-GB'));

const bookedCount = computed(() => props.sessions.filter((s) => s.status === 'booked').length);
const waitlistCount = computed(() => props.sessions.filter((s) => s.status === 'waitlisted').length);
const totalMinutes = computed(() =>
    props.sessions
        .filter((s) => s.status === 'booked')
        .reduce((sum, s) => sum + (Number(s.end_time.slice(0, 2)) * 60 + Number(s.end_time.slice(3)))
            - (Number(s.start_time.slice(0, 2)) * 60 + Number(s.start_time.slice(3))), 0),
);

const selected = ref(null);

const statusTone = { booked: 'ym-tag--ok', waitlisted: 'ym-tag--warn', cancelled: 'ym-tag--neutral' };
const statusText = (status) => ({
    booked: t('member.statusBooked'),
    waitlisted: t('member.waitlisted'),
    cancelled: t('member.classCancelled'),
})[status];

const formatDate = (iso) =>
    new Intl.DateTimeFormat(locale.value, { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date(`${iso}T00:00:00`));
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('member.mySchedule') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('member.personalWeeklyCalendar') }}</h1>
                <p class="ym-page-sub">{{ $t('member.weekCalendarSub') }}</p>
            </div>
            <div class="ym-page-actions">
                <Link :href="route('member.classes.book')" class="ym-btn ym-btn--primary">
                    <i class="bi bi-plus-lg" /> {{ $t('member.tabBook') }}
                </Link>
            </div>
        </header>

        <div class="ym-stats">
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('member.statusBooked') }}</p>
                <p class="ym-stat-card-value">{{ bookedCount }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--warn">
                <p class="ym-stat-card-label">{{ $t('member.waitlisted') }}</p>
                <p class="ym-stat-card-value">{{ waitlistCount }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--info">
                <p class="ym-stat-card-label">{{ $t('member.practiceTime') }}</p>
                <p class="ym-stat-card-value">{{ $t('member.durationMinutes', { count: totalMinutes }) }}</p>
            </div>
        </div>

        <WeekCalendar
            :sessions="sessions"
            :week="week"
            :reload-only="['sessions', 'week']"
            :empty-text="$t('member.noClassesThisWeek')"
            @select="selected = $event"
        >
            <template #event="{ session }">
                <span class="ym-wcal-title">{{ session.class_type_name }}</span>
                <span class="ym-wcal-meta">{{ session.room_name }}</span>
                <span v-if="session.status !== 'booked'" class="ym-wcal-meta">{{ statusText(session.status) }}</span>
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
                        <dt>{{ $t('operations.status') }}</dt>
                        <dd>
                            <span class="ym-tag" :class="statusTone[selected.status]">
                                {{ statusText(selected.status) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt>{{ $t('operations.coach') }}</dt>
                        <dd>{{ selected.coach_name }}</dd>
                    </div>
                    <div>
                        <dt>{{ $t('member.locationSection') }}</dt>
                        <dd>{{ selected.branch_name }} · {{ selected.room_name }}</dd>
                    </div>
                    <div>
                        <dt>{{ $t('operations.bookingReference') }}</dt>
                        <dd class="ym-num">{{ selected.reference }}</dd>
                    </div>
                    <div>
                        <dt>{{ $t('operations.sessionCode') }}</dt>
                        <dd class="ym-num">{{ selected.session_reference }}</dd>
                    </div>
                </dl>
                <div class="ym-sd-actions">
                    <Link :href="endpoints.bookings" class="ym-btn ym-btn--outline">
                        {{ $t('member.manageInBookings') }} <i class="bi bi-arrow-right" />
                    </Link>
                </div>
            </div>
        </Modal>
    </div>
</template>
