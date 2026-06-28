<script setup>
import { useI18n } from 'vue-i18n';
import AuthCard from '@/Components/Auth/AuthCard.vue';
import LabeledInput from '@/Components/Form/LabeledInput.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const { t } = useI18n();

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'));
};
</script>

<template>
    <AuthCard :title="t('auth.cmsLogin')">
        <form @submit.prevent="submit" class="space-y-4">
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
                id="password"
                v-model="form.password"
                :label="t('auth.password')"
                type="password"
                autocomplete="current-password"
                :error="form.errors.password"
            />

            <label class="flex items-center gap-2 text-sm text-neutral-700">
                <input v-model="form.remember" type="checkbox" class="rounded border-neutral-300" />
                {{ t('auth.rememberMe') }}
            </label>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-md bg-neutral-900 text-white py-2 text-sm font-medium hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ form.processing ? t('auth.signingIn') : t('auth.signIn') }}
            </button>

            <p class="text-sm text-neutral-600">
                {{ t('auth.needAccount') }}
                <Link :href="route('register')" class="font-medium text-teal-700 hover:underline">{{ t('auth.register') }}</Link>
            </p>
        </form>
    </AuthCard>
</template>
