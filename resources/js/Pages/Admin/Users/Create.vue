<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';

const props = defineProps({
    endpoints: Object,
});

const roles = [
    { label: 'admin', value: 'admin' },
    { label: 'coach', value: 'coach' },
    { label: 'member', value: 'member' },
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
    <AppLayout title="Create User">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">Create User</h2>
            <p class="ym-subtitle">Add a new admin, coach, or member account.</p>
            <form class="ym-form-grid" @submit.prevent="createUser">
                <Field label="Name" :error="form.errors.name">
                    <TextInput v-model="form.name" autocomplete="name" />
                </Field>
                <Field label="Username" :error="form.errors.username">
                    <TextInput v-model="form.username" autocomplete="username" />
                </Field>
                <Field label="Email" :error="form.errors.email">
                    <TextInput v-model="form.email" type="email" autocomplete="email" />
                </Field>
                <Field label="Role" :error="form.errors.role">
                    <Select v-model="form.role" :options="roles" />
                </Field>
                <Field label="Password" :error="form.errors.password">
                    <TextInput v-model="form.password" type="password" autocomplete="new-password" />
                </Field>
                <Field label="Confirm Password">
                    <TextInput v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                </Field>
                <div class="ym-actions">
                    <button type="submit" class="ym-btn-sm" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create User' }}
                    </button>
                    <Link :href="endpoints.index" class="ym-btn-ghost">Cancel</Link>
                </div>
            </form>
        </section>
    </AppLayout>
</template>
