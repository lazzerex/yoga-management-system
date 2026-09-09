<script setup>
import { computed, ref } from 'vue';
import NavMenuLink from './NavMenuLink.vue';

defineOptions({ name: 'SidebarMenuItem' });

const props = defineProps({
    item: { type: Object, required: true },
    openItems: { type: Set, required: true },
    isActive: { type: Function, required: true },
    toggle: { type: Function, required: true },
    collapsed: { type: Boolean, default: false },
});

const itemKey = computed(() => props.item.href ?? props.item.labelKey);
const hasChildren = computed(() => (props.item.children ?? []).length > 0);

// .ym-side-nav clips overflow-x, so the panel is positioned fixed from the row's rect.
const row = ref(null);
const flyoutStyle = ref({});
const flyoutOpen = ref(false);

const openFlyout = () => {
    if (! props.collapsed || ! row.value) {
        return;
    }

    const rect = row.value.getBoundingClientRect();
    flyoutStyle.value = { top: `${rect.top}px`, left: `${rect.right}px` };
    flyoutOpen.value = true;
};

const closeFlyout = () => {
    flyoutOpen.value = false;
};

// Collapsed, the flyout already shows what the toggle would have revealed.
const onParentClick = () => {
    if (! props.collapsed) {
        props.toggle(itemKey.value);
    }
};
</script>

<template>
    <hr v-if="item.separator" class="ym-side-separator" />

    <NavMenuLink
        v-if="!hasChildren"
        :href="item.href"
        :label="item.label"
        :icon="item.icon"
        :icon-color="item.iconColor"
        :badge="item.badge"
        :active="isActive(item.href)"
        variant="sidebar"
    />

    <div
        v-else
        ref="row"
        class="ym-side-parent"
        @mouseenter="openFlyout"
        @mouseleave="closeFlyout"
        @focusin="openFlyout"
        @focusout="closeFlyout"
    >
        <span class="ym-side-link ym-side-link--parent" @click="onParentClick">
            <span class="ym-nav-item-content">
                <i v-if="item.icon" :class="['bi', item.icon, 'ym-nav-item-icon']" />
                <span class="ym-nav-item-text">{{ item.label }}</span>
            </span>
            <i class="bi ym-side-chevron" :class="openItems.has(itemKey) ? 'bi-chevron-down' : 'bi-chevron-right'" />
        </span>
        <div
            v-show="collapsed ? flyoutOpen : openItems.has(itemKey)"
            class="ym-side-children"
            :class="{ 'ym-side-flyout': collapsed }"
            :style="collapsed ? flyoutStyle : null"
        >
            <p v-if="collapsed" class="ym-side-flyout-title">{{ item.label }}</p>
            <SidebarMenuItem
                v-for="child in item.children"
                :key="child.href ?? child.labelKey"
                :item="child"
                :open-items="openItems"
                :is-active="isActive"
                :toggle="toggle"
            />
        </div>
    </div>
</template>
