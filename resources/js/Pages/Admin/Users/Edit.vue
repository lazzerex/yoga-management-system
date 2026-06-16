<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';

const props = defineProps({
    user: Object,
    endpoints: Object,
});

const roles = [
    { label: 'admin', value: 'admin' },
    { label: 'coach', value: 'coach' },
    { label: 'member', value: 'member' },
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

<template>
    <AppLayout title="Edit User">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">Edit User</h2>
            <p class="ym-subtitle">Leave password blank to keep the existing one.</p>
            <form class="ym-form-grid" @submit.prevent="saveEdit">
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
                <Field label="New Password (optional)" :error="form.errors.password">
                    <TextInput v-model="form.password" type="password" autocomplete="new-password" />
                </Field>
                <Field label="Confirm New Password">
                    <TextInput v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                </Field>
                <div class="ym-actions">
                    <button type="submit" class="ym-btn-sm" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                    <Link :href="endpoints.index" class="ym-btn-ghost">Cancel</Link>
                </div>
            </form>
        </section>
    </AppLayout>
</template>
