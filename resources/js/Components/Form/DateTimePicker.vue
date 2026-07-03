<script setup>
defineProps({
    modelValue: { type: String, default: '' },
    min: { type: String, default: '' },
    max: { type: String, default: '' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <input
        type="datetime-local"
        class="ym-input"
        :value="modelValue"
        :min="min"
        :max="max"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>