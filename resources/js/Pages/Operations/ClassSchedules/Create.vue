<script setup>
import { computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';

const props = defineProps({
    branches: Array,
    rooms: Array,
    classTypes: Array,
    coachProfiles: Array,
    selectedBranchId: Number,
    endpoints: Object,
});

const branchOptions = computed(() => props.branches.map((b) => ({ value: String(b.id), label: b.name })));
const classTypeOptions = computed(() => props.classTypes.map((c) => ({ value: String(c.id), label: c.name })));
const coachOptions = computed(() => props.coachProfiles.map((c) => ({ value: String(c.id), label: c.name })));
const dayOptions = [
    { value: '1', label: 'operations.dayMonday' },
    { value: '2', label: 'operations.dayTuesday' },
    { value: '3', label: 'operations.dayWednesday' },
    { value: '4', label: 'operations.dayThursday' },
    { value: '5', label: 'operations.dayFriday' },
    { value: '6', label: 'operations.daySaturday' },
    { value: '0', label: 'operations.daySunday' },
];

const firstRoomForBranch = (branchId) => {
    const room = props.rooms.find((r) => String(r.branch_id) === branchId);
    return room ? String(room.id) : '';
};

const defaultBranchId = props.selectedBranchId ?? props.branches[0]?.id;
const initialBranchId = defaultBranchId ? String(defaultBranchId) : '';

const form = useForm({
    branch_id: initialBranchId,
    room_id: firstRoomForBranch(initialBranchId),
    class_type_id: props.classTypes[0] ? String(props.classTypes[0].id) : '',
    coach_profile_id: props.coachProfiles[0] ? String(props.coachProfiles[0].id) : '',
    day_of_week: '1',
    start_time: '18:00',
    duration_minutes: '60',
    capacity: '',
    is_active: true,
});

const roomOptions = computed(() =>
    props.rooms
        .filter((room) => String(room.branch_id) === form.branch_id)
        .map((room) => ({ value: String(room.id), label: room.name }))
);

watch(() => form.branch_id, (branchId) => {
    form.room_id = firstRoomForBranch(branchId);
});

const submit = () => {
    form.post(props.endpoints.store);
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.createSchedule') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('operations.createSchedule') }}</h2>
        <p class="ym-subtitle">{{ $t('operations.manageSchedules') }}</p>

        <form class="ym-form-grid" @submit.prevent="submit">
            <Field :label="$t('operations.branch')" :error="form.errors.branch_id">
                <Select v-model="form.branch_id" :options="branchOptions" />
            </Field>
            <Field :label="$t('operations.room')" :error="form.errors.room_id">
                <Select v-model="form.room_id" :options="roomOptions" />
            </Field>
            <Field :label="$t('operations.class')" :error="form.errors.class_type_id">
                <Select v-model="form.class_type_id" :options="classTypeOptions" />
            </Field>
            <Field :label="$t('operations.coach')" :error="form.errors.coach_profile_id">
                <Select v-model="form.coach_profile_id" :options="coachOptions" />
            </Field>
            <Field :label="$t('operations.dayOfWeek')" :error="form.errors.day_of_week">
                <Select v-model="form.day_of_week" :options="dayOptions.map((d) => ({ value: d.value, label: $t(d.label) }))" />
            </Field>
            <Field :label="$t('operations.startTime')" :error="form.errors.start_time">
                <TextInput v-model="form.start_time" type="time" />
            </Field>
            <Field :label="$t('operations.durationMinutes')" :error="form.errors.duration_minutes">
                <TextInput v-model="form.duration_minutes" type="number" />
            </Field>
            <Field :label="$t('operations.capacity')" :error="form.errors.capacity">
                <TextInput v-model="form.capacity" type="number" />
            </Field>
            <Checkbox v-model="form.is_active" :label="$t('operations.active')" />

            <div class="ym-actions">
                <button type="submit" class="ym-btn-sm" :disabled="form.processing">
                    {{ $t('operations.createSchedule') }}
                </button>
                <Link :href="endpoints.index" class="ym-btn-ghost">{{ $t('common.cancel') }}</Link>
            </div>
        </form>
    </section>
</template>
