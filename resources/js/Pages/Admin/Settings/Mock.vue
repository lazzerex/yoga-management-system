<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
});

// Layout and copy for settings that are designed now and wired up later. Every
// control is disabled on purpose: a switch that writes nothing is worse than none.
const GROUPS = {
    General: [
        {
            key: 'centre',
            icon: 'bi-building',
            rows: [
                { key: 'centreName', type: 'text', value: 'Serenity Yoga' },
                { key: 'defaultBranch', type: 'select', options: ['branchDowntown', 'branchRiverside'] },
                { key: 'timezone', type: 'select', options: ['tzHoChiMinh', 'tzBangkok'] },
                { key: 'currency', type: 'select', options: ['currencyVnd'] },
            ],
        },
        {
            key: 'booking',
            icon: 'bi-calendar-check',
            rows: [
                { key: 'cancelCutoff', type: 'number', value: '6' },
                { key: 'waitlistPromote', type: 'toggle', value: true },
                { key: 'dailyBookingLimit', type: 'number', value: '2' },
            ],
        },
        {
            key: 'localisation',
            icon: 'bi-translate',
            rows: [
                { key: 'defaultLocale', type: 'select', options: ['localeVi', 'localeEn'] },
                { key: 'dateFormat', type: 'select', options: ['dateDmy', 'dateYmd'] },
            ],
        },
    ],
    'System / General': [
        {
            key: 'notifications',
            icon: 'bi-bell',
            rows: [
                { key: 'emailReminders', type: 'toggle', value: true },
                { key: 'reminderLeadTime', type: 'number', value: '3' },
                { key: 'fromAddress', type: 'text', value: 'no-reply@serenity.test' },
            ],
        },
        {
            key: 'files',
            icon: 'bi-folder',
            rows: [
                { key: 'maxUploadSize', type: 'number', value: '5' },
                { key: 'mediaDisk', type: 'select', options: ['diskLocal', 'diskS3'] },
            ],
        },
    ],
    'System / Advanced': [
        {
            key: 'operations',
            icon: 'bi-cpu',
            rows: [
                { key: 'queueDriver', type: 'select', options: ['queueDatabase', 'queueRedis'] },
                { key: 'logLevel', type: 'select', options: ['logDebug', 'logInfo', 'logError'] },
                { key: 'sessionRetention', type: 'number', value: '90' },
            ],
        },
        {
            key: 'maintenance',
            icon: 'bi-shield-check',
            rows: [
                { key: 'backupSchedule', type: 'select', options: ['backupDaily', 'backupWeekly'] },
                { key: 'maintenanceMode', type: 'toggle', value: false },
            ],
        },
    ],
};

const groups = computed(() => GROUPS[props.title] ?? GROUPS.General);

const label = (key) => t(`admin.settings.${key}`);
const hint = (key) => t(`admin.settings.${key}Hint`);
</script>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: (h, page) => h(AppLayout, { title: page.props.title }, () => page),
};
</script>

<template>
    <div class="ym-ui ym-form-page">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ title }}</h1>
                <p class="ym-page-sub">{{ $t('admin.settings.subtitle') }}</p>
            </div>
        </header>

        <p class="ym-callout ym-callout--warn mb-3">
            <i class="bi bi-info-circle" />
            <span>{{ $t('admin.settings.designNotice') }}</span>
        </p>

        <section v-for="group in groups" :key="group.key" class="ym-card">
            <div class="ym-card-head">
                <h2 class="ym-card-title">
                    <i :class="['bi', group.icon]" /> {{ label(group.key) }}
                </h2>
            </div>

            <div class="ym-card-body">
                <div v-for="row in group.rows" :key="row.key" class="ym-setting">
                    <div class="ym-setting-text">
                        <p class="ym-setting-label">{{ label(row.key) }}</p>
                        <p class="ym-setting-hint">{{ hint(row.key) }}</p>
                    </div>

                    <div class="ym-setting-control">
                        <select v-if="row.type === 'select'" class="ym-log-filter-select" disabled>
                            <option v-for="option in row.options" :key="option">{{ label(option) }}</option>
                        </select>

                        <span v-else-if="row.type === 'toggle'" class="ym-switch" :class="{ 'is-on': row.value }" aria-hidden="true">
                            <span class="ym-switch-dot" />
                        </span>

                        <input
                            v-else
                            class="ym-input"
                            :type="row.type === 'number' ? 'number' : 'text'"
                            :value="row.value"
                            disabled
                        />
                    </div>
                </div>
            </div>

            <div class="ym-form-foot">
                <span class="ym-form-foot-lead ym-note">{{ $t('admin.settings.notWired') }}</span>
                <button type="button" class="ym-btn ym-btn--primary" disabled>{{ $t('common.save') }}</button>
            </div>
        </section>
    </div>
</template>
