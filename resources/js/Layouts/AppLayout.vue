<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faArrowRotateLeft,
    faBell,
    faBuilding,
    faCalendarCheck,
    faEllipsis,
    faEllipsisVertical,
    faClockRotateLeft,
    faClipboardCheck,
    faFolderOpen,
    faHouse,
    faMagnifyingGlass,
    faMoneyBillWave,
    faPenToSquare,
    faPlus,
    faUser,
    faUserShield,
    faUsers,
} from '@fortawesome/free-solid-svg-icons';
import NavMenuLink from '@/Components/UI/NavMenuLink.vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
});

const page = usePage();

const flash = computed(() => page.props.flash ?? {});
const userName = computed(() => page.props.auth?.user?.name ?? 'Guest');
const userRole = computed(() => page.props.auth?.user?.role ?? 'member');
const isAdmin = computed(() => userRole.value === 'admin');
const isCoach = computed(() => userRole.value === 'coach');
const isMember = computed(() => userRole.value === 'member');
const canAccessFees = computed(() => ['admin', 'member'].includes(userRole.value));
const canAccessTeacherOperations = computed(() => ['admin', 'coach'].includes(userRole.value));
const canAccessFileLibrary = computed(() => ['admin', 'coach'].includes(userRole.value));
const roleLabel = computed(() => {
    const role = userRole.value;
    if (!role) return 'guest';
    return role.charAt(0).toUpperCase() + role.slice(1);
});
const notificationsOpen = ref(false);
const profileMenuOpen = ref(false);
const notificationsRef = ref(null);
const profileMenuRef = ref(null);
const dashboardActionsOpen = ref(false);
const dashboardActionsRef = ref(null);

const roleTopMenuDefaults = computed(() => {
    if (isAdmin.value) {
        return [
            { label: 'Homepage', viewKey: 'homepage', href: route('cms.dashboard') },
            { label: 'My Schedule', viewKey: 'my-schedule', href: route('cms.dashboard', { view: 'my-schedule' }) },
            { label: 'Members', viewKey: 'members', href: route('cms.dashboard', { view: 'members' }) },
            { label: 'Attendance', viewKey: 'attendance', href: route('cms.dashboard', { view: 'attendance' }) },
            { label: 'Studio Reports', viewKey: 'studio-reports', href: route('cms.dashboard', { view: 'studio-reports' }) },
            { label: 'Financials', viewKey: 'financials', href: route('cms.dashboard', { view: 'financials' }) },
        ];
    }

    if (isCoach.value) {
        return [
            { label: 'Homepage', viewKey: 'homepage', href: route('cms.dashboard') },
            { label: 'Overview', viewKey: 'overview', href: '' },
            { label: 'My Performance', viewKey: 'my-performance', href: '' },
            { label: 'Class Stats', viewKey: 'class-stats', href: '' },
            { label: 'Student Progress', viewKey: 'student-progress', href: '' },
            { label: 'Earnings', viewKey: 'earnings', href: '' },
        ];
    }

    return [
        { label: 'Homepage', viewKey: 'homepage', href: route('cms.dashboard') },
        { label: 'Overview', viewKey: 'overview', href: '' },
        { label: 'My Progress', viewKey: 'my-progress', href: '' },
        { label: 'Attendance', viewKey: 'attendance', href: '' },
        { label: 'Payments', viewKey: 'payments', href: '' },
        { label: 'Achievements', viewKey: 'achievements', href: '' },
    ];
});

const topMenuItems = ref([]);
const coachMemberView = ref('homepage');

const notifications = [
    { title: '3 new trial requests from website leads', time: '2m ago' },
    { title: 'Teacher attendance for today was submitted', time: '14m ago' },
    { title: 'April tuition reconciliation is almost due', time: '1h ago' },
];

const quickActions = [
    { label: 'Profile', hint: 'View account summary', href: route('cms.profile.show') },
    { label: 'Preferences', hint: 'Adjust workspace settings' },
    { label: 'Last Viewed', hint: 'Jump back to recent pages' },
    { label: 'About', hint: 'See release notes' },
];

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

const isActive = (paths) => {
    const pathList = Array.isArray(paths) ? paths : [paths];
    return pathList.some((path) => (
        page.url === path || page.url.startsWith(`${path}/`) || page.url.startsWith(`${path}?`)
    ));
};

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

    if (isAdmin.value) {
        return dashboardView.value === item.viewKey;
    }

    return coachMemberView.value === item.viewKey;
};

const getTopNavHref = (item) => {
    if (isAdmin.value) {
        return item.href;
    }

    return '';
};

const handleTopTabClick = (item) => {
    if (isAdmin.value) {
        return;
    }

    coachMemberView.value = item.viewKey;

    window.dispatchEvent(new CustomEvent('ym-dashboard-action', {
        detail: { action: 'view-change', viewKey: item.viewKey },
    }));
};

const sidebarGroups = computed(() => {
    const groups = [
        {
            label: 'Main',
            items: [
                {
                    label: 'Home',
                    href: route('cms.dashboard'),
                    activePaths: [route('cms.dashboard', undefined, false)],
                    icon: faHouse,
                    iconColor: '#4f8bc8',
                },
                {
                    label: 'My Profile',
                    href: route('cms.profile.show'),
                    activePaths: [route('cms.profile.show', undefined, false)],
                    icon: faUser,
                    iconColor: '#5f77cf',
                },
            ],
        },
        {
            label: 'Operations',
            items: [
                {
                    label: 'Centers',
                    href: route('operations.yoga-center'),
                    activePaths: [route('operations.yoga-center', undefined, false)],
                    icon: faBuilding,
                    iconColor: '#d99a34',
                },
                {
                    label: 'Classes',
                    href: route('operations.academy'),
                    activePaths: [route('operations.academy', undefined, false)],
                    icon: faUsers,
                    iconColor: '#3fa07e',
                },
            ],
        },
    ];

    if (canAccessTeacherOperations.value) {
        groups[1].items.push(
            {
                label: 'Attendance',
                href: route('operations.teacher-attendance'),
                activePaths: [route('operations.teacher-attendance', undefined, false)],
                icon: faClipboardCheck,
                iconColor: '#4f81cf',
            },
            {
                label: 'Plans',
                href: route('operations.lesson-planning'),
                activePaths: [route('operations.lesson-planning', undefined, false)],
                badge: 'Approval',
                icon: faCalendarCheck,
                iconColor: '#6a78c8',
            },
        );
    }

    if (canAccessFees.value) {
        groups[1].items.push({
            label: 'Tuition',
            href: route('operations.tuition-fees'),
            activePaths: [route('operations.tuition-fees', undefined, false)],
            icon: faMoneyBillWave,
            iconColor: '#32a06f',
        });
    }

    if (canAccessFileLibrary.value) {
        groups[1].items.push({
            label: 'Files',
            href: route('operations.file-library'),
            activePaths: [route('operations.file-library', undefined, false)],
            icon: faFolderOpen,
            iconColor: '#c97846',
        });
    }

    if (isMember.value) {
        groups.push({
            label: 'Member',
            items: [
                {
                    label: 'My Membership',
                    href: route('member.my-membership'),
                    activePaths: [route('member.my-membership', undefined, false)],
                    icon: faMoneyBillWave,
                    iconColor: '#3f8f6f',
                },
                {
                    label: 'My Classes',
                    href: route('member.my-classes'),
                    activePaths: [route('member.my-classes', undefined, false)],
                    icon: faUsers,
                    iconColor: '#3f7ec4',
                },
                {
                    label: 'My Schedule',
                    href: route('member.my-schedule'),
                    activePaths: [route('member.my-schedule', undefined, false)],
                    icon: faCalendarCheck,
                    iconColor: '#6a78c8',
                },
            ],
        });
    }

    if (isCoach.value) {
        groups.push({
            label: 'Coach',
            items: [
                {
                    label: 'My Classes',
                    href: route('coach.my-classes'),
                    activePaths: [route('coach.my-classes', undefined, false)],
                    icon: faUsers,
                    iconColor: '#3f7ec4',
                },
                {
                    label: 'My Students',
                    href: route('coach.my-students'),
                    activePaths: [route('coach.my-students', undefined, false)],
                    icon: faClipboardCheck,
                    iconColor: '#4f81cf',
                },
                {
                    label: 'Teaching Schedule',
                    href: route('coach.my-teaching-schedule'),
                    activePaths: [route('coach.my-teaching-schedule', undefined, false)],
                    icon: faCalendarCheck,
                    iconColor: '#6a78c8',
                },
            ],
        });
    }

    if (isAdmin.value) {
        groups.push({
            label: 'Admin',
            items: [
                {
                    label: 'Users',
                    href: route('admin.users.index'),
                    activePaths: [route('admin.users.index', undefined, false)],
                    icon: faUserShield,
                    iconColor: '#5f77cf',
                },
                {
                    label: 'Logs',
                    href: route('admin.login-logs.index'),
                    activePaths: [route('admin.login-logs.index', undefined, false)],
                    icon: faClockRotateLeft,
                    iconColor: '#b26464',
                },
            ],
        });
    }

    return groups;
});

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

watch(userRole, () => {
    topMenuItems.value = roleTopMenuDefaults.value.map((item) => ({ ...item }));
    coachMemberView.value = 'homepage';
}, { immediate: true });

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
        <aside class="ym-sidebar">
            <div class="ym-brand">
                <p class="ym-brand-mark">YM</p>
                <div>
                    <p class="ym-brand-title">Yoga CRM</p>
                    <p class="ym-brand-subtitle">Management workspace</p>
                </div>
            </div>

            <nav class="ym-side-nav">
                <section v-for="group in sidebarGroups" :key="group.label" class="ym-side-group-wrap">
                    <p class="ym-side-group">{{ group.label }}</p>
                    <NavMenuLink
                        v-for="item in group.items"
                        :key="item.href"
                        :href="item.href"
                        :label="item.label"
                        :icon="item.icon"
                        :icon-color="item.iconColor"
                        :badge="item.badge"
                        :active="isActive(item.activePaths)"
                        variant="sidebar"
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
                    <div>
                        <p class="ym-overline">Yoga Management System</p>
                        <h1 class="ym-header-title">{{ title }}</h1>
                    </div>

                    <div class="ym-topbar-actions">
                        <label class="ym-search-wrap" aria-label="Search">
                            <FontAwesomeIcon :icon="faMagnifyingGlass" class="ym-search-icon" />
                            <input
                                type="search"
                                class="ym-search"
                                placeholder="Search"
                                aria-label="Search"
                            />
                        </label>

                        <div ref="notificationsRef" class="ym-header-menu-wrap">
                            <button
                                type="button"
                                class="ym-icon-btn"
                                :class="{ 'ym-icon-btn--active': notificationsOpen }"
                                aria-label="Open notifications"
                                aria-haspopup="menu"
                                :aria-expanded="notificationsOpen"
                                @click.stop="toggleNotifications"
                            >
                                <FontAwesomeIcon :icon="faBell" />
                                <span class="ym-icon-dot" aria-hidden="true" />
                            </button>

                            <div v-if="notificationsOpen" class="ym-popover ym-popover-notifications" role="menu">
                                <div class="ym-popover-head">
                                    <p class="ym-popover-title">Notifications</p>
                                    <button type="button" class="ym-popover-link" @click="notificationsOpen = false">
                                        Mark all read
                                    </button>
                                </div>
                                <ul class="ym-notification-list">
                                    <li
                                        v-for="item in notifications"
                                        :key="item.title"
                                        class="ym-notification-item"
                                    >
                                        <p class="ym-notification-title">{{ item.title }}</p>
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
                                aria-label="Open quick menu"
                                aria-haspopup="menu"
                                :aria-expanded="profileMenuOpen"
                                @click.stop="toggleProfileMenu"
                            >
                                <FontAwesomeIcon :icon="faEllipsisVertical" />
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
                                    <span>Sign Out</span>
                                    <small>End current session</small>
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
                            aria-label="Open dashboard options"
                            aria-haspopup="menu"
                            :aria-expanded="dashboardActionsOpen"
                            @click.stop="toggleDashboardActions"
                        >
                            <FontAwesomeIcon :icon="faEllipsis" />
                        </button>

                        <div v-if="dashboardActionsOpen" class="ym-popover ym-top-links-menu" role="menu">
                            <button type="button" class="ym-menu-item" @click="triggerDashboardAction('edit-dashboard')">
                                <span class="ym-menu-item-label">
                                    <FontAwesomeIcon :icon="faPenToSquare" class="ym-menu-item-icon" />
                                    Edit Dashboard
                                </span>
                            </button>
                            <button type="button" class="ym-menu-item" @click="triggerDashboardAction('add-dashlet')">
                                <span class="ym-menu-item-label">
                                    <FontAwesomeIcon :icon="faPlus" class="ym-menu-item-icon" />
                                    Add Dashlet
                                </span>
                            </button>
                            <button type="button" class="ym-menu-item" @click="triggerDashboardAction('reset-dashboard')">
                                <span class="ym-menu-item-label">
                                    <FontAwesomeIcon :icon="faArrowRotateLeft" class="ym-menu-item-icon" />
                                    Reset Layout
                                </span>
                            </button>
                        </div>
                    </div>
                </nav>
            </header>

            <main class="ym-main">
                <div v-if="flash.success" class="ym-alert-success ym-main-alert">{{ flash.success }}</div>
                <div v-if="flash.error" class="ym-alert-error ym-main-alert">{{ flash.error }}</div>
                <slot />
            </main>
        </div>
    </div>
</template>