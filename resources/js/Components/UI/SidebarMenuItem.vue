<script setup>
import { computed } from 'vue';
import NavMenuLink from './NavMenuLink.vue';

defineOptions({ name: 'SidebarMenuItem' });

const props = defineProps({
    item: { type: Object, required: true },
    openItems: { type: Set, required: true },
    isActive: { type: Function, required: true },
    toggle: { type: Function, required: true },
});

const itemKey = computed(() => props.item.href ?? props.item.labelKey);
const hasChildren = computed(() => (props.item.children ?? []).length > 0);
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

    <div v-else class="ym-side-parent">
        <span class="ym-side-link ym-side-link--parent" @click="toggle(itemKey)">
            <span class="ym-nav-item-content">
                <i v-if="item.icon" :class="['bi', item.icon, 'ym-nav-item-icon']" />
                <span class="ym-nav-item-text">{{ item.label }}</span>
            </span>
            <i class="bi ym-side-chevron" :class="openItems.has(itemKey) ? 'bi-chevron-down' : 'bi-chevron-right'" />
        </span>
        <div v-show="openItems.has(itemKey)" class="ym-side-children">
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
