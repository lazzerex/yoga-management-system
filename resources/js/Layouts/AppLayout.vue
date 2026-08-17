<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { loadLanguageAsync, trans as t, currentLocale } from 'laravel-vue-i18n';
import NavMenuLink from '@/Components/UI/NavMenuLink.vue';
import SidebarMenuItem from '@/Components/UI/SidebarMenuItem.vue';
import { useSidebarMenuState } from '@/Composables/useSidebarMenuState.js';

defineProps({
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

const { openMenuItems, toggleMenuItem } = useSidebarMenuState();

const isMenuItemActive = (href) => {
    if (!href) return false;
    const path = href.startsWith('http') ? new URL(href).pathname : href;
    return page.url === path
        || page.url.startsWith(`${path}/`)
        || page.url.startsWith(`${path}?`);
};
const roleLabel = computed(() => {
    const role = userRole.value;
    if (!role) return 'guest';
    return t(`admin.roles.${role}`);
});
const sidebarOpen = ref(false);
const toggleSidebar = () => { sidebarOpen.value = !sidebarOpen.value; };
const closeSidebar = () => { sidebarOpen.value = false; };

const notificationsOpen = ref(false);
const profileMenuOpen = ref(false);
const notificationsRef = ref(null);
const profileMenuRef = ref(null);
const dashboardActionsOpen = ref(false);
const dashboardActionsRef = ref(null);

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
    router.post(route('logout'));
};
</script>

<template>
    <div class="ym-shell">
        <div v-if="sidebarOpen" class="ym-sidebar-backdrop" @click="closeSidebar" />

        <aside class="ym-sidebar" :class="{ 'ym-sidebar--open': sidebarOpen }">
            <div class="ym-brand">
                <p class="ym-brand-mark">YM</p>
                <div>
                    <p class="ym-brand-title">{{ $t('layout.brand.title') }}</p>
                    <p class="ym-brand-subtitle">{{ $t('layout.brand.subtitle') }}</p>
                </div>
            </div>

            <nav class="ym-side-nav">
                <section v-for="group in sidebarMenu" :key="group.key" class="ym-side-group-wrap">
                    <p class="ym-side-group">{{ group.label }}</p>
                    <SidebarMenuItem
                        v-for="item in group.items"
                        :key="item.href ?? item.labelKey"
                        :item="item"
                        :open-items="openMenuItems"
                        :is-active="isMenuItemActive"
                        :toggle="toggleMenuItem"
                    />
                </section>
            </nav>

            <div class="ym-sidebar-footer">
                <span class="ym-badge">{{ roleLabel }}</span>
            </div>
        </aside>

        <div class="ym-workspace">
            <header class="ym-topbar">
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
                <div v-if="flash.success" class="ym-alert-success ym-main-alert">{{ resolveFlashMessage(flash.success) }}</div>
                <div v-if="flash.error" class="ym-alert-error ym-main-alert">{{ resolveFlashMessage(flash.error) }}</div>
                <slot />
            </main>
        </div>
    </div>
</template>
