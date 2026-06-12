<script setup>
import { computed } from 'vue';
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

const formatDevice = (deviceType) => (deviceType === 'mobile' ? 'Mobile' : 'Desktop');
</script>

<template>
    <AppLayout title="My Profile">
        <div class="ym-record-layout">
            <div class="ym-record-main ym-surface">
                <section class="ym-record-section">
                    <div class="ym-record-row ym-record-row--single">
                        <div class="ym-rf">
                            <p class="ym-rf-label">Username</p>
                            <p class="ym-rf-value">@{{ props.profile.username }}</p>
                        </div>
                    </div>
                    <div class="ym-record-row">
                        <div class="ym-rf">
                            <p class="ym-rf-label">Full Name</p>
                            <p class="ym-rf-value">{{ props.profile.name }}</p>
                        </div>
                        <div class="ym-rf">
                            <p class="ym-rf-label">Role</p>
                            <p class="ym-rf-value">
                                <span :class="['ym-role-badge', `ym-role-${props.profile.role}`]">{{ roleLabel }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="ym-record-row ym-record-row--single ym-record-row--last">
                        <div class="ym-rf">
                            <p class="ym-rf-label">Email</p>
                            <p class="ym-rf-value">{{ props.profile.email || 'Not set' }}</p>
                        </div>
                    </div>
                </section>

                <section class="ym-record-section ym-record-section--divided">
                    <p class="ym-record-group-title">Security and Access</p>
                    <div class="ym-record-row ym-record-row--last">
                        <div class="ym-rf">
                            <p class="ym-rf-label">Two-Factor Authentication</p>
                            <p class="ym-rf-value">
                                {{ props.security.two_factor_enabled ? 'Enabled' : 'Not Enabled' }}
                            </p>
                            <p class="ym-rf-note">
                                {{
                                    props.security.two_factor_enabled
                                        ? `Confirmed ${props.security.two_factor_confirmed_at}`
                                        : 'Enable 2FA to improve account security'
                                }}
                            </p>
                        </div>
                        <div class="ym-rf">
                            <p class="ym-rf-label">Total Sign-ins</p>
                            <p class="ym-rf-value">{{ props.loginStats.total_sign_ins }}</p>
                            <p class="ym-rf-note">
                                Last: {{ props.loginStats.last_login_at ?? 'No sign-ins yet' }}
                            </p>
                        </div>
                    </div>
                </section>

                <section class="ym-record-section ym-record-section--divided">
                    <p class="ym-record-group-title">Recent Sign-ins</p>
                    <div v-if="props.recentLogins.length" class="ym-record-log-list">
                        <div class="ym-record-log-head">
                            <span>Device</span>
                            <span>Date and Time</span>
                            <span>IP Address</span>
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
                            <span class="ym-record-log-time">{{ session.logged_in_at ?? 'Unknown' }}</span>
                            <span class="ym-record-log-ip">{{ session.ip_address }}</span>
                        </div>
                    </div>
                    <p v-else class="ym-record-empty">No login activity has been recorded yet.</p>
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
                        <p class="ym-rf-label">Joined</p>
                        <p class="ym-record-meta-val">{{ props.profile.joined_at ?? 'Unknown' }}</p>
                    </div>
                    <div class="ym-record-meta-item">
                        <p class="ym-rf-label">Last Sign-in</p>
                        <p class="ym-record-meta-val">{{ props.loginStats.last_login_at ?? 'Never' }}</p>
                    </div>
                    <div class="ym-record-meta-item">
                        <p class="ym-rf-label">Sessions Total</p>
                        <p class="ym-record-meta-val">{{ props.loginStats.total_sign_ins }}</p>
                    </div>
                </div>
            </aside>
        </div>
    </AppLayout>
</template>