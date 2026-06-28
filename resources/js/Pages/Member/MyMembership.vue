<script setup>
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const { t } = useI18n();

const benefits = [
    { title: t('member.unlimitedOrQuantity', { value: 'Unlimited Studio Classes' }), meta: 'All branches · Any level', limit: 'Unlimited' },
    { title: t('member.unlimitedOrQuantity', { value: 'Wellness Workshop Credits' }), meta: 'Nutrition + mobility workshops', limit: '6 / year' },
    { title: t('member.unlimitedOrQuantity', { value: '1:1 Coach Consultations' }), meta: 'Goal and posture check-in', limit: '4 / year' },
    { title: t('member.unlimitedOrQuantity', { value: 'Guest Access' }), meta: 'Invite a friend to selected sessions', limit: '4 left' },
];

const renewalTimeline = [
    { date: 'Dec 02, 2026', note: 'Renewal reminder email', status: t('common.noData') },
    { date: 'Dec 20, 2026', note: 'Payment method check window', status: t('common.loading') },
    { date: 'Jan 05, 2027', note: 'Plan renews automatically', status: t('common.save') },
];
</script>

<template>
    <AppLayout :title="t('member.myMembership')">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ t('member.renewalDate') }}</p>
                <p class="ym-stat-value">Jan 5, 2027</p>
                <p class="ym-stat-note">{{ t('member.autoRenewEnabled') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ t('member.packageType') }}</p>
                <p class="ym-stat-value">Premium</p>
                <p class="ym-stat-note">{{ t('member.unlimitedClassesWorkshops') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ t('member.guestPasses') }}</p>
                <p class="ym-stat-value">4</p>
                <p class="ym-stat-note">{{ t('member.remainingThisCycle') }}</p>
            </div>
        </div>

        <div class="ym-pane">
            <div class="ym-pane-head">
                <div class="ym-pane-title-wrap">
                    <i class="bi bi-person-vcard ym-pane-icon" />
                    <h2 class="ym-pane-title">{{ t('member.membershipRecord') }}</h2>
                </div>
                <span class="ym-status-pill ym-status-pill--started">{{ t('member.active') }}</span>
            </div>
            <div class="ym-pane-body">
                <div class="ym-plan-header">
                    <p class="ym-plan-overline">{{ t('member.memberId', { id: 'YM-29814' }) }}</p>
                    <p class="ym-plan-name">{{ t('member.planName') }}</p>
                    <p class="ym-plan-sub">{{ t('member.planSub', { start: 'Jan 5, 2026', end: 'Jan 5, 2027' }) }}</p>
                </div>
            </div>
        </div>

        <div class="ym-page-cols">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-list-check ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ t('member.includedBenefits') }}</h2>
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
                        <i class="bi bi-calendar3 ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ t('member.renewalTimeline') }}</h2>
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
                        <i class="bi bi-info-circle ym-info-icon" />
                        <span>{{ t('member.autoRenewNote', { date: 'Dec 28, 2026' }) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>