<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';

const props = defineProps({
    user: Object,
    endpoints: Object,
});

const roles = [
    { label: t('admin.roles.admin'), value: 'admin' },
    { label: t('admin.roles.coach'), value: 'coach' },
    { label: t('admin.roles.member'), value: 'member' },
];

const form = useForm({
    name: props.user.name,
    username: props.user.username,
    email: props.user.email,
    role: props.user.role,
    password: '',
    password_confirmation: '',
});

const saveEdit = () => {
    form.patch(props.endpoints.update, {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default {
    layout: (h, page) => h(AppLayout, { title: t('admin.editUser') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('admin.editUser') }}</h2>
        <p class="ym-subtitle">{{ $t('admin.leavePasswordBlank') }}</p>
        <form class="ym-form-grid" @submit.prevent="saveEdit">
            <Field :label="$t('admin.name')" :error="form.errors.name">
                <TextInput v-model="form.name" autocomplete="name" />
            </Field>
            <Field :label="$t('admin.username')" :error="form.errors.username">
                <TextInput v-model="form.username" autocomplete="username" />
            </Field>
            <Field :label="$t('admin.email')" :error="form.errors.email">
                <TextInput v-model="form.email" type="email" autocomplete="email" />
            </Field>
            <Field :label="$t('admin.role')" :error="form.errors.role">
                <Select v-model="form.role" :options="roles" />
            </Field>
            <Field :label="$t('admin.newPassword')" :error="form.errors.password">
                <TextInput v-model="form.password" type="password" autocomplete="new-password" />
            </Field>
            <Field :label="$t('admin.confirmNewPassword')">
                <TextInput v-model="form.password_confirmation" type="password" autocomplete="new-password" />
            </Field>
            <div class="ym-actions">
                <button type="submit" class="ym-btn-sm" :disabled="form.processing">
                    {{ form.processing ? $t('admin.saving') : $t('admin.saveChanges') }}
                </button>
                <Link :href="endpoints.index" class="ym-btn-ghost">{{ $t('admin.cancel') }}</Link>
            </div>
        </form>
    </section>
</template>
