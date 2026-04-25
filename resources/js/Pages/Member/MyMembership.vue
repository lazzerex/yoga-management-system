<template>
    <AppLayout title="My Membership">
        <section class="ym-surface ym-section ym-reveal">
            <div class="ym-membership-banner">
                <div>
                    <p class="ym-kpi-label">Membership Card</p>
                    <h2 class="ym-title">Premium Flow Annual</h2>
                    <p class="ym-subtitle">Member ID YM-29814 · Started Jan 5, 2026</p>
                </div>
                <span class="ym-status-pill ym-status-pill--started">Active</span>
            </div>

            <div class="ym-kpi-grid">
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Renewal Date</p>
                    <p class="ym-kpi-value">Jan 5, 2027</p>
                    <p class="ym-kpi-note">Auto-renew is enabled</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Package Type</p>
                    <p class="ym-kpi-value">Premium</p>
                    <p class="ym-kpi-note">Unlimited classes + workshops</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Remaining Guest Passes</p>
                    <p class="ym-kpi-value">4</p>
                    <p class="ym-kpi-note">Resets on renewal</p>
                </article>
            </div>
        </section>

        <section class="ym-grid-split mt-4">
            <article class="ym-surface ym-section ym-reveal ym-reveal-delay-1">
                <h3 class="ym-subsection-title">Included Benefits</h3>
                <div class="ym-list">
                    <div
                        v-for="(benefit, index) in benefits"
                        :key="benefit.title"
                        class="ym-list-item ym-stagger-item"
                        :style="{ '--ym-stagger': `${index * 70}ms` }"
                    >
                        <div>
                            <strong>{{ benefit.title }}</strong>
                            <p class="ym-list-meta">{{ benefit.meta }}</p>
                        </div>
                        <span class="ym-chip">{{ benefit.limit }}</span>
                    </div>
                </div>
            </article>

            <article class="ym-surface ym-section ym-reveal ym-reveal-delay-2">
                <h3 class="ym-subsection-title">Renewal Timeline</h3>
                <div class="ym-list">
                    <div
                        v-for="(checkpoint, index) in renewalTimeline"
                        :key="checkpoint.date"
                        class="ym-list-item ym-stagger-item"
                        :style="{ '--ym-stagger': `${index * 70}ms` }"
                    >
                        <div>
                            <strong>{{ checkpoint.date }}</strong>
                            <p class="ym-list-meta">{{ checkpoint.note }}</p>
                        </div>
                        <span class="ym-tag">{{ checkpoint.status }}</span>
                    </div>
                </div>
                <p class="ym-note-banner mt-3">
                    You are all set for auto-renewal. Update payment details any time before Dec 28, 2026.
                </p>
            </article>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

const benefits = [
    { title: 'Unlimited Studio Classes', meta: 'All branches · Any level', limit: 'Unlimited' },
    { title: 'Wellness Workshop Credits', meta: 'Nutrition + mobility workshops', limit: '6 / year' },
    { title: '1:1 Coach Consultations', meta: 'Goal and posture check-in', limit: '4 / year' },
    { title: 'Guest Access', meta: 'Invite a friend to selected sessions', limit: '4 left' },
];

const renewalTimeline = [
    { date: 'Dec 02, 2026', note: 'Renewal reminder email', status: 'Upcoming' },
    { date: 'Dec 20, 2026', note: 'Payment method check window', status: 'Upcoming' },
    { date: 'Jan 05, 2027', note: 'Plan renews automatically', status: 'Scheduled' },
];
</script>
