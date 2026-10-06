<script setup>
import { ref, onMounted } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import AuthCard from '@/Components/Auth/AuthCard.vue';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineProps({
    status: {
        type: String,
        default: null,
    },
});

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const hasSubmitted = ref(false);

onMounted(() => {
    form.clearErrors();
});

const submit = () => {
    hasSubmitted.value = true;
    form.post(route('login'));
};
</script>

<template>
    <AuthCard :title="$t('auth.cmsLogin')">
        <form @submit.prevent="submit" class="space-y-4">
            <p v-if="status" class="rounded-md bg-teal-50 px-3 py-2 text-sm text-teal-800">
                {{ $t('auth.passwordResetDone') }}
            </p>

            <Field :label="$t('auth.username')" :error="form.errors.username">
                <TextInput
                    v-model="form.username"
                    type="text"
                    :placeholder="$t('auth.usernamePlaceholder')"
                    autocomplete="username"
                />
            </Field>

            <Field :label="$t('auth.password')" :error="hasSubmitted ? form.errors.password : ''">
                <TextInput
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    :placeholder="$t('auth.passwordPlaceholder')"
                />
            </Field>

            <div class="flex items-center justify-between gap-2">
                <label class="flex items-center gap-2 text-sm text-neutral-700">
                    <input v-model="form.remember" type="checkbox" class="rounded border-neutral-300" />
                    {{ $t('auth.rememberMe') }}
                </label>
                <Link :href="route('password.request')" class="text-sm font-medium text-teal-700 hover:underline">{{ $t('auth.forgotPassword') }}</Link>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-md bg-neutral-900 text-white py-2 text-sm font-medium hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ form.processing ? $t('auth.signingIn') : $t('auth.signIn') }}
            </button>

            <p class="text-sm text-neutral-600">
                {{ $t('auth.needAccount') }}
                <Link :href="route('register')" class="font-medium text-teal-700 hover:underline">{{ $t('auth.register') }}</Link>
            </p>
        </form>
    </AuthCard>
</template>
