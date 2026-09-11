<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { getActiveLanguage } from 'laravel-vue-i18n';

const props = defineProps({
    session: Object,
    students: Array,
    canManage: Boolean,
    endpoints: Object,
});

const STATUSES = ['present', 'late', 'absent'];
const STATUS_TONE = { present: 'ok', late: 'warn', absent: 'danger' };

const entries = reactive(
    Object.fromEntries(props.students.map((student) => [student.enrollment_id, { status: student.status, notes: student.notes ?? '' }])),
);

const saving = ref(false);

const markedCount = computed(() => Object.values(entries).filter((entry) => entry.status).length);

const locale = () => (getActiveLanguage() === 'vi' ? 'vi-VN' : 'en-US');

const longDate = computed(() =>
    new Intl.DateTimeFormat(locale(), { weekday: 'long', day: 'numeric', month: 'long' }).format(
        new Date(`${props.session.session_date}T00:00:00`),
    ),
);

const setAll = (status) => {
    props.students.forEach((student) => (entries[student.enrollment_id].status = status));
};

const save = () => {
    const payload = Object.entries(entries)
        .filter(([, entry]) => entry.status)
        .map(([enrollmentId, entry]) => ({
            enrollment_id: Number(enrollmentId),
            status: entry.status,
            notes: entry.notes || null,
        }));

    if (!payload.length) {
        return;
    }

    saving.value = true;
    router.post(props.endpoints.mark, { entries: payload }, {
        preserveScroll: true,
        onFinish: () => (saving.value = false),
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.rosterTitle') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">
                    {{ $t('operations.rosterTitle') }}
                    <span class="ym-count">{{ markedCount }} / {{ students.length }}</span>
                </h1>
                <p class="ym-page-sub">
                    {{ $t('operations.rosterSubtitle', { class: session.class_type_name, coach: session.coach_name }) }}
                </p>
            </div>
            <div class="ym-page-actions">
                <Link :href="endpoints.back" class="ym-btn ym-btn--outline">
                    {{ $t('operations.attendanceBackToBoard') }}
                </Link>
            </div>
        </header>

        <section class="ym-card">
            <div class="ym-card-meta">
                <dl class="ym-kv">
                    <div>
                        <dt>{{ $t('operations.date') }}</dt>
                        <dd>{{ longDate }}</dd>
                    </div>
                    <div>
                        <dt>{{ $t('operations.time') }}</dt>
                        <dd class="ym-num">{{ session.start_time }} - {{ session.end_time }}</dd>
                    </div>
                    <div>
                        <dt>{{ $t('operations.room') }}</dt>
                        <dd>{{ session.branch_name }} / {{ session.room_name }}</dd>
                    </div>
                </dl>
            </div>

            <div v-if="!canManage" class="ym-card-body">
                <p class="ym-callout">
                    <i class="bi bi-info-circle" />
                    <span>{{ $t('operations.rosterReadOnly') }}</span>
                </p>
            </div>

            <div v-else-if="students.length" class="ym-filter-band">
                <div class="ym-log-filters">
                    <span class="ym-figure-label">{{ $t('operations.status') }}</span>
                    <button
                        v-for="status in STATUSES"
                        :key="status"
                        type="button"
                        class="ym-btn ym-btn--outline ym-btn--sm"
                        @click="setAll(status)"
                    >
                        {{ $t(`operations.roster${status.charAt(0).toUpperCase()}${status.slice(1)}`) }}
                    </button>
                </div>
            </div>

            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <th>{{ $t('operations.student') }}</th>
                            <th>{{ $t('operations.status') }}</th>
                            <th>{{ $t('operations.rosterNotes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="student in students" :key="student.enrollment_id">
                            <td class="is-strong">{{ student.student_name }}</td>
                            <td>
                                <div v-if="canManage" class="ym-pick">
                                    <button
                                        v-for="status in STATUSES"
                                        :key="status"
                                        type="button"
                                        class="ym-pick-btn"
                                        :class="{ 'is-on': entries[student.enrollment_id].status === status }"
                                        :data-tone="STATUS_TONE[status]"
                                        @click="entries[student.enrollment_id].status = status"
                                    >
                                        {{ $t(`operations.roster${status.charAt(0).toUpperCase()}${status.slice(1)}`) }}
                                    </button>
                                </div>
                                <span
                                    v-else
                                    class="ym-tag"
                                    :class="`ym-tag--${student.status ? STATUS_TONE[student.status] : 'neutral'}`"
                                >
                                    {{
                                        student.status
                                            ? $t(`operations.roster${student.status.charAt(0).toUpperCase()}${student.status.slice(1)}`)
                                            : $t('operations.rosterUnmarked')
                                    }}
                                </span>
                            </td>
                            <td>
                                <input
                                    v-if="canManage"
                                    v-model="entries[student.enrollment_id].notes"
                                    type="text"
                                    maxlength="500"
                                    class="ym-input"
                                />
                                <span v-else class="is-muted">{{ student.notes || '-' }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!students.length" class="ym-empty">
                <i class="bi bi-people" />
                <p>{{ $t('operations.rosterEmpty') }}</p>
            </div>

            <div v-if="canManage && students.length" class="ym-form-foot">
                <p class="ym-form-foot-lead ym-note">{{ markedCount }} / {{ students.length }}</p>
                <button type="button" class="ym-btn ym-btn--primary" :disabled="saving" @click="save">
                    <i class="bi bi-check-lg" /> {{ $t('operations.rosterSave') }}
                </button>
            </div>
        </section>
    </div>
</template>
