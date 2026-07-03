<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    label: { type: String, default: ''},
})

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="ym-checkbox-wrap">
        <input
            type="checkbox"
            class="ym-checkbox"
            :checked="modelValue"
            @change="$emit('update:modelValue', $event.target.checked)"
        />
        <span v-if="label" class="ym-checkbox-label">{{ label }}</span>
    </label>
</template>