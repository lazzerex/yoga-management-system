<script setup>
defineProps({
    modelValue: { type: String, default: '#000000' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="ym-color-wrap">
        <input
            type="color"
            class="ym-color-input"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <span class="ym-color-value">{{ modelValue }}</span>
    </div>
</template>