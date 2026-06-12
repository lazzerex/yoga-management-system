<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCalendarDays, faCircleInfo, faIdCard, faListCheck } from '@fortawesome/free-solid-svg-icons';
import AppLayout from '@/Layouts/AppLayout.vue';

const benefits = [
    { title: 'Unlimited Studio Classes', meta: 'All branches Â· Any level', limit: 'Unlimited' },
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

<template>
    <AppLayout title="My Membership">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">Renewal Date</p>
                <p class="ym-stat-value">Jan 5, 2027</p>
                <p class="ym-stat-note">Auto-renew enabled</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Package Type</p>
                <p class="ym-stat-value">Premium</p>
                <p class="ym-stat-note">Unlimited classes + workshops</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Guest Passes</p>
                <p class="ym-stat-value">4</p>
                <p class="ym-stat-note">Remaining this cycle</p>
            </div>
        </div>

        <div class="ym-pane">
            <div class="ym-pane-head">
                <div class="ym-pane-title-wrap">
                    <FontAwesomeIcon :icon="faIdCard" class="ym-pane-icon" />
                    <h2 class="ym-pane-title">Membership Record</h2>
                </div>
                <span class="ym-status-pill ym-status-pill--started">Active</span>
            </div>
            <div class="ym-pane-body">
                <div class="ym-plan-header">
                    <p class="ym-plan-overline">Member ID YM-29814</p>
                    <p class="ym-plan-name">Premium Flow Annual</p>
                    <p class="ym-plan-sub">Started Jan 5, 2026 Â· Renews Jan 5, 2027</p>
                </div>
            </div>
        </div>

        <div class="ym-page-cols">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <FontAwesomeIcon :icon="faListCheck" class="ym-pane-icon" />
                        <h2 class="ym-pane-title">Included Benefits</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="benefit in benefits" :key="benefit.title" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ benefit.title }}</p>
                                <p class="ym-row-meta">{{ benefit.meta }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-chip">{{ benefit.limit }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <FontAwesomeIcon :icon="faCalendarDays" class="ym-pane-icon" />
                        <h2 class="ym-pane-title">Renewal Timeline</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="checkpoint in renewalTimeline" :key="checkpoint.date" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ checkpoint.date }}</p>
                                <p class="ym-row-meta">{{ checkpoint.note }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-tag">{{ checkpoint.status }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="ym-info-row">
                        <FontAwesomeIcon :icon="faCircleInfo" class="ym-info-icon" />
                        <span>You are set for auto-renewal. Update payment details any time before Dec 28, 2026.</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>