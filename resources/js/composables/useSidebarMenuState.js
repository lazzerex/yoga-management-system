import { ref } from 'vue';

const openMenuItems = ref(new Set());
let lastUserId = null;

export function useSidebarMenuState(userId = null) {
    if (userId !== lastUserId) {
        lastUserId = userId;
        openMenuItems.value = new Set();
    }

    const toggleMenuItem = (key) => {
        const next = new Set(openMenuItems.value);
        next.has(key) ? next.delete(key) : next.add(key);
        openMenuItems.value = next;
    };

    return { openMenuItems, toggleMenuItem };
}
