<script setup>
import { computed } from 'vue';

// Shows VND grouped as 1.900.000 while the model keeps the plain digits (1900000),
// so what gets posted is still an integer number of dong.
const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    placeholder: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

// Leading zeros are dropped so a field seeded with "0" does not become "01.900.000".
const digitsOf = (value) => String(value ?? '').replace(/\D/g, '').replace(/^0+(?=\d)/, '');
const group = (digits) => digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

const display = computed(() => {
    const digits = digitsOf(props.modelValue);

    return digits ? group(digits) : '';
});

const onInput = (event) => {
    const el = event.target;
    const caret = el.selectionStart ?? el.value.length;
    const digitsBeforeCaret = el.value.slice(0, caret).replace(/\D/g, '').length;
    const digits = digitsOf(el.value);
    const formatted = digits ? group(digits) : '';

    // Written back by hand: when only separators move, the model value is unchanged
    // and Vue would skip the re-render, leaving the typed character on screen.
    el.value = formatted;

    let position = 0;
    let seen = 0;

    while (position < formatted.length && seen < digitsBeforeCaret) {
        if (/\d/.test(formatted[position])) {
            seen += 1;
        }
        position += 1;
    }

    el.setSelectionRange(position, position);
    emit('update:modelValue', digits);
};
</script>

<template>
    <input
        type="text"
        inputmode="numeric"
        autocomplete="off"
        class="ym-input"
        :value="display"
        :placeholder="placeholder"
        @input="onInput"
    />
</template>
