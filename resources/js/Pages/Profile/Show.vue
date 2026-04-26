<template>
    <AppLayout title="My Profile">
        <section class="ym-surface ym-section ym-profile-page-head">
            <div class="ym-profile-page-main">
                <span class="ym-profile-page-avatar">{{ initials }}</span>
                <div>
                    <h2 class="ym-title">{{ props.profile.name }}</h2>
                    <p class="ym-subtitle">@{{ props.profile.username }} · {{ roleLabel }}</p>
                </div>
            </div>

            <div class="ym-profile-page-tags">
                <span :class="['ym-role-badge', `ym-role-${props.profile.role}`]">{{ props.profile.role }}</span>
                <span class="ym-chip">Joined {{ props.profile.joined_at ?? 'Unknown' }}</span>
            </div>
        </section>

        <section class="ym-grid-split mt-4">
            <article class="ym-surface ym-section">
                <h3 class="ym-subsection-title">Account Details</h3>
                <dl class="ym-profile-field-list">
                    <div class="ym-profile-field-row">
                        <dt class="ym-profile-field-label">Full Name</dt>
                        <dd class="ym-profile-field-value">{{ props.profile.name }}</dd>
                    </div>
                    <div class="ym-profile-field-row">
                        <dt class="ym-profile-field-label">Username</dt>
                        <dd class="ym-profile-field-value">@{{ props.profile.username }}</dd>
                    </div>
                    <div class="ym-profile-field-row">
                        <dt class="ym-profile-field-label">Email</dt>
                        <dd class="ym-profile-field-value">{{ props.profile.email }}</dd>
                    </div>
                    <div class="ym-profile-field-row">
                        <dt class="ym-profile-field-label">Role</dt>
                        <dd class="ym-profile-field-value ym-profile-field-value--caps">{{ props.profile.role }}</dd>
                    </div>
                </dl>
            </article>

            <article class="ym-surface ym-section">
                <h3 class="ym-subsection-title">Security and Access</h3>

                <div class="ym-profile-security-grid">
                    <article class="ym-profile-stat-card">
                        <p class="ym-profile-stat-label">Two-Factor Authentication</p>
                        <p class="ym-profile-stat-value">
                            {{ props.security.two_factor_enabled ? 'Enabled' : 'Not Enabled' }}
                        </p>
                        <p class="ym-profile-stat-note">
                            {{
                                props.security.two_factor_enabled
                                    ? `Confirmed ${props.security.two_factor_confirmed_at}`
                                    : 'Enable 2FA to improve account security.'
                            }}
                        </p>
                    </article>

                    <article class="ym-profile-stat-card">
                        <p class="ym-profile-stat-label">Total Sign-ins</p>
                        <p class="ym-profile-stat-value">{{ props.loginStats.total_sign_ins }}</p>
                        <p class="ym-profile-stat-note">
                            Last sign-in: {{ props.loginStats.last_login_at ?? 'No sign-ins yet' }}
                        </p>
                    </article>
                </div>
            </article>
        </section>

        <section class="ym-surface ym-section mt-4">
            <h3 class="ym-subsection-title">Recent Sign-ins</h3>
            <p class="ym-subtitle">Latest account access sessions.</p>

            <div v-if="props.recentLogins.length" class="ym-profile-login-list mt-3">
                <article
                    v-for="session in props.recentLogins"
                    :key="`${session.logged_in_at}-${session.ip_address}`"
                    class="ym-profile-login-row"
                >
                    <div>
                        <p class="ym-profile-login-title">{{ formatDevice(session.device_type) }} Device</p>
                        <p class="ym-profile-login-meta">{{ session.logged_in_at ?? 'Unknown time' }}</p>
                    </div>
                    <p class="ym-profile-login-ip">{{ session.ip_address }}</p>
                </article>
            </div>

            <p v-else class="ym-note-banner mt-3">No login activity has been recorded yet.</p>
        </section>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

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
