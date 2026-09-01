<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';
import Textarea from '@/Components/Form/Textarea.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';

const props = defineProps({
    users: Array,
    canViewMedical: Boolean,
    endpoints: Object,
});

const userOptions = computed(() => props.users.map((user) => ({ value: String(user.id), label: user.name })));

const form = useForm({
    user_id: props.users[0] ? String(props.users[0].id) : '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    goals: '',
    is_active: true,
    ...(props.canViewMedical ? { medical_notes: '' } : {}),
});

const submit = () => {
    form.post(props.endpoints.store);
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.createStudent') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('operations.createStudent') }}</h2>
        <p class="ym-subtitle">{{ $t('operations.manageStudents') }}</p>

        <form class="ym-form-grid" @submit.prevent="submit">
            <Field :label="$t('operations.studentUser')" :error="form.errors.user_id">
                <Select v-model="form.user_id" :options="userOptions" />
            </Field>
            <Field :label="$t('operations.emergencyContactName')" :error="form.errors.emergency_contact_name">
                <TextInput v-model="form.emergency_contact_name" />
            </Field>
            <Field :label="$t('operations.emergencyContactPhone')" :error="form.errors.emergency_contact_phone">
                <TextInput v-model="form.emergency_contact_phone" />
            </Field>
            <Field :label="$t('operations.goals')" :error="form.errors.goals">
                <Textarea v-model="form.goals" />
            </Field>
            <Field v-if="canViewMedical" :label="$t('operations.medicalNotes')" :error="form.errors.medical_notes">
                <Textarea v-model="form.medical_notes" />
            </Field>
            <p v-else class="ym-card-note">{{ $t('operations.medicalNotesRestricted') }}</p>
            <Checkbox v-model="form.is_active" :label="$t('operations.active')" />

            <div class="ym-actions">
                <button type="submit" class="ym-btn-sm" :disabled="form.processing">
                    {{ $t('common.create') }}
                </button>
                <Link :href="endpoints.index" class="ym-btn-ghost">{{ $t('common.cancel') }}</Link>
            </div>
        </form>
    </section>
</template>
