import { computed, nextTick, provide, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const NON_FILTER_KEYS = ['sort', 'dir', 'date', 'months'];

const UNCOUNTED_KEYS = [...NON_FILTER_KEYS, 'search'];

export const FilterDraftKey = Symbol('filter-draft');

/**
 * Server-side filtering for an index page. Search, sorting and anything else outside the
 * filter panel applies on change, text debounced; panel fields are staged until Apply.
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

    // The panel edits `filters` directly; the snapshot is what Cancel puts back.
    let draft = null;

    const beginDraft = () => {
        draft = { ...filters.value };
        suspended = true;
        clearTimeout(timer);
    };

    const commitDraft = () => {
        draft = null;
        suspended = false;
        apply();
    };

    const discardDraft = () => {
        if (draft !== null) {
            Object.assign(filters.value, draft);
            draft = null;
        }

        // Restoring re-triggers the watchers, so the guard has to outlive this tick.
        nextTick(() => {
            suspended = false;
        });
    };

    provide(FilterDraftKey, { beginDraft, commitDraft, discardDraft });

    const clearAll = () => {
        Object.keys(filters.value).forEach((key) => {
            filters.value[key] = '';
        });
    };

    // Inside an open panel the clear is staged like any other panel edit.
    const reset = () => (draft === null ? batch(clearAll) : clearAll());

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
