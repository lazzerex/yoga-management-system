<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    options: { type: Array, required: true },   // [{ value, label }]
    placeholder: { type: String, default: 'Select...' },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const containerRef = ref(null);

const isSelected = (val) => props.modelValue.includes(val);

const toggleOption = (val) => {
    const next = [...props.modelValue];
    const idx = next.indexOf(val);
    if (idx === -1) next.push(val);
    else next.splice(idx, 1);
    emit('update:modelValue', next);
};

const displayLabel = computed(() => {
    if (props.modelValue.length === 0) return props.placeholder;
    return props.modelValue
        .map((v) => props.options.find((o) => o.value === v)?.label ?? v)
        .join(', ');
});

const handleOutsideClick = (e) => {
    if (containerRef.value && !containerRef.value.contains(e.target)) {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('click', handleOutsideClick));
onBeforeUnmount(() => document.removeEventListener('click', handleOutsideClick));
</script>

<template>
    <div ref="containerRef" class="ym-multiselect" :class="{ 'ym-multiselect--open': open }">
        <button
            type="button"
            class="ym-multiselect-trigger"
            @click.stop="open = !open"
        >
            <span
                class="ym-multiselect-value"
                :class="{ 'ym-multiselect-placeholder': modelValue.length === 0 }"
            >
                {{ displayLabel }}
            </span>
            <i class="bi bi-chevron-down ym-multiselect-arrow" />
        </button>

        <div v-if="open" class="ym-multiselect-dropdown" @click.stop>
            <label
                v-for="opt in options"
                :key="opt.value"
                class="ym-multiselect-option"
            >
                <input
                    type="checkbox"
                    :checked="isSelected(opt.value)"
                    @change="toggleOption(opt.value)"
                />
                {{ opt.label }}
            </label>
        </div>
    </div>
</template>