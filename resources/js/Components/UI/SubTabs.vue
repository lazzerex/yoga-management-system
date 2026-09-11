<script>
// Module scope, so it survives the remount an Inertia visit causes. Without it the pill
// would render already in place and there would be nothing to animate from.
const lastIndex = new Map();
</script>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    tabs: { type: Array, required: true },
    group: { type: String, required: true },
});

const wrap = ref(null);
const indicatorStyle = ref({ opacity: 0 });

const activeIndex = computed(() => Math.max(0, props.tabs.findIndex((tab) => tab.active)));

const place = (index) => {
    const el = wrap.value?.querySelectorAll('.ym-subtab')[index];

    if (el) {
        indicatorStyle.value = { left: `${el.offsetLeft}px`, width: `${el.offsetWidth}px`, opacity: 1 };
    }
};

onMounted(() => {
    place(lastIndex.get(props.group) ?? activeIndex.value);

    // Two frames: the start position has to be painted before the move, or the browser
    // coalesces both writes and the pill jumps instead of sliding.
    requestAnimationFrame(() => requestAnimationFrame(() => place(activeIndex.value)));

    lastIndex.set(props.group, activeIndex.value);
});
</script>

<template>
    <div ref="wrap" class="ym-subtabs">
        <span class="ym-subtab-indicator" :style="indicatorStyle" />
        <Link
            v-for="tab in tabs"
            :key="tab.href"
            :href="tab.href"
            class="ym-subtab"
            :class="{ 'is-active': tab.active }"
        >
            {{ tab.label }}
        </Link>
    </div>
</template>
