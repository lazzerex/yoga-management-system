<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps({
    tabs: { type: Array, required: true },
    modelValue: { type: String, required: true },
});

const emit = defineEmits(['update:modelValue']);

const tabRefs = ref({});
const setTabRef = (key) => (el) => {
    if (el) tabRefs.value[key] = el;
};

const indicatorStyle = ref({ left: '0px', width: '0px' });

const updateIndicator = () => {
    const el = tabRefs.value[props.modelValue];
    if (!el) return;
    indicatorStyle.value = { left: `${el.offsetLeft}px`, width: `${el.offsetWidth}px` };
};

onMounted(() => nextTick(updateIndicator));
watch(() => props.modelValue, () => nextTick(updateIndicator));
watch(() => props.tabs, () => nextTick(updateIndicator));
</script>

<template>
    <div class="ym-tabbar">
        <button
            v-for="tab in tabs"
            :key="tab.key"
            :ref="setTabRef(tab.key)"
            type="button"
            class="ym-tabbar-btn"
            :class="{ 'ym-tabbar-btn--active': modelValue === tab.key }"
            @click="emit('update:modelValue', tab.key)"
        >
            {{ tab.label }}
        </button>
        <span class="ym-tabbar-indicator" :style="indicatorStyle" />
    </div>
</template>
