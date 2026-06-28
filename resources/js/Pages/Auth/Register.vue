<script setup>
import { useI18n } from 'vue-i18n';
import { Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthCard from '@/Components/Auth/AuthCard.vue';
import LabeledInput from '@/Components/Form/LabeledInput.vue';

const { t } = useI18n();

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'));
};
</script>

<template>
    <AuthCard :title="t('auth.createAccount')">
        <form @submit.prevent="submit" class="space-y-4">
            <LabeledInput
                id="name"
                v-model="form.name"
                :label="t('auth.name')"
                type="text"
                :placeholder="t('auth.namePlaceholder')"
                autocomplete="name"
                :error="form.errors.name"
            />

            <LabeledInput
                id="username"
                v-model="form.username"
                :label="t('auth.username')"
                type="text"
                :placeholder="t('auth.usernamePlaceholder')"
                autocomplete="username"
                :error="form.errors.username"
            />

            <LabeledInput
                id="email"
                v-model="form.email"
                :label="t('auth.email')"
                type="email"
                :placeholder="t('auth.emailPlaceholder')"
                autocomplete="email"
                :error="form.errors.email"
            />

            <LabeledInput
                id="password"
                v-model="form.password"
                :label="t('auth.password')"
                type="password"
                :placeholder="t('auth.passwordHint')"
                autocomplete="new-password"
                :error="form.errors.password"
            />

            <LabeledInput
                id="password_confirmation"
                v-model="form.password_confirmation"
                :label="t('auth.confirmPassword')"
                type="password"
                :placeholder="t('auth.confirmPasswordPlaceholder')"
                autocomplete="new-password"
                :error="form.errors.password_confirmation"
            />

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-md bg-neutral-900 text-white py-2 text-sm font-medium hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ form.processing ? t('auth.creatingAccount') : t('auth.createAccount') }}
            </button>

            <p class="text-sm text-neutral-600">
                {{ t('auth.alreadyHaveAccount') }}
                <Link :href="route('login')" class="font-medium text-teal-700 hover:underline">{{ t('auth.signIn') }}</Link>
            </p>
        </form>
    </AuthCard>
</template>
