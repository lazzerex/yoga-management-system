<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';

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
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default {
    layout: (h, page) => h(AppLayout, { title: t('admin.createUser') }, () => page),
};
</script>


<template>
    <div class="ym-ui ym-form-page">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('admin.createUser') }}</h1>
                <p class="ym-page-sub">{{ $t('admin.addAccount') }}</p>
            </div>
        </header>

        <form @submit.prevent="createUser">
            <section class="ym-card">
                <div class="ym-card-body">
                    <div class="ym-form-grid-2">
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
                    </div>
                </div>

                <div class="ym-card-subhead">
                    <h2 class="ym-card-title">{{ $t('auth.password') }}</h2>
                </div>
                <div class="ym-card-body">
                    <div class="ym-form-grid-2">
                        <Field :label="$t('auth.password')" :error="form.errors.password">
                            <TextInput v-model="form.password" type="password" autocomplete="new-password" />
                        </Field>
                        <Field :label="$t('auth.confirmPassword')">
                            <TextInput v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                        </Field>
                    </div>
                </div>

                <div class="ym-form-foot">
                    <Link :href="endpoints.index" class="ym-btn ym-btn--quiet">{{ $t('admin.cancel') }}</Link>
                    <button type="submit" class="ym-btn ym-btn--primary" :disabled="form.processing">
                        {{ form.processing ? $t('admin.creating') : $t('admin.createUser') }}
                    </button>
                </div>
            </section>
        </form>
    </div>
</template>
