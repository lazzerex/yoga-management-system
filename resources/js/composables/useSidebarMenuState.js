import { ref } from 'vue';

const openMenuItems = ref(new Set());

export function useSidebarMenuState() {
    const toggleMenuItem = (key) => {
        const next = new Set(openMenuItems.value);
        next.has(key) ? next.delete(key) : next.add(key);
        openMenuItems.value = next;
    };

    return { openMenuItems, toggleMenuItem };
}
