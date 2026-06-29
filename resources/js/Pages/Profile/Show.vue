<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

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
    return role.charAt(0).toUpperCase() + role.slice(1);
});

const formatDevice = (deviceType) => (deviceType === 'mobile' ? t('profile.mobile') : t('profile.desktop'));
</script>

<template>
    <AppLayout :title="$t('profile.myProfile')">
        <div class="ym-record-layout">
            <div class="ym-record-main ym-surface">
                <section class="ym-record-section">
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
                    <div class="ym-record-avatar">{{ initials }}</div>
                    <p class="ym-record-avatar-name">{{ props.profile.name }}</p>
                    <span :class="['ym-role-badge', `ym-role-${props.profile.role}`]">{{ roleLabel }}</span>
                </div>

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
    </AppLayout>
</template>
