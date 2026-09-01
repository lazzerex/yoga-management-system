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
    <section class="ym-surface ym-section">
        <div class="ym-at-roster-head">
            <div>
                <h2 class="ym-title">
                    {{ $t('operations.rosterTitle') }}
                    <span class="ym-count-badge">{{ markedCount }} / {{ students.length }}</span>
                </h2>
                <p class="ym-subtitle">
                    {{ $t('operations.rosterSubtitle', { class: session.class_type_name, coach: session.coach_name }) }}
                </p>
                <p class="ym-subtitle">
                    {{ longDate }} · {{ session.start_time }} - {{ session.end_time }} · {{ session.branch_name }} / {{ session.room_name }}
                </p>
            </div>
            <Link :href="endpoints.back" class="ym-btn-outline ym-btn-sm">
                {{ $t('operations.attendanceBackToBoard') }}
            </Link>
        </div>

        <p v-if="!canManage" class="ym-info-row">
            <i class="bi bi-info-circle ym-info-icon" />
            <span>{{ $t('operations.rosterReadOnly') }}</span>
        </p>

        <div v-if="canManage && students.length" class="ym-at-bulk">
            <button v-for="status in STATUSES" :key="status" type="button" class="ym-btn-ghost ym-btn-sm" @click="setAll(status)">
                {{ $t(`operations.roster${status.charAt(0).toUpperCase()}${status.slice(1)}`) }}
            </button>
        </div>

        <div class="ym-table-wrap">
            <table class="ym-table">
                <thead>
                    <tr>
                        <th class="ym-th">{{ $t('operations.student') }}</th>
                        <th class="ym-th">{{ $t('operations.status') }}</th>
                        <th class="ym-th">{{ $t('operations.rosterNotes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="student in students" :key="student.enrollment_id" class="ym-tr">
                        <td class="ym-td font-medium">{{ student.student_name }}</td>
                        <td class="ym-td">
                            <div v-if="canManage" class="ym-at-seg">
                                <button
                                    v-for="status in STATUSES"
                                    :key="status"
                                    type="button"
                                    class="ym-at-seg-btn"
                                    :class="{ 'ym-at-seg-btn--on': entries[student.enrollment_id].status === status }"
                                    @click="entries[student.enrollment_id].status = status"
                                >
                                    {{ $t(`operations.roster${status.charAt(0).toUpperCase()}${status.slice(1)}`) }}
                                </button>
                            </div>
                            <span v-else class="ym-status-pill">
                                {{
                                    student.status
                                        ? $t(`operations.roster${student.status.charAt(0).toUpperCase()}${student.status.slice(1)}`)
                                        : $t('operations.rosterUnmarked')
                                }}
                            </span>
                        </td>
                        <td class="ym-td">
                            <input
                                v-if="canManage"
                                v-model="entries[student.enrollment_id].notes"
                                type="text"
                                maxlength="500"
                                class="ym-at-note-input"
                            />
                            <span v-else class="text-neutral-500">{{ student.notes || '—' }}</span>
                        </td>
                    </tr>
                    <tr v-if="!students.length">
                        <td class="ym-td text-neutral-500" colspan="3">{{ $t('operations.rosterEmpty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="canManage && students.length" class="ym-at-save">
            <button type="button" class="ym-btn-primary" :disabled="saving" @click="save">
                {{ $t('operations.rosterSave') }}
            </button>
        </div>
    </section>
</template>
