<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthCard from '@/Components/Auth/AuthCard.vue';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';

defineProps({
    status: {
        type: String,
        default: null,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthCard :title="$t('auth.forgotTitle')">
        <form @submit.prevent="submit" class="space-y-4">
            <p class="text-sm text-neutral-600">{{ $t('auth.forgotIntro') }}</p>

            <p v-if="status" class="rounded-md bg-teal-50 px-3 py-2 text-sm text-teal-800">
                {{ $t('auth.resetLinkSent') }}
            </p>

            <Field :label="$t('auth.email')" :error="form.errors.email">
                <TextInput
                    v-model="form.email"
                    type="email"
                    :placeholder="$t('auth.emailPlaceholder')"
                    autocomplete="email"
                />
            </Field>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-md bg-neutral-900 text-white py-2 text-sm font-medium hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ form.processing ? $t('auth.sendingResetLink') : $t('auth.sendResetLink') }}
            </button>

            <p class="text-sm text-neutral-600">
                <Link :href="route('login')" class="font-medium text-teal-700 hover:underline">{{ $t('auth.backToLogin') }}</Link>
            </p>
        </form>
    </AuthCard>
</template>
