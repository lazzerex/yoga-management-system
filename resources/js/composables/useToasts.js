import { ref } from 'vue';

/**
 * Client-side toasts, for feedback that never leaves the browser (layout changes,
 * dashlets added). Server feedback keeps using the flash bag.
 *
 * A toast holds its translation key, not its text, so switching language rewrites
 * the message that is already on screen.
 */
export const toasts = ref([]);

let nextId = 0;

export const dismissToast = (id) => {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
};

export const pushToast = (key, { params = {}, kind = 'success', timeout = 6000 } = {}) => {
    const id = ++nextId;

    toasts.value = [...toasts.value, { id, key, params, kind }];

    if (timeout) {
        setTimeout(() => dismissToast(id), timeout);
    }

    return id;
};
