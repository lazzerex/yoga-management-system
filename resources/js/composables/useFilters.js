import { computed, nextTick, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const NON_FILTER_KEYS = ['sort', 'dir', 'date', 'months'];

const UNCOUNTED_KEYS = [...NON_FILTER_KEYS, 'search'];

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

    const isSet = (value) => value !== '' && value !== null && value !== undefined;

    const active = computed(() => Object.entries(filters.value)
        .some(([key, value]) => ! NON_FILTER_KEYS.includes(key) && isSet(value)));

    /** How many narrowing filters are on, for the badge on the Filters button. */
    const filterCount = computed(() => Object.entries(filters.value)
        .filter(([key, value]) => ! UNCOUNTED_KEYS.includes(key) && isSet(value))
        .length);

    /**
     * Change several fields as one request. Watchers flush after the current tick, so the
     * guard has to outlive it — releasing it straight away would let each field fire again.
     */
    const batch = (mutate) => {
        suspended = true;
        clearTimeout(timer);
        mutate();
        apply();
        nextTick(() => {
            suspended = false;
        });
    };

    const reset = () => batch(() => {
        Object.keys(filters.value).forEach((key) => {
            filters.value[key] = '';
        });
    });

    /** Column headers cycle ascending, descending, then back to the default order. */
    const toggleSort = (field) => batch(() => {
        if (filters.value.sort !== field) {
            filters.value.sort = field;
            filters.value.dir = 'asc';
        } else if (filters.value.dir === 'asc') {
            filters.value.dir = 'desc';
        } else {
            filters.value.sort = '';
            filters.value.dir = '';
        }
    });

    return { filters, active, filterCount, apply, reset, toggleSort };
}
