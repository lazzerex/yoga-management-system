<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';
import Textarea from '@/Components/Form/TextArea.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';
import FileInput from '@/Components/Form/FileInput.vue';

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
    avatar: null,
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
    <div class="ym-ui ym-form-page">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('operations.createStudent') }}</h1>
                <p class="ym-page-sub">{{ $t('operations.manageStudents') }}</p>
            </div>
        </header>

        <form @submit.prevent="submit">
            <section class="ym-card">
                <div class="ym-card-body">
                    <div class="ym-form-grid-2">
                        <Field class="ym-form-span" :label="$t('operations.studentUser')" :error="form.errors.user_id">
                            <Select v-model="form.user_id" :options="userOptions" />
                        </Field>
                        <Field :label="$t('operations.emergencyContactName')" :error="form.errors.emergency_contact_name">
                            <TextInput v-model="form.emergency_contact_name" />
                        </Field>
                        <Field :label="$t('operations.emergencyContactPhone')" :error="form.errors.emergency_contact_phone">
                            <TextInput v-model="form.emergency_contact_phone" />
                        </Field>
                        <Field class="ym-form-span" :label="$t('operations.goals')" :error="form.errors.goals">
                            <Textarea v-model="form.goals" />
                        </Field>
                        <Field
                            v-if="canViewMedical"
                            class="ym-form-span"
                            :label="$t('operations.medicalNotes')"
                            :error="form.errors.medical_notes"
                        >
                            <Textarea v-model="form.medical_notes" />
                        </Field>
                        <p v-else class="ym-callout ym-form-span">
                            <i class="bi bi-shield-lock" />
                            <span>{{ $t('operations.medicalNotesRestricted') }}</span>
                        </p>
                        <Field
                            class="ym-form-span"
                            :label="$t('operations.avatar')"
                            :error="form.errors.avatar"
                            :hint="$t('operations.avatarHint')"
                        >
                            <FileInput v-model="form.avatar" accept="image/jpeg,image/png,image/webp" />
                        </Field>
                    </div>

                    <div class="mt-4">
                        <Checkbox v-model="form.is_active" :label="$t('operations.active')" />
                    </div>
                </div>

                <div class="ym-form-foot">
                    <Link :href="endpoints.index" class="ym-btn ym-btn--quiet">{{ $t('common.cancel') }}</Link>
                    <button type="submit" class="ym-btn ym-btn--primary" :disabled="form.processing">
                        {{ $t('common.create') }}
                    </button>
                </div>
            </section>
        </form>
    </div>
</template>
