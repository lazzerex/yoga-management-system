<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthCard from '@/Components/Auth/AuthCard.vue';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm.store'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthCard :title="$t('auth.confirmTitle')">
        <form @submit.prevent="submit" class="space-y-4">
            <p class="text-sm text-neutral-600">{{ $t('auth.confirmIntro') }}</p>

            <Field :label="$t('auth.password')" :error="form.errors.password">
                <TextInput
                    v-model="form.password"
                    type="password"
                    :placeholder="$t('auth.passwordPlaceholder')"
                    autocomplete="current-password"
                />
            </Field>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-md bg-neutral-900 text-white py-2 text-sm font-medium hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ form.processing ? $t('auth.confirming') : $t('auth.confirm') }}
            </button>

            <p class="text-sm text-neutral-600">
                <Link :href="route('cms.dashboard')" class="font-medium text-teal-700 hover:underline">{{ $t('auth.backToDashboard') }}</Link>
            </p>
        </form>
    </AuthCard>
</template>
