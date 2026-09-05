<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Field from '@/Components/Form/Field.vue';
import FileInput from '@/Components/Form/FileInput.vue';
import TextInput from '@/Components/Form/TextInput.vue';

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
    security: {
        type: Object,
        required: true,
    },
    loginStats: {
        type: Object,
        required: true,
    },
    recentLogins: {
        type: Array,
        required: true,
    },
    endpoints: {
        type: Object,
        required: true,
    },
});

const editingAccount = ref(false);

const avatarForm = useForm({ avatar: null, remove_avatar: false });
const accountForm = useForm({
    name: props.profile.name,
    username: props.profile.username,
    email: props.profile.email,
});

// Inertia switches to FormData on its own once it sees a File.
const submitAvatar = () => avatarForm.post(props.endpoints.avatar, {
    preserveScroll: true,
    onSuccess: () => avatarForm.reset(),
});

const removeAvatar = () => {
    avatarForm.avatar = null;
    avatarForm.remove_avatar = true;
    submitAvatar();
};

const submitAccount = () => accountForm.put(props.endpoints.account, {
    preserveScroll: true,
    errorBag: 'updateProfileInformation',
    onSuccess: () => {
        editingAccount.value = false;
    },
});

const initials = computed(() => {
    const name = props.profile?.name?.trim();

    if (!name) {
        return 'GU';
    }

    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
});

const roleLabel = computed(() => {
    const role = props.profile?.role ?? 'member';
    return t(`admin.roles.${role}`);
});

const formatDevice = (deviceType) => (deviceType === 'mobile' ? t('profile.mobile') : t('profile.desktop'));
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default {
    layout: (h, page) => h(AppLayout, { title: t('profile.myProfile') }, () => page),
};
</script>


<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ props.profile.name }}</h1>
                <p class="ym-page-sub">@{{ props.profile.username }}</p>
            </div>
        </header>

        <div class="ym-split">
            <div>
                <section class="ym-card">
                    <div class="ym-card-head">
                        <h2 class="ym-card-title">{{ $t('profile.accountDetails') }}</h2>
                        <button
                            v-if="!editingAccount"
                            type="button"
                            class="ym-btn ym-btn--outline ym-btn--sm"
                            @click="editingAccount = true"
                        >
                            <i class="bi bi-pencil" /> {{ $t('profile.editAccount') }}
                        </button>
                    </div>

                    <form v-if="editingAccount" @submit.prevent="submitAccount">
                        <div class="ym-card-body">
                            <div class="ym-form-grid-2">
                                <Field :label="$t('profile.fullName')" :error="accountForm.errors.name">
                                    <TextInput v-model="accountForm.name" />
                                </Field>
                                <Field :label="$t('profile.username')" :error="accountForm.errors.username">
                                    <TextInput v-model="accountForm.username" />
                                </Field>
                                <Field class="ym-form-span" :label="$t('profile.email')" :error="accountForm.errors.email">
                                    <TextInput v-model="accountForm.email" type="email" />
                                </Field>
                            </div>
                        </div>
                        <div class="ym-form-foot">
                            <button type="button" class="ym-btn ym-btn--quiet" @click="editingAccount = false">
                                {{ $t('common.cancel') }}
                            </button>
                            <button type="submit" class="ym-btn ym-btn--primary" :disabled="accountForm.processing">
                                {{ $t('common.save') }}
                            </button>
                        </div>
                    </form>

                    <div v-else class="ym-card-body">
                        <dl class="ym-kv">
                            <div>
                                <dt>{{ $t('profile.username') }}</dt>
                                <dd>@{{ props.profile.username }}</dd>
                            </div>
                            <div>
                                <dt>{{ $t('profile.fullName') }}</dt>
                                <dd>{{ props.profile.name }}</dd>
                            </div>
                            <div>
                                <dt>{{ $t('profile.email') }}</dt>
                                <dd>{{ props.profile.email || $t('profile.notSet') }}</dd>
                            </div>
                            <div>
                                <dt>{{ $t('profile.role') }}</dt>
                                <dd><span class="ym-tag ym-tag--info">{{ roleLabel }}</span></dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <section class="ym-card">
                    <div class="ym-card-head">
                        <h2 class="ym-card-title">{{ $t('profile.securityAndAccess') }}</h2>
                    </div>
                    <div class="ym-card-body">
                        <div class="ym-figure-row">
                            <div>
                                <p class="ym-figure-label">{{ $t('profile.twoFactorAuth') }}</p>
                                <p class="ym-figure-value">
                                    <span class="ym-tag" :class="props.security.two_factor_enabled ? 'ym-tag--ok' : 'ym-tag--neutral'">
                                        {{ props.security.two_factor_enabled ? $t('profile.enabled') : $t('profile.notEnabled') }}
                                    </span>
                                </p>
                                <p class="ym-note mt-1">
                                    {{
                                        props.security.two_factor_enabled
                                            ? $t('profile.confirmedAt', { date: props.security.two_factor_confirmed_at })
                                            : $t('profile.enable2fa')
                                    }}
                                </p>
                            </div>
                            <div>
                                <p class="ym-figure-label">{{ $t('profile.totalSignIns') }}</p>
                                <p class="ym-figure-value">{{ props.loginStats.total_sign_ins }}</p>
                                <p class="ym-note mt-1">
                                    {{ $t('profile.last') }}: {{ props.loginStats.last_login_at ?? $t('profile.noSignInsYet') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="ym-card">
                    <div class="ym-card-head">
                        <h2 class="ym-card-title">{{ $t('profile.recentSignIns') }}</h2>
                    </div>

                    <div v-if="props.recentLogins.length" class="ym-table-scroll">
                        <table class="ym-grid-table">
                            <thead>
                                <tr>
                                    <th>{{ $t('profile.device') }}</th>
                                    <th>{{ $t('profile.dateAndTime') }}</th>
                                    <th>{{ $t('profile.ipAddress') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="session in props.recentLogins"
                                    :key="`${session.logged_in_at}-${session.ip_address}`"
                                >
                                    <td>
                                        <span class="ym-tag ym-tag--neutral">
                                            <i :class="session.device_type === 'mobile' ? 'bi bi-phone' : 'bi bi-laptop'" />
                                            {{ formatDevice(session.device_type) }}
                                        </span>
                                    </td>
                                    <td class="ym-num">{{ session.logged_in_at ?? $t('profile.unknown') }}</td>
                                    <td class="is-muted ym-num">{{ session.ip_address }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="ym-empty">
                        <i class="bi bi-box-arrow-in-right" />
                        <p>{{ $t('profile.noLoginActivity') }}</p>
                    </div>
                </section>
            </div>

            <aside class="ym-split-rail">
                <section class="ym-card ym-card--accent">
                    <div class="ym-card-head">
                        <h2 class="ym-card-title">{{ $t('profile.profilePicture') }}</h2>
                    </div>

                    <form @submit.prevent="submitAvatar">
                        <div class="ym-card-body">
                            <div class="ym-avatar-block">
                                <img
                                    v-if="props.profile.avatar_url"
                                    :src="props.profile.avatar_url"
                                    :alt="props.profile.name"
                                    class="ym-avatar-lg"
                                />
                                <div v-else class="ym-avatar-lg ym-avatar-lg--initials">{{ initials }}</div>
                                <p class="ym-avatar-name">{{ props.profile.name }}</p>
                                <span class="ym-tag ym-tag--info">{{ roleLabel }}</span>
                            </div>

                            <Field class="mt-4" :error="avatarForm.errors.avatar" :label="$t('profile.profilePicture')">
                                <FileInput
                                    v-model="avatarForm.avatar"
                                    accept="image/jpeg,image/png,image/webp"
                                    :hint="$t('profile.pictureHint')"
                                />
                            </Field>
                        </div>

                        <div class="ym-form-foot">
                            <button
                                v-if="props.profile.avatar_url"
                                type="button"
                                class="ym-btn ym-btn--quiet ym-form-foot-lead"
                                :disabled="avatarForm.processing"
                                @click="removeAvatar"
                            >
                                {{ $t('common.remove') }}
                            </button>
                            <button
                                type="submit"
                                class="ym-btn ym-btn--primary"
                                :disabled="avatarForm.processing || !avatarForm.avatar"
                            >
                                {{ $t('common.save') }}
                            </button>
                        </div>
                    </form>
                </section>

                <section class="ym-card">
                    <div class="ym-card-body">
                        <dl class="ym-kv">
                            <div>
                                <dt>{{ $t('profile.joined') }}</dt>
                                <dd>{{ props.profile.joined_at ?? $t('profile.unknown') }}</dd>
                            </div>
                            <div>
                                <dt>{{ $t('profile.lastSignIn') }}</dt>
                                <dd>{{ props.loginStats.last_login_at ?? $t('profile.never') }}</dd>
                            </div>
                            <div>
                                <dt>{{ $t('profile.sessionsTotal') }}</dt>
                                <dd class="ym-num">{{ props.loginStats.total_sign_ins }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>
