<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({ data: { type: Object, required: true } });

const hours = computed(() => Math.floor(props.data.minutes / 60));
const minutes = computed(() => props.data.minutes % 60);
</script>

<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.hoursTaughtThisMonth') }}</p>
            <p class="ym-stat-value">{{ hours }}h {{ minutes }}m</p>
            <p class="ym-stat-note">
                <Link :href="data.url">{{ $t('dashboard.openAttendanceReports') }}</Link>
            </p>
        </div>
    </div>
</template>
