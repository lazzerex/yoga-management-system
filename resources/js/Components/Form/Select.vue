<script setup>
defineProps({
    modelValue: { type: String, default: '' },
    options: { type: Array, required: true },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <select
        :value="modelValue"
        class="ym-select"
        @change="$emit('update:modelValue', $event.target.value)"
    >
        <option v-for="opt in options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
    </select>
</template>
