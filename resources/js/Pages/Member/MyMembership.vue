<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';

const benefits = computed(() => [
    { title: t('member.unlimitedOrQuantity', { value: t('member.unlimitedStudioClasses') }), meta: t('member.allBranchesAnyLevel'), limit: t('member.unlimited') },
    { title: t('member.unlimitedOrQuantity', { value: t('member.wellnessWorkshopCredits') }), meta: t('member.nutritionMobilityWorkshops'), limit: t('member.sixPerYear') },
    { title: t('member.unlimitedOrQuantity', { value: t('member.coachConsultations') }), meta: t('member.goalAndPostureCheckin'), limit: t('member.fourPerYear') },
    { title: t('member.unlimitedOrQuantity', { value: t('member.guestAccess') }), meta: t('member.inviteFriendSessions'), limit: t('member.fourLeft') },
]);

const renewalTimeline = computed(() => [
    { date: 'Dec 02, 2026', note: t('member.renewalReminderEmail'), status: t('member.pending') },
    { date: 'Dec 20, 2026', note: t('member.paymentMethodCheckWindow'), status: t('member.scheduled') },
    { date: 'Jan 05, 2027', note: t('member.planRenewsAutomatically'), status: t('member.confirmed') },
]);
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default {
    layout: (h, page) => h(AppLayout, { title: t('member.myMembership') }, () => page),
};
</script>


<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('member.renewalDate') }}</p>
            <p class="ym-stat-value">Jan 5, 2027</p>
            <p class="ym-stat-note">{{ $t('member.autoRenewEnabled') }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('member.packageType') }}</p>
            <p class="ym-stat-value">{{ $t('member.premium') }}</p>
            <p class="ym-stat-note">{{ $t('member.unlimitedClassesWorkshops') }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('member.guestPasses') }}</p>
            <p class="ym-stat-value">4</p>
            <p class="ym-stat-note">{{ $t('member.remainingThisCycle') }}</p>
        </div>
    </div>

    <div class="ym-pane">
        <div class="ym-pane-head">
            <div class="ym-pane-title-wrap">
                <i class="bi bi-person-vcard ym-pane-icon" />
                <h2 class="ym-pane-title">{{ $t('member.membershipRecord') }}</h2>
            </div>
            <span class="ym-status-pill ym-status-pill--started">{{ $t('member.active') }}</span>
        </div>
        <div class="ym-pane-body">
            <div class="ym-plan-header">
                <p class="ym-plan-overline">{{ $t('member.memberId', { id: 'YM-29814' }) }}</p>
                <p class="ym-plan-name">{{ $t('member.planName') }}</p>
                <p class="ym-plan-sub">{{ $t('member.planSub', { start: 'Jan 5, 2026', end: 'Jan 5, 2027' }) }}</p>
            </div>
        </div>
    </div>

    <div class="ym-page-cols">
        <div class="ym-pane">
            <div class="ym-pane-head">
                <div class="ym-pane-title-wrap">
                    <i class="bi bi-list-check ym-pane-icon" />
                    <h2 class="ym-pane-title">{{ $t('member.includedBenefits') }}</h2>
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
                    <h2 class="ym-pane-title">{{ $t('member.renewalTimeline') }}</h2>
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
                    <span>{{ $t('member.autoRenewNote', { date: 'Dec 28, 2026' }) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>