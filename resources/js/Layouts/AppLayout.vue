<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { loadLanguageAsync, trans as t, currentLocale } from 'laravel-vue-i18n';
import NavMenuLink from '@/Components/UI/NavMenuLink.vue';
import SidebarMenuItem from '@/Components/UI/SidebarMenuItem.vue';
import { useSidebarMenuState } from '@/composables/useSidebarMenuState.js';
import { claimedActionErrors } from '@/composables/useActionError.js';

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
});

const page = usePage();

const toggleLocale = async () => {
    const newLocale = currentLocale.value === 'en' ? 'vi' : 'en';
    await loadLanguageAsync(newLocale);
    document.cookie = `locale=${newLocale}; path=/; SameSite=Lax`;
};

const resolveFlashMessage = (message) => {
    if (!message) {
        return '';
    }

    if (typeof message === 'string') {
        return message;
    }

    return t(message.key, message.params ?? {});
};

const flash = computed(() => page.props.flash ?? {});

// Reserved key for business-rule failures, which pages with no form would otherwise drop.
const actionError = computed(() => {
    const errors = page.props.errors;

    return errors === claimedActionErrors.value ? null : (errors?.action ?? null);
});

const dismissed = ref({ success: false, error: false, action: false });
let successTimer = null;

const visible = (kind) => !dismissed.value[kind];

const dismiss = (kind) => {
    dismissed.value[kind] = true;
};

// An alert belongs to a response, not to a message: raising the same error twice
// must announce it twice, so dismissals are cleared per visit rather than when the
// text happens to change.
const announce = () => {
    dismissed.value = { success: false, error: false, action: false };
    clearTimeout(successTimer);

    if (flash.value.success) {
        successTimer = setTimeout(() => dismiss('success'), 6000);
    }
};

announce();

// Keyed to the page Inertia actually applies, not to any request that finishes:
// a hover prefetch completes without delivering props and must not resurrect a
// dismissed alert, while a repeated identical error still arrives as fresh props.
watch(() => page.props, announce);

onBeforeUnmount(() => clearTimeout(successTimer));
const userName = computed(() => page.props.auth?.user?.name ?? 'Guest');
const userRole = computed(() => page.props.auth?.user?.role ?? 'member');
const canAccessAdmin = computed(() => page.props.auth?.user?.canAccessAdmin ?? false);
const canViewCoachDashboard = computed(() => page.props.auth?.user?.canViewCoachDashboard ?? false);

const sidebarMenu = computed(() => {
    currentLocale.value;

    const mapItem = (item) => ({
        ...item,
        label: t(item.labelKey),
        badge: item.badgeKey ? t(item.badgeKey) : null,
        children: (item.children ?? []).map(mapItem),
    });

    return (page.props.menu ?? []).map((group) => ({
        key: group.labelKey,
        label: t(group.labelKey),
        items: group.items.map(mapItem),
    }));
});

const { openMenuItems, toggleMenuItem } = useSidebarMenuState(page.props.auth?.user?.id ?? null);

const isMenuItemActive = (href) => {
    if (!href) return false;
    const path = href.startsWith('http') ? new URL(href).pathname : href;
    return page.url === path
        || page.url.startsWith(`${path}/`)
        || page.url.startsWith(`${path}?`);
};

// Single indicator that measures the currently active sidebar link and
// slides to it. If the active link isn't visible (e.g. inside a collapsed
// group), there's nothing to slide from, so it fades in at the new spot
// instead — `top`/`height` and `opacity` transition independently in CSS.
const sideNavRef = ref(null);
const sideIndicatorStyle = ref({ top: '0px', left: '0px', height: '0px', opacity: 0 });

const updateSideIndicator = () => {
    let target = sideNavRef.value?.querySelector('.ym-side-link--active');

    // A collapsed rail or a closed group hides the active child (offsetParent goes null),
    // so mark the parent row it belongs to rather than collapsing to the top of the nav.
    while (target && target.offsetParent === null) {
        const parentRow = target.closest('.ym-side-parent')?.querySelector(':scope > .ym-side-link--parent');
        target = parentRow && parentRow !== target ? parentRow : null;
    }

    if (!target) {
        sideIndicatorStyle.value = { ...sideIndicatorStyle.value, opacity: 0 };
        return;
    }

    // offsetLeft keeps the bar against the active link, which is indented for children.
    // Children sit a little further left so the bar clears their highlight pill.
    const childInset = target.closest('.ym-side-children') ? 9 : 0;

    sideIndicatorStyle.value = {
        top: `${target.offsetTop}px`,
        left: `${target.offsetLeft - childInset}px`,
        height: `${target.offsetHeight}px`,
        opacity: 1,
    };
};

onMounted(() => nextTick(updateSideIndicator));
watch(() => page.url, () => nextTick(updateSideIndicator));
watch(openMenuItems, () => nextTick(updateSideIndicator));

// Member "My classes" section sub navigation. Rendered inside the persistent
// topbar so the underline indicator can slide as the member moves between the
// bookings / book / schedule pages instead of remounting on every visit.
const memberClassTabs = computed(() => {
    currentLocale.value;

    const path = page.url.split('?')[0];
    const tab = (name, label, icon) => ({
        label,
        icon,
        href: route(name),
        active: path === route(name, undefined, false),
    });

    return [
        tab('member.my-classes', t('member.tabBookings'), 'bi-journal-bookmark'),
        tab('member.classes.book', t('member.tabBook'), 'bi-calendar-plus'),
        tab('member.my-schedule', t('member.tabSchedule'), 'bi-calendar-week'),
    ];
});
const showMemberSubnav = computed(() => memberClassTabs.value.some((tab) => tab.active));

const topbarRef = ref(null);
const subnavRef = ref(null);
const subIndicatorStyle = ref({ left: '0px', width: '0px', opacity: 0 });

const updateSubIndicator = () => {
    const idx = memberClassTabs.value.findIndex((tab) => tab.active);
    const el = subnavRef.value?.querySelectorAll('.ym-subnav-tab')[idx];
    if (!el) {
        subIndicatorStyle.value = { ...subIndicatorStyle.value, opacity: 0 };
        return;
    }
    subIndicatorStyle.value = { left: `${el.offsetLeft}px`, width: `${el.offsetWidth}px`, opacity: 1 };
};

let topbarObserver;
onMounted(() => {
    nextTick(updateSubIndicator);
    if (topbarRef.value && 'ResizeObserver' in window) {
        topbarObserver = new ResizeObserver(() => {
            document.documentElement.style.setProperty('--ym-topbar-h', `${topbarRef.value.offsetHeight}px`);
        });
        topbarObserver.observe(topbarRef.value);
    }
});
onBeforeUnmount(() => topbarObserver?.disconnect());
watch(() => page.url, () => nextTick(updateSubIndicator));
watch(currentLocale, () => nextTick(updateSubIndicator));

const flattenMenuItems = (groups) => {
    const flat = [];
    const walk = (items) => {
        for (const item of items) {
            if (item.href) flat.push({ href: item.href, label: item.label });
            if (item.children?.length) walk(item.children);
        }
    };
    groups.forEach((group) => walk(group.items));
    return flat;
};

// A parent and its first child often share one href (Lesson Plans / All Plans), and
// isMenuItemActive matches on prefix, so several items can match at once. Only the
// longest match is the page you are on — anything shorter is an ancestor.
const activeHref = computed(() => flattenMenuItems(sidebarMenu.value)
    .map((item) => item.href)
    .filter((href) => isMenuItemActive(href))
    .sort((a, b) => b.length - a.length)[0] ?? null);

const isMenuLinkActive = (href) => !!href && href === activeHref.value;

// Ancestors of the active item, so the breadcrumb reads Home > Lesson Plans > Approval Queue.
const activeTrail = computed(() => {
    if (!activeHref.value) return [];

    const walk = (items, trail) => {
        for (const item of items) {
            const next = [...trail, item];
            if (item.href === activeHref.value) return next;

            const found = walk(item.children ?? [], next);
            if (found) return found;
        }
        return null;
    };

    for (const group of sidebarMenu.value) {
        const found = walk(group.items, []);
        if (found) return found;
    }
    return [];
});

const breadcrumbs = computed(() => {
    if (isOnDashboard.value) {
        return [{ label: props.title, current: true }];
    }

    const crumbs = [{ label: t('dashboard.homepage'), href: route('cms.dashboard') }];

    activeTrail.value.forEach((item) => crumbs.push({ label: item.label, href: item.href }));

    const last = crumbs[crumbs.length - 1];
    if (last.label === props.title) {
        delete last.href;
        last.current = true;
    } else {
        crumbs.push({ label: props.title, current: true });
    }

    return crumbs;
});
const roleLabel = computed(() => {
    const role = userRole.value;
    if (!role) return 'guest';
    return t(`admin.roles.${role}`);
});
const sidebarOpen = ref(false);
const toggleSidebar = () => { sidebarOpen.value = !sidebarOpen.value; };
const closeSidebar = () => { sidebarOpen.value = false; };

const readStoredSidebarCollapsed = () => {
    try {
        return localStorage.getItem('ym-sidebar-collapsed') === '1';
    } catch {
        return false;
    }
};

const sidebarCollapsed = ref(readStoredSidebarCollapsed());
const toggleSidebarCollapsed = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    try {
        localStorage.setItem('ym-sidebar-collapsed', sidebarCollapsed.value ? '1' : '0');
    } catch {
        // ignore — collapse still works for this session, just won't persist
    }
};

// Collapsing hides the group labels (display:none, instant) which shifts every
// item below it upward immediately — no need to wait for the width transition,
// vertical stacking doesn't depend on it. Measuring right away avoids a stale
// position followed by a jump.
watch(sidebarCollapsed, () => nextTick(updateSideIndicator));

const notificationsOpen = ref(false);
const profileMenuOpen = ref(false);
const notificationsRef = ref(null);
const profileMenuRef = ref(null);
const dashboardActionsOpen = ref(false);
const dashboardActionsRef = ref(null);
const branchSwitcherOpen = ref(false);
const branchSwitcherRef = ref(null);

const currentBranch = computed(() => page.props.currentBranch);
const allBranches = computed(() => page.props.allBranches ?? []);
const branchSwitching = ref(false);

const toggleBranchSwitcher = () => {
    branchSwitcherOpen.value = !branchSwitcherOpen.value;
    if (branchSwitcherOpen.value) {
        notificationsOpen.value = false;
        profileMenuOpen.value = false;
        dashboardActionsOpen.value = false;
    }
};

const switchBranch = (branchId) => {
    if (branchId === currentBranch.value?.id) {
        branchSwitcherOpen.value = false;
        return;
    }

    branchSwitcherOpen.value = false;
    branchSwitching.value = true;
    document.cookie = `branch_id=${branchId}; path=/; SameSite=Lax`;

    // Sidebar links prefetch on hover, so a page you hovered before switching is already
    // cached with the old branch's data — and a visit to that URL would be served from
    // that cache without ever reaching the server. Every cached page is branch-stale now.
    router.flushAll();

    // Page 3 of the old branch usually doesn't exist in the new one, which reads as
    // an empty page. Non-paging filters (date, month, status) stay — they aren't branch-bound.
    const url = new URL(window.location.href);
    [...url.searchParams.keys()]
        .filter((key) => key.toLowerCase().endsWith('page'))
        .forEach((key) => url.searchParams.delete(key));

    // Cleared on every terminal outcome, not just success — a server error must not
    // leave the switcher spinning with no way back to another branch.
    const done = () => { branchSwitching.value = false; };

    router.visit(`${url.pathname}${url.search}`, {
        preserveState: true,
        preserveScroll: true,
        // Inertia keys the prefetch cache on visit params including headers, and flushAll()
        // leaves in-flight prefetches behind. This is what router.reload() sends, and it is
        // what keeps this visit from ever being answered by a hover prefetch.
        headers: { 'Cache-Control': 'no-cache' },
        onFinish: done,
        onError: done,
        onCancel: done,
    });
};

const roleTopMenuDefaults = computed(() => {
    currentLocale.value;
    if (canAccessAdmin.value) {
        return [
            { label: t('dashboard.homepage'), viewKey: 'homepage', href: route('cms.dashboard') },
            { label: t('dashboard.mySchedule'), viewKey: 'my-schedule', href: route('cms.dashboard', { view: 'my-schedule' }) },
            { label: t('dashboard.members'), viewKey: 'members', href: route('cms.dashboard', { view: 'members' }) },
            { label: t('dashboard.attendance'), viewKey: 'attendance', href: route('cms.dashboard', { view: 'attendance' }) },
            { label: t('dashboard.studioReports'), viewKey: 'studio-reports', href: route('cms.dashboard', { view: 'studio-reports' }) },
            { label: t('dashboard.financials'), viewKey: 'financials', href: route('cms.dashboard', { view: 'financials' }) },
        ];
    }

    if (canViewCoachDashboard.value) {
        return [
            { label: t('dashboard.homepage'), viewKey: 'homepage', href: route('cms.dashboard') },
            { label: t('dashboard.overview'), viewKey: 'overview', href: '' },
            { label: t('dashboard.myPerformance'), viewKey: 'my-performance', href: '' },
            { label: t('dashboard.classStats'), viewKey: 'class-stats', href: '' },
            { label: t('dashboard.studentProgress'), viewKey: 'student-progress', href: '' },
            { label: t('dashboard.earnings'), viewKey: 'earnings', href: '' },
        ];
    }

    return [
        { label: t('dashboard.homepage'), viewKey: 'homepage', href: route('cms.dashboard') },
        { label: t('dashboard.overview'), viewKey: 'overview', href: '' },
        { label: t('dashboard.myProgress'), viewKey: 'my-progress', href: '' },
        { label: t('dashboard.attendance'), viewKey: 'attendance', href: '' },
        { label: t('dashboard.payments'), viewKey: 'payments', href: '' },
        { label: t('dashboard.achievements'), viewKey: 'achievements', href: '' },
    ];
});

const topMenuItems = ref([]);
const coachMemberView = ref('homepage');

const notifications = [
    { titleKey: 'layout.notifications.leads', time: '2m ago' },
    { titleKey: 'layout.notifications.attendance', time: '14m ago' },
    { titleKey: 'layout.notifications.tuition', time: '1h ago' },
];

const quickActions = computed(() => [
    { label: t('common.profile'), hint: t('common.viewAccountSummary'), href: route('cms.profile.show') },
    { label: t('common.preferences'), hint: t('common.adjustWorkspaceSettings') },
    { label: t('common.lastViewed'), hint: t('common.jumpBackToRecentPages') },
    { label: t('common.about'), hint: t('common.seeReleaseNotes') },
]);

const userInitials = computed(() => {
    const name = userName.value?.trim();
    if (!name) {
        return 'GU';
    }

    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
});

const toViewKey = (value) => value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

const normalizeTopTabs = (incomingTabs) => {
    const fallbackTabs = roleTopMenuDefaults.value;
    const normalized = incomingTabs
        .slice(0, 6)
        .map((tab, index) => {
            const fallback = fallbackTabs[index] ?? fallbackTabs[fallbackTabs.length - 1];
            const label = (tab?.label ?? '').toString().trim() || fallback.label;
            const fallbackKey = index === 0 ? 'homepage' : fallback.viewKey;
            const rawViewKey = (tab?.viewKey ?? toViewKey(label)) || fallbackKey;
            const viewKey = index === 0 ? 'homepage' : rawViewKey;
            const href = index === 0
                ? route('cms.dashboard')
                : ('href' in (tab ?? {}) ? tab.href : route('cms.dashboard', { view: viewKey }));

            return { label, viewKey, href };
        });

    while (normalized.length < 6) {
        normalized.push({ ...fallbackTabs[normalized.length] });
    }

    return normalized;
};

const isOnDashboard = computed(() => page.url.startsWith(route('cms.dashboard', undefined, false)));

const dashboardView = computed(() => {
    if (!isOnDashboard.value) {
        return '';
    }

    const query = page.url.split('?')[1] ?? '';
    return new URLSearchParams(query).get('view') ?? 'homepage';
});

const isTopNavActive = (item) => {
    if (!isOnDashboard.value) {
        return false;
    }

    if (canAccessAdmin.value) {
        return dashboardView.value === item.viewKey;
    }

    return coachMemberView.value === item.viewKey;
};

const getTopNavHref = (item) => {
    if (canAccessAdmin.value) {
        return item.href;
    }

    return '';
};

const handleTopTabClick = (item) => {
    if (canAccessAdmin.value) {
        return;
    }

    coachMemberView.value = item.viewKey;

    window.dispatchEvent(new CustomEvent('ym-dashboard-action', {
        detail: { action: 'view-change', viewKey: item.viewKey },
    }));
};

const closeMenus = () => {
    notificationsOpen.value = false;
    profileMenuOpen.value = false;
    dashboardActionsOpen.value = false;
    branchSwitcherOpen.value = false;
};

const toggleNotifications = () => {
    notificationsOpen.value = !notificationsOpen.value;
    if (notificationsOpen.value) {
        profileMenuOpen.value = false;
    }
};

const toggleProfileMenu = () => {
    profileMenuOpen.value = !profileMenuOpen.value;
    if (profileMenuOpen.value) {
        notificationsOpen.value = false;
        dashboardActionsOpen.value = false;
    }
};

const toggleDashboardActions = () => {
    dashboardActionsOpen.value = !dashboardActionsOpen.value;
    if (dashboardActionsOpen.value) {
        notificationsOpen.value = false;
        profileMenuOpen.value = false;
    }
};

const triggerDashboardAction = (action) => {
    dashboardActionsOpen.value = false;

    if (!page.url.startsWith(route('cms.dashboard', undefined, false))) {
        router.get(route('cms.dashboard'), { dashboardAction: action });
        return;
    }

    window.dispatchEvent(new CustomEvent('ym-dashboard-action', {
        detail: {
            action,
            tabs: topMenuItems.value.map((item) => ({
                label: item.label,
                viewKey: item.viewKey,
                href: item.href,
            })),
        },
    }));
};

const handleQuickAction = (action) => {
    closeMenus();

    if (action?.href) {
        router.get(action.href);
    }
};

const handleDashboardTabsUpdated = (event) => {
    const incomingTabs = event?.detail?.tabs;
    if (Array.isArray(incomingTabs)) {
        topMenuItems.value = normalizeTopTabs(incomingTabs);
        coachMemberView.value = 'homepage';
    }
};

const handleGlobalClick = (event) => {
    const target = event.target;

    if (notificationsOpen.value && notificationsRef.value && !notificationsRef.value.contains(target)) {
        notificationsOpen.value = false;
    }

    if (profileMenuOpen.value && profileMenuRef.value && !profileMenuRef.value.contains(target)) {
        profileMenuOpen.value = false;
    }

    if (dashboardActionsOpen.value && dashboardActionsRef.value && !dashboardActionsRef.value.contains(target)) {
        dashboardActionsOpen.value = false;
    }

    if (branchSwitcherOpen.value && branchSwitcherRef.value && !branchSwitcherRef.value.contains(target)) {
        branchSwitcherOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleGlobalClick);
    window.addEventListener('ym-dashboard-tabs-updated', handleDashboardTabsUpdated);
});

watch([canAccessAdmin, canViewCoachDashboard], () => {
    topMenuItems.value = roleTopMenuDefaults.value.map((item) => ({ ...item }));
    coachMemberView.value = 'homepage';
}, { immediate: true });

watch(currentLocale, () => {
    topMenuItems.value = roleTopMenuDefaults.value.map((item) => ({ ...item }));
});

watch(() => page.url, closeSidebar);

onBeforeUnmount(() => {
    document.removeEventListener('click', handleGlobalClick);
    window.removeEventListener('ym-dashboard-tabs-updated', handleDashboardTabsUpdated);
});

const logout = () => {
    closeMenus();
    // Signing out is an Inertia visit, not a page load, so prefetched pages would otherwise
    // stay in memory after the session ends.
    router.flushAll();
    router.post(route('logout'));
};
</script>

<template>
    <Head :title="title" />

    <div class="ym-shell" :class="{ 'ym-shell--collapsed': sidebarCollapsed }">
        <div v-if="sidebarOpen" class="ym-sidebar-backdrop" @click="closeSidebar" />

        <aside class="ym-sidebar" :class="{ 'ym-sidebar--open': sidebarOpen, 'ym-sidebar--collapsed': sidebarCollapsed }">
            <div class="ym-brand">
                <p class="ym-brand-mark">YM</p>
                <div class="ym-brand-text">
                    <p class="ym-brand-title">{{ $t('layout.brand.title') }}</p>
                </div>

                <button
                    type="button"
                    class="ym-sidebar-collapse-toggle"
                    :aria-label="$t('common.toggleSidebar')"
                    @click="toggleSidebarCollapsed"
                >
                    <i :class="sidebarCollapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left'" />
                </button>
            </div>

            <nav ref="sideNavRef" class="ym-side-nav">
                <span class="ym-side-active-indicator" :style="sideIndicatorStyle" />
                <section v-for="group in sidebarMenu" :key="group.key" class="ym-side-group-wrap">
                    <p class="ym-side-group">{{ group.label }}</p>
                    <SidebarMenuItem
                        v-for="item in group.items"
                        :key="item.href ?? item.labelKey"
                        :item="item"
                        :open-items="openMenuItems"
                        :is-active="isMenuLinkActive"
                        :toggle="toggleMenuItem"
                    />
                </section>
            </nav>

            <div class="ym-sidebar-footer">
                <span class="ym-badge">{{ roleLabel }}</span>
            </div>
        </aside>

        <div class="ym-workspace">
            <header ref="topbarRef" class="ym-topbar">
                <div class="ym-topbar-row">
                    <div class="ym-topbar-head">
                        <button
                            type="button"
                            class="ym-icon-btn ym-sidebar-toggle"
                            :aria-label="$t('common.toggleSidebar')"
                            @click.stop="toggleSidebar"
                        >
                            <i class="bi bi-list" />
                        </button>
                        <div>
                            <p class="ym-overline">{{ $t('dashboard.systemName') }}</p>
                            <h1 class="ym-header-title">{{ title }}</h1>
                        </div>
                    </div>

                    <div class="ym-topbar-actions">
                        <div v-if="currentBranch" ref="branchSwitcherRef" class="ym-header-menu-wrap">
                            <button
                                type="button"
                                class="ym-btn-sm ym-branch-switch-btn"
                                :class="{ 'ym-branch-switch-btn--busy': branchSwitching }"
                                aria-haspopup="menu"
                                :aria-expanded="branchSwitcherOpen"
                                @click.stop="toggleBranchSwitcher"
                            >
                                <i :class="branchSwitching ? 'bi bi-arrow-repeat ym-branch-switch-spinner' : 'bi bi-geo-alt'" />
                                {{ currentBranch.name }}
                                <i class="bi bi-chevron-down ym-branch-switch-caret" />
                            </button>

                            <div v-if="branchSwitcherOpen" class="ym-popover ym-popover-menu" role="menu">
                                <button
                                    v-for="branch in allBranches"
                                    :key="branch.id"
                                    type="button"
                                    class="ym-menu-item"
                                    :class="{ 'ym-menu-item--active': branch.id === currentBranch.id }"
                                    @click="switchBranch(branch.id)"
                                >
                                    <span>{{ branch.name }}</span>
                                    <i v-if="branch.id === currentBranch.id" class="bi bi-check2" />
                                </button>
                            </div>
                        </div>

                        <label class="ym-search-wrap" :aria-label="$t('common.search')">
                            <i class="bi bi-search ym-search-icon" />
                            <input
                                type="search"
                                class="ym-search"
                                :placeholder="$t('common.search')"
                                :aria-label="$t('common.search')"
                            />
                        </label>

                        <button
                            type="button"
                            class="ym-icon-btn ym-lang-toggle"
                            :title="$t('common.switchLang')"
                            @click="toggleLocale"
                        >
                            <span class="ym-lang-label">{{ currentLocale === 'en' ? 'EN' : 'VI' }}</span>
                        </button>

                        <div ref="notificationsRef" class="ym-header-menu-wrap">
                            <button
                                type="button"
                                class="ym-icon-btn"
                                :class="{ 'ym-icon-btn--active': notificationsOpen }"
                                :aria-label="$t('common.openNotifications')"
                                aria-haspopup="menu"
                                :aria-expanded="notificationsOpen"
                                @click.stop="toggleNotifications"
                            >
                                <i class="bi bi-bell" />
                                <span class="ym-icon-dot" aria-hidden="true" />
                            </button>

                            <div v-if="notificationsOpen" class="ym-popover ym-popover-notifications" role="menu">
                                <div class="ym-popover-head">
                                    <p class="ym-popover-title">{{ $t('common.notifications') }}</p>
                                    <button type="button" class="ym-popover-link" @click="notificationsOpen = false">
                                        {{ $t('common.markAllRead') }}
                                    </button>
                                </div>
                                <ul class="ym-notification-list">
                                    <li
                                        v-for="item in notifications"
                                        :key="item.titleKey"
                                        class="ym-notification-item"
                                    >
                                        <p class="ym-notification-title">{{ $t(item.titleKey) }}</p>
                                        <p class="ym-notification-time">{{ item.time }}</p>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div ref="profileMenuRef" class="ym-header-menu-wrap">
                            <button
                                type="button"
                                class="ym-icon-btn"
                                :class="{ 'ym-icon-btn--active': profileMenuOpen }"
                                :aria-label="t('common.openQuickMenu')"
                                aria-haspopup="menu"
                                :aria-expanded="profileMenuOpen"
                                @click.stop="toggleProfileMenu"
                            >
                                <i class="bi bi-three-dots-vertical" />
                            </button>

                            <div v-if="profileMenuOpen" class="ym-popover ym-popover-menu" role="menu">
                                <div class="ym-profile-chip">
                                    <span class="ym-profile-avatar">{{ userInitials }}</span>
                                    <div>
                                        <p class="ym-profile-name">{{ userName }}</p>
                                        <p class="ym-profile-role">{{ roleLabel }}</p>
                                    </div>
                                </div>

                                <button
                                    v-for="action in quickActions"
                                    :key="action.label"
                                    type="button"
                                    class="ym-menu-item"
                                    @click="handleQuickAction(action)"
                                >
                                    <span>{{ action.label }}</span>
                                    <small>{{ action.hint }}</small>
                                </button>

                                <button type="button" class="ym-menu-item ym-menu-item--danger" @click="logout">
                                    <span>{{ $t('common.signOut') }}</span>
                                    <small>{{ $t('common.endSession') }}</small>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <nav v-if="breadcrumbs.length > 1" class="ym-breadcrumb" aria-label="breadcrumb">
                    <template v-for="(crumb, index) in breadcrumbs" :key="index">
                        <i v-if="index > 0" class="bi bi-chevron-right ym-breadcrumb-sep" />
                        <Link v-if="crumb.href && !crumb.current" :href="crumb.href" class="ym-breadcrumb-link">
                            {{ crumb.label }}
                        </Link>
                        <span v-else class="ym-breadcrumb-current">{{ crumb.label }}</span>
                    </template>
                </nav>

                <nav v-if="showMemberSubnav" ref="subnavRef" class="ym-subnav-bar" aria-label="section navigation">
                    <Link
                        v-for="tab in memberClassTabs"
                        :key="tab.href"
                        :href="tab.href"
                        class="ym-subnav-tab"
                        :class="{ 'is-active': tab.active }"
                    >
                        <i :class="`bi ${tab.icon}`" />
                        {{ tab.label }}
                    </Link>
                    <span class="ym-subnav-indicator" :style="subIndicatorStyle" />
                </nav>

                <nav v-if="isOnDashboard" class="ym-top-links">
                    <NavMenuLink
                        v-for="item in topMenuItems"
                        :key="item.viewKey"
                        :href="getTopNavHref(item)"
                        :label="item.label"
                        :active="isTopNavActive(item)"
                        variant="top"
                        @tab-click="handleTopTabClick(item)"
                    />

                    <div ref="dashboardActionsRef" class="ym-header-menu-wrap ym-top-links-more">
                        <button
                            type="button"
                            class="ym-icon-btn ym-top-links-more-btn"
                            :class="{ 'ym-icon-btn--active': dashboardActionsOpen }"
                            :aria-label="$t('common.openDashboardOptions')"
                            aria-haspopup="menu"
                            :aria-expanded="dashboardActionsOpen"
                            @click.stop="toggleDashboardActions"
                        >
                            <i class="bi bi-three-dots" />
                        </button>

                        <div v-if="dashboardActionsOpen" class="ym-popover ym-top-links-menu" role="menu">
                            <button type="button" class="ym-menu-item" @click="triggerDashboardAction('edit-dashboard')">
                                <span class="ym-menu-item-label">
                                    <i class="bi bi-pencil-square ym-menu-item-icon" />
                                    {{ $t('dashboard.editDashboard') }}
                                </span>
                            </button>
                            <button type="button" class="ym-menu-item" @click="triggerDashboardAction('add-dashlet')">
                                <span class="ym-menu-item-label">
                                    <i class="bi bi-plus-lg ym-menu-item-icon" />
                                    {{ $t('dashboard.addDashlet') }}
                                </span>
                            </button>
                            <button type="button" class="ym-menu-item" @click="triggerDashboardAction('reset-dashboard')">
                                <span class="ym-menu-item-label">
                                    <i class="bi bi-arrow-counterclockwise ym-menu-item-icon" />
                                    {{ $t('dashboard.resetLayout') }}
                                </span>
                            </button>
                        </div>
                    </div>
                </nav>
            </header>

            <main class="ym-main">
                <div class="ym-alert-stack">
                <Transition name="ym-alert-fade">
                    <div v-if="flash.success && visible('success')" class="ym-alert-success ym-main-alert">
                        <i class="bi bi-check-circle ym-alert-icon" />
                        <span class="ym-alert-text">{{ resolveFlashMessage(flash.success) }}</span>
                        <button type="button" class="ym-alert-close" :aria-label="$t('common.close')" @click="dismiss('success')">
                            <i class="bi bi-x-lg" />
                        </button>
                    </div>
                </Transition>
                <Transition name="ym-alert-fade">
                    <div v-if="flash.error && visible('error')" class="ym-alert-error ym-main-alert">
                        <i class="bi bi-exclamation-triangle ym-alert-icon" />
                        <span class="ym-alert-text">{{ resolveFlashMessage(flash.error) }}</span>
                        <button type="button" class="ym-alert-close" :aria-label="$t('common.close')" @click="dismiss('error')">
                            <i class="bi bi-x-lg" />
                        </button>
                    </div>
                </Transition>
                <Transition name="ym-alert-fade">
                    <div v-if="actionError && visible('action')" class="ym-alert-error ym-main-alert">
                        <i class="bi bi-exclamation-triangle ym-alert-icon" />
                        <span class="ym-alert-text">{{ actionError }}</span>
                        <button type="button" class="ym-alert-close" :aria-label="$t('common.close')" @click="dismiss('action')">
                            <i class="bi bi-x-lg" />
                        </button>
                    </div>
                </Transition>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
