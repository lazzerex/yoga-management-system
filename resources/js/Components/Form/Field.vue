<script setup>
defineProps({
    label: { type: String, required: true },
    error: { type: String, default: '' },
    bare: { type: Boolean, default: false },
});
</script>

<template>
    <component :is="bare ? 'div' : 'label'" class="ym-field">
        <span class="ym-label">{{ label }}</span>
        <slot />
        <span v-if="error" class="ym-field-error">{{ error }}</span>
    </component>
</template>
