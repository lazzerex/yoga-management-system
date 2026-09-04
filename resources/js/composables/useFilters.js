import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Server-side filtering for an index page. Text fields are debounced, selects apply at
 * once, and an empty value drops out of the query string instead of being sent blank.
 *
 * @param {string} url        endpoint the page filters against
 * @param {object} initial    current filter values from the controller
 * @param {string[]} debounced keys typed into rather than picked from
 */
export function useFilters(url, initial = {}, debounced = ['search']) {
    const filters = ref(Object.fromEntries(
        Object.keys(initial).map((key) => [key, initial[key] ?? '']),
    ));

    const query = () => Object.fromEntries(
        Object.entries(filters.value).map(([key, value]) => [key, value === '' || value === null ? undefined : value]),
    );

    const apply = () => router.get(url, query(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });

    let timer = null;
    // Clearing every field would otherwise fire one request per field.
    let suspended = false;

    Object.keys(filters.value).forEach((key) => {
        watch(() => filters.value[key], () => {
            if (suspended) {
                return;
            }

            if (! debounced.includes(key)) {
                apply();
                return;
            }

            clearTimeout(timer);
            timer = setTimeout(apply, 350);
        });
    });

    const active = computed(() => Object.values(filters.value).some((value) => value !== '' && value !== null));

    const reset = () => {
        suspended = true;
        clearTimeout(timer);
        Object.keys(filters.value).forEach((key) => {
            filters.value[key] = '';
        });
        suspended = false;
        apply();
    };

    return { filters, active, apply, reset };
}
