<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Textarea from '@/Components/Form/Textarea.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';

const props = defineProps({
    studentProfile: Object,
    canViewMedical: Boolean,
    endpoints: Object,
});

const form = useForm({
    emergency_contact_name: props.studentProfile.emergency_contact_name ?? '',
    emergency_contact_phone: props.studentProfile.emergency_contact_phone ?? '',
    goals: props.studentProfile.goals ?? '',
    is_active: props.studentProfile.is_active,
    ...(props.canViewMedical ? { medical_notes: props.studentProfile.medical_notes ?? '' } : {}),
});

const submit = () => {
    form.patch(props.endpoints.update);
};
</script>

<template>
    <AppLayout :title="$t('operations.editStudent')">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">{{ $t('operations.editStudent') }}</h2>
            <p class="ym-subtitle">{{ studentProfile.user_name }}</p>

            <form class="ym-form-grid" @submit.prevent="submit">
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
                        {{ $t('operations.saveStudent') }}
                    </button>
                    <Link :href="endpoints.index" class="ym-btn-ghost">{{ $t('common.cancel') }}</Link>
                </div>
            </form>
        </section>
    </AppLayout>
</template>
