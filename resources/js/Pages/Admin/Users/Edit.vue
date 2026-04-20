<template>
    <AppLayout title="Edit User">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">Edit User</h2>
            <p class="ym-subtitle">Leave password blank to keep the existing one.</p>
            <form class="ym-form-grid" @submit.prevent="saveEdit">
                <label class="ym-field">
                    <span class="ym-label">Name</span>
                    <input v-model="form.name" type="text" class="ym-input" autocomplete="name" />
                    <span v-if="form.errors.name" class="ym-field-error">{{ form.errors.name }}</span>
                </label>
                <label class="ym-field">
                    <span class="ym-label">Username</span>
                    <input v-model="form.username" type="text" class="ym-input" autocomplete="username" />
                    <span v-if="form.errors.username" class="ym-field-error">{{ form.errors.username }}</span>
                </label>
                <label class="ym-field">
                    <span class="ym-label">Email</span>
                    <input v-model="form.email" type="email" class="ym-input" autocomplete="email" />
                    <span v-if="form.errors.email" class="ym-field-error">{{ form.errors.email }}</span>
                </label>
                <label class="ym-field">
                    <span class="ym-label">Role</span>
                    <select v-model="form.role" class="ym-select">
                        <option value="admin">admin</option>
                        <option value="coach">coach</option>
                        <option value="member">member</option>
                    </select>
                    <span v-if="form.errors.role" class="ym-field-error">{{ form.errors.role }}</span>
                </label>
                <label class="ym-field">
                    <span class="ym-label">New Password (optional)</span>
                    <input v-model="form.password" type="password" class="ym-input" autocomplete="new-password" />
                    <span v-if="form.errors.password" class="ym-field-error">{{ form.errors.password }}</span>
                </label>
                <label class="ym-field">
                    <span class="ym-label">Confirm New Password</span>
                    <input v-model="form.password_confirmation" type="password" class="ym-input" autocomplete="new-password" />
                </label>
                <div class="ym-actions">
                    <button type="submit" class="ym-btn-sm" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                    <Link href="/cms/admin/users" class="ym-btn-ghost">Cancel</Link>
                </div>
            </form>
        </section>
    </AppLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const page = usePage();
const user = page.props.user;

const form = useForm({
    name: user.name,
    username: user.username,
    email: user.email,
    role: user.role,
    password: '',
    password_confirmation: '',
});

const saveEdit = () => {
    form.patch(`/cms/admin/users/${user.id}`, {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
