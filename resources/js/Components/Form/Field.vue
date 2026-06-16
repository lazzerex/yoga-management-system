<script setup>
defineProps({
    label: { type: String, required: true },
    error: { type: String, default: '' },
});
</script>

<template>
    <label class="ym-field">
        <span class="ym-label">{{ label }}</span>
        <slot />
        <span v-if="error" class="ym-field-error">{{ error }}</span>
    </label>
</template>
