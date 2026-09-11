<script setup>
defineProps({
    from: { type: String, default: '' },
    to: { type: String, default: '' },
    label: { type: String, default: '' },
});

const emit = defineEmits(['update:from', 'update:to']);
</script>

<template>
    <div class="ym-daterange">
        <span v-if="label" class="ym-daterange-label">{{ label }}</span>
        <div class="ym-daterange-row">
            <input
                :value="from"
                type="date"
                class="ym-log-filter-select"
                :max="to || null"
                :aria-label="$t('common.from')"
                @input="emit('update:from', $event.target.value)"
            />
            <span class="ym-daterange-sep">-</span>
            <input
                :value="to"
                type="date"
                class="ym-log-filter-select"
                :min="from || null"
                :aria-label="$t('common.to')"
                @input="emit('update:to', $event.target.value)"
            />
        </div>
    </div>
</template>
