<script setup>
defineProps({
    active: { type: Boolean, default: false },
});

defineEmits(['reset']);
</script>

<template>
    <div class="ym-log-filters">
        <slot />

        <button v-if="active" type="button" class="ym-log-clear-btn" @click="$emit('reset')">
            {{ $t('common.clearFilters') }}
        </button>
    </div>
</template>
