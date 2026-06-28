<script setup>
import { useI18n } from 'vue-i18n';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';

const { t } = useI18n();

const props = defineProps({
    endpoints: Object,
});

const roles = [
    { label: t('admin.roles.admin'), value: 'admin' },
    { label: t('admin.roles.coach'), value: 'coach' },
    { label: t('admin.roles.member'), value: 'member' },
];

const form = useForm({
    name: '',
    username: '',
    email: '',
    role: 'member',
    password: '',
    password_confirmation: '',
});

const createUser = () => {
    form.post(props.endpoints.store, {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <AppLayout :title="t('admin.createUser')">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">{{ t('admin.createUser') }}</h2>
            <p class="ym-subtitle">{{ t('admin.addAccount') }}</p>
            <form class="ym-form-grid" @submit.prevent="createUser">
                <Field :label="t('admin.name')" :error="form.errors.name">
                    <TextInput v-model="form.name" autocomplete="name" />
                </Field>
                <Field :label="t('admin.username')" :error="form.errors.username">
                    <TextInput v-model="form.username" autocomplete="username" />
                </Field>
                <Field :label="t('admin.email')" :error="form.errors.email">
                    <TextInput v-model="form.email" type="email" autocomplete="email" />
                </Field>
                <Field :label="t('admin.role')" :error="form.errors.role">
                    <Select v-model="form.role" :options="roles" />
                </Field>
                <Field :label="t('auth.password')" :error="form.errors.password">
                    <TextInput v-model="form.password" type="password" autocomplete="new-password" />
                </Field>
                <Field :label="t('auth.confirmPassword')">
                    <TextInput v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                </Field>
                <div class="ym-actions">
                    <button type="submit" class="ym-btn-sm" :disabled="form.processing">
                        {{ form.processing ? t('admin.creating') : t('admin.createUser') }}
                    </button>
                    <Link :href="endpoints.index" class="ym-btn-ghost">{{ t('admin.cancel') }}</Link>
                </div>
            </form>
        </section>
    </AppLayout>
</template>
