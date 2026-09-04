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
    <div class="ym-record-layout">
        <div class="ym-record-main ym-surface">
            <section class="ym-record-section">
                <div class="ym-record-section-head">
                    <p class="ym-record-group-title">{{ $t('profile.accountDetails') }}</p>
                    <button v-if="!editingAccount" type="button" class="ym-btn-outline" @click="editingAccount = true">
                        {{ $t('profile.editAccount') }}
                    </button>
                </div>

                <form v-if="editingAccount" class="ym-record-account-form" @submit.prevent="submitAccount">
                    <Field :label="$t('profile.fullName')" :error="accountForm.errors.name">
                        <TextInput v-model="accountForm.name" />
                    </Field>
                    <Field :label="$t('profile.username')" :error="accountForm.errors.username">
                        <TextInput v-model="accountForm.username" />
                    </Field>
                    <Field :label="$t('profile.email')" :error="accountForm.errors.email">
                        <TextInput v-model="accountForm.email" type="email" />
                    </Field>
                    <div class="ym-inline-actions">
                        <button type="submit" class="ym-btn-sm" :disabled="accountForm.processing">{{ $t('common.save') }}</button>
                        <button type="button" class="ym-btn-outline" @click="editingAccount = false">{{ $t('common.cancel') }}</button>
                    </div>
                </form>

                <template v-else>
                    <div class="ym-record-row ym-record-row--single">
                        <div class="ym-rf">
                            <p class="ym-rf-label">{{ $t('profile.username') }}</p>
                            <p class="ym-rf-value">@{{ props.profile.username }}</p>
                        </div>
                    </div>
                    <div class="ym-record-row">
                        <div class="ym-rf">
                            <p class="ym-rf-label">{{ $t('profile.fullName') }}</p>
                            <p class="ym-rf-value">{{ props.profile.name }}</p>
                        </div>
                        <div class="ym-rf">
                            <p class="ym-rf-label">{{ $t('profile.role') }}</p>
                            <p class="ym-rf-value">
                                <span :class="['ym-role-badge', `ym-role-${props.profile.role}`]">{{ roleLabel }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="ym-record-row ym-record-row--single ym-record-row--last">
                        <div class="ym-rf">
                            <p class="ym-rf-label">{{ $t('profile.email') }}</p>
                            <p class="ym-rf-value">{{ props.profile.email || $t('profile.notSet') }}</p>
                        </div>
                    </div>
                </template>
            </section>

            <section class="ym-record-section ym-record-section--divided">
                <p class="ym-record-group-title">{{ $t('profile.securityAndAccess') }}</p>
                <div class="ym-record-row ym-record-row--last">
                    <div class="ym-rf">
                        <p class="ym-rf-label">{{ $t('profile.twoFactorAuth') }}</p>
                        <p class="ym-rf-value">
                            {{ props.security.two_factor_enabled ? $t('profile.enabled') : $t('profile.notEnabled') }}
                        </p>
                        <p class="ym-rf-note">
                            {{
                                props.security.two_factor_enabled
                                    ? $t('profile.confirmedAt', { date: props.security.two_factor_confirmed_at })
                                    : $t('profile.enable2fa')
                            }}
                        </p>
                    </div>
                    <div class="ym-rf">
                        <p class="ym-rf-label">{{ $t('profile.totalSignIns') }}</p>
                        <p class="ym-rf-value">{{ props.loginStats.total_sign_ins }}</p>
                        <p class="ym-rf-note">
                            {{ $t('profile.last') }}: {{ props.loginStats.last_login_at ?? $t('profile.noSignInsYet') }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="ym-record-section ym-record-section--divided">
                <p class="ym-record-group-title">{{ $t('profile.recentSignIns') }}</p>
                <div v-if="props.recentLogins.length" class="ym-record-log-list">
                    <div class="ym-record-log-head">
                        <span>{{ $t('profile.device') }}</span>
                        <span>{{ $t('profile.dateAndTime') }}</span>
                        <span>{{ $t('profile.ipAddress') }}</span>
                    </div>
                    <div
                        v-for="session in props.recentLogins"
                        :key="`${session.logged_in_at}-${session.ip_address}`"
                        class="ym-record-log-row"
                    >
                        <span>
                            <span :class="['ym-device-badge', `ym-device-${session.device_type}`]">
                                {{ formatDevice(session.device_type) }}
                            </span>
                        </span>
                        <span class="ym-record-log-time">{{ session.logged_in_at ?? $t('profile.unknown') }}</span>
                        <span class="ym-record-log-ip">{{ session.ip_address }}</span>
                    </div>
                </div>
                <p v-else class="ym-record-empty">{{ $t('profile.noLoginActivity') }}</p>
            </section>
        </div>

        <aside class="ym-record-aside ym-surface">
            <div class="ym-record-avatar-zone">
                <img
                    v-if="props.profile.avatar_url"
                    :src="props.profile.avatar_url"
                    :alt="props.profile.name"
                    class="ym-record-avatar-img"
                />
                <div v-else class="ym-record-avatar">{{ initials }}</div>
                <p class="ym-record-avatar-name">{{ props.profile.name }}</p>
                <span :class="['ym-role-badge', `ym-role-${props.profile.role}`]">{{ roleLabel }}</span>
            </div>

            <form class="ym-record-avatar-form" @submit.prevent="submitAvatar">
                <Field :label="$t('profile.profilePicture')" :error="avatarForm.errors.avatar">
                    <FileInput v-model="avatarForm.avatar" accept="image/jpeg,image/png,image/webp" :hint="$t('profile.pictureHint')" />
                </Field>
                <div class="ym-inline-actions">
                    <button type="submit" class="ym-btn-sm" :disabled="avatarForm.processing || !avatarForm.avatar">
                        {{ $t('common.save') }}
                    </button>
                    <button
                        v-if="props.profile.avatar_url"
                        type="button"
                        class="ym-btn-outline"
                        :disabled="avatarForm.processing"
                        @click="removeAvatar"
                    >
                        {{ $t('common.remove') }}
                    </button>
                </div>
            </form>

            <div class="ym-record-meta-list">
                <div class="ym-record-meta-item">
                    <p class="ym-rf-label">{{ $t('profile.joined') }}</p>
                    <p class="ym-record-meta-val">{{ props.profile.joined_at ?? $t('profile.unknown') }}</p>
                </div>
                <div class="ym-record-meta-item">
                    <p class="ym-rf-label">{{ $t('profile.lastSignIn') }}</p>
                    <p class="ym-record-meta-val">{{ props.loginStats.last_login_at ?? $t('profile.never') }}</p>
                </div>
                <div class="ym-record-meta-item">
                    <p class="ym-rf-label">{{ $t('profile.sessionsTotal') }}</p>
                    <p class="ym-record-meta-val">{{ props.loginStats.total_sign_ins }}</p>
                </div>
            </div>
        </aside>
    </div>
</template>
