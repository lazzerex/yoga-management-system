<script setup>
import { computed } from 'vue';

const props = defineProps({
    field: { type: String, required: true },
    label: { type: String, required: true },
    // The page's filter object, so a header needs one binding instead of two.
    state: { type: Object, required: true },
    numeric: { type: Boolean, default: false },
});

const emit = defineEmits(['sort']);

const state = computed(() => (props.state.sort === props.field ? props.state.dir || 'asc' : ''));
</script>

<template>
    <th :class="{ 'is-num': numeric }" :aria-sort="state === 'asc' ? 'ascending' : state === 'desc' ? 'descending' : 'none'">
        <button type="button" class="ym-sort" :class="{ 'is-on': state }" @click="emit('sort', field)">
            <span>{{ label }}</span>
            <i
                class="bi ym-sort-ico"
                :class="state === 'asc' ? 'bi-arrow-up' : state === 'desc' ? 'bi-arrow-down' : 'bi-arrow-down-up'"
            />
        </button>
    </th>
</template>
