<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import { trans as t, currentLocale } from 'laravel-vue-i18n';
import Draggable from 'vuedraggable';
import Modal from '@/Components/UI/Modal.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    auth: Object,
});

const getCurrentUrl = () => 
`${window.location.pathname}${window.location.search}`;

const maxTopTabs = 6;

const userRole = computed(() => props.auth?.user?.role ?? 'member');
const isAdmin = computed(() => userRole.value === 'admin');

const renderKey = ref(0);
watch(currentLocale, () => {
    renderKey.value++;
});

const roleDashboard = computed(() => {
    void currentLocale.value;
    return {
    admin: {
        pageTitle: t('dashboard.homepage'),
        tabs: [t('dashboard.homepage'), t('dashboard.mySchedule'), t('dashboard.members'), t('dashboard.attendance'), t('dashboard.studioReports'), t('dashboard.financials')],
    },
    coach: {
        pageTitle: t('dashboard.coachDashboard'),
        headline: t('dashboard.todaysTeachingSnapshot'),
        subtitle: t('dashboard.coachSubtitle'),
        metrics: [
            { label: t('dashboard.sessionsToday'), value: '4', note: t('dashboard.sessionsTodayNote') },
            { label: t('dashboard.studentsToday'), value: '72', note: t('dashboard.studentsTodayNote') },
            { label: t('dashboard.averageAttendance'), value: '89%', note: t('dashboard.last14Days') },
        ],
        primaryPanel: {
            title: t('dashboard.todaysSchedule'),
            icon: 'bi-clipboard-data',
            rows: [
                { title: '07:00 Power Core', meta: t('dashboard.riversideStudents', { count: 24 }), badge: t('dashboard.completed'), tone: 'started' },
                { title: '12:30 Prenatal Flow', meta: t('dashboard.westsideStudents', { count: 14 }), badge: t('dashboard.completed'), tone: 'started' },
                { title: '18:30 Evening Yin', meta: t('dashboard.downtownStudents', { count: 21 }), badge: t('dashboard.upNext'), tone: 'pending' },
                { title: '20:00 Breathwork Lab', meta: t('dashboard.onlineStudents', { count: 32 }), badge: t('dashboard.later'), tone: 'default' },
            ],
        },
        secondaryPanel: {
            title: t('dashboard.studentSnapshot'),
            icon: 'bi-people',
            rows: [
                { title: t('dashboard.highConsistencyStudents'), meta: t('dashboard.attendanceAboveThreshold', { threshold: '90%' }), badge: '23', tone: 'started' },
                { title: t('dashboard.followUpNeeded'), meta: t('dashboard.attendanceBelowThreshold', { threshold: '60%' }), badge: '6', tone: 'pending' },
                { title: t('dashboard.newThisWeek'), meta: t('dashboard.firstTimeStudents'), badge: '11', tone: 'default' },
                { title: t('dashboard.progressAssessmentsDue'), meta: t('dashboard.byEndOfWeek'), badge: '8', tone: 'default' },
            ],
        },
        weeklyPanel: {
            title: t('dashboard.weeklyTeachingTimeline'),
            emptyMessage: t('dashboard.noClassesAssigned'),
            days: [
                {
                    day: t('dashboard.monday'),
                    dayShort: t('dashboard.mondayShort'),
                    date: 'Apr 27',
                    entries: [
                        { time: '06:45', title: 'Sunrise Mobility', meta: t('dashboard.westsideStudentsWithCoach', { count: 18 }) },
                        { time: '18:30', title: 'Evening Yin', meta: t('dashboard.downtownStudentsWithCoach', { count: 21 }) },
                    ],
                },
                {
                    day: t('dashboard.tuesday'),
                    dayShort: t('dashboard.tuesdayShort'),
                    date: 'Apr 28',
                    entries: [
                        { time: '07:00', title: 'Power Core', meta: t('dashboard.riversideStudentsWithCoach', { count: 24 }) },
                        { time: '20:00', title: 'Breathwork Lab', meta: t('dashboard.onlineStudentsWithCoach', { count: 32 }) },
                    ],
                },
                {
                    day: t('dashboard.wednesday'),
                    dayShort: t('dashboard.wednesdayShort'),
                    date: 'Apr 29',
                    entries: [
                        { time: '18:30', title: 'Evening Yin', meta: t('dashboard.downtownStudentsWithCoach', { count: 20 }) },
                    ],
                },
                {
                    day: t('dashboard.thursday'),
                    dayShort: t('dashboard.thursdayShort'),
                    date: 'Apr 30',
                    entries: [
                        { time: '07:00', title: 'Power Core', meta: t('dashboard.riversideCoachDaniel') },
                        { time: '20:00', title: 'Breathwork Lab', meta: t('dashboard.onlineCoachNoah') },
                    ],
                },
                {
                    day: t('dashboard.friday'),
                    dayShort: t('dashboard.fridayShort'),
                    date: 'May 01',
                    entries: [
                        { time: '17:45', title: 'Mobility Reset', meta: t('dashboard.downtownCoachLina') },
                    ],
                },
                {
                    day: t('dashboard.saturday'),
                    dayShort: t('dashboard.saturdayShort'),
                    date: 'May 02',
                    entries: [
                        { time: '09:00', title: 'Weekend Flow', meta: t('dashboard.uptownCoachMia') },
                    ],
                },
                { day: t('dashboard.sunday'), dayShort: t('dashboard.sundayShort'), date: 'May 03', entries: [] },
            ],
        },
        tabs: [t('dashboard.homepage'), t('dashboard.overview'), t('dashboard.myProgress'), t('dashboard.attendance'), t('dashboard.payments'), t('dashboard.achievements')],
    },
    member: {
        pageTitle: t('dashboard.memberDashboard'),
        headline: t('dashboard.membershipAndSessionOverview'),
        subtitle: t('dashboard.memberSubtitle'),
        metrics: [
            { label: t('dashboard.membershipStatus'), value: t('dashboard.active'), note: t('member.planName') },
            { label: t('dashboard.sessionsThisWeek'), value: '3 / 5', note: t('dashboard.sessionsRemainingNote') },
            { label: t('dashboard.nextSession'), value: t('dashboard.today1830'), note: t('dashboard.eveningYinDowntown') },
        ],
        primaryPanel: {
            title: t('dashboard.membershipStatus'),
            icon: 'bi-person-vcard',
            rows: [
                { title: t('dashboard.planType'), meta: t('member.planName'), badge: t('dashboard.premium'), tone: 'started' },
                { title: t('dashboard.renewalDate'), meta: 'Jan 5, 2027', badge: t('dashboard.auto'), tone: 'default' },
                { title: t('dashboard.guestPasses'), meta: t('dashboard.availableThisCycle'), badge: t('dashboard.fourLeft'), tone: 'default' },
                { title: t('dashboard.supportTickets'), meta: t('dashboard.membershipRequests'), badge: t('dashboard.zeroOpen'), tone: 'started' },
            ],
        },
        secondaryPanel: {
            title: t('dashboard.upcomingSessions'),
            icon: 'bi-calendar3',
            rows: [
                { title: 'Evening Yin', meta: t('dashboard.today1830Downtown'), badge: t('dashboard.booked'), tone: 'started' },
                { title: 'Power Core', meta: t('dashboard.tue0700Riverside'), badge: t('dashboard.booked'), tone: 'started' },
                { title: 'Breathwork Lab', meta: t('dashboard.thu2000Online'), badge: t('dashboard.waitlist'), tone: 'pending' },
                { title: 'Weekend Flow', meta: t('dashboard.sat0900Uptown'), badge: t('dashboard.booked'), tone: 'started' },
            ],
        },
        weeklyPanel: {
            title: t('dashboard.personalWeeklyCalendar'),
            emptyMessage: t('dashboard.restAndRecoveryDay'),
            days: [
                {
                    day: t('dashboard.monday'),
                    dayShort: t('dashboard.mondayShort'),
                    date: 'Apr 27',
                    entries: [
                        { time: '06:45', title: 'Sunrise Mobility', meta: t('dashboard.westsideCoachLina') },
                        { time: '18:30', title: 'Evening Yin', meta: t('dashboard.downtownCoachAri') },
                    ],
                },
                {
                    day: t('dashboard.tuesday'),
                    dayShort: t('dashboard.tuesdayShort'),
                    date: 'Apr 28',
                    entries: [
                        { time: '07:00', title: 'Power Core', meta: t('dashboard.riversideCoachDaniel') },
                    ],
                },
                {
                    day: t('dashboard.wednesday'),
                    dayShort: t('dashboard.wednesdayShort'),
                    date: 'Apr 29',
                    entries: [
                        { time: '18:30', title: 'Evening Yin', meta: t('dashboard.downtownCoachAri') },
                    ],
                },
                {
                    day: t('dashboard.thursday'),
                    dayShort: t('dashboard.thursdayShort'),
                    date: 'Apr 30',
                    entries: [
                        { time: '07:00', title: 'Power Core', meta: t('dashboard.riversideCoachDaniel') },
                        { time: '20:00', title: 'Breathwork Lab', meta: t('dashboard.onlineCoachNoah') },
                    ],
                },
                {
                    day: t('dashboard.friday'),
                    dayShort: t('dashboard.fridayShort'),
                    date: 'May 01',
                    entries: [
                        { time: '17:45', title: 'Mobility Reset', meta: t('dashboard.downtownCoachLina') },
                    ],
                },
                {
                    day: t('dashboard.saturday'),
                    dayShort: t('dashboard.saturdayShort'),
                    date: 'May 02',
                    entries: [
                        { time: '09:00', title: 'Weekend Flow', meta: t('dashboard.uptownCoachMia') },
                    ],
                },
                { day: t('dashboard.sunday'), dayShort: t('dashboard.sundayShort'), date: 'May 03', entries: [] },
            ],
        },
        tabs: [t('dashboard.homepage'), t('dashboard.overview'), t('dashboard.myProgress'), t('dashboard.attendance'), t('dashboard.payments'), t('dashboard.achievements')],
    },
    };
});

const dashboardConfig = computed(() => roleDashboard.value[userRole.value] ?? roleDashboard.value.member);
const pageTitle = computed(() => roleDashboard.value[userRole.value]?.pageTitle ?? 'Dashboard');
const dashboardNotice = ref('');
const activeView = ref('homepage');
const isCoach = computed(() => userRole.value === 'coach');

const viewConfigs = computed(() => {
    void currentLocale.value;
    return {
    coach: {
        overview: {
            title: t('dashboard.teachingOverview'),
            icon: 'bi-bar-chart',
            note: t('dashboard.teachingOverviewNote'),
            rows: [
                { title: t('dashboard.totalSessionsThisMonth'), meta: t('dashboard.acrossAllBranches'), badge: '52', tone: 'default' },
                { title: t('dashboard.newStudents'), meta: t('dashboard.firstClassThisMonth'), badge: '11', tone: 'started' },
                { title: t('dashboard.avgClassRating'), meta: t('dashboard.studentFeedbackScore'), badge: '4.7 / 5', tone: 'started' },
                { title: t('dashboard.pendingSessionNotes'), meta: t('dashboard.overdueSubmissions'), badge: '3', tone: 'pending' },
            ],
        },
        'my-performance': {
            title: t('dashboard.myPerformance'),
            icon: 'bi-easel',
            note: t('dashboard.myPerformanceNote'),
            rows: [
                { title: t('dashboard.punctualityRate'), meta: t('dashboard.classesStartedOnTime'), badge: '96%', tone: 'started' },
                { title: t('dashboard.studentRetention'), meta: t('dashboard.reEnrolledPastStudents'), badge: '78%', tone: 'started' },
                { title: t('dashboard.avgFeedbackScore'), meta: t('dashboard.fromSessionReviews'), badge: '4.7 / 5', tone: 'started' },
                { title: t('dashboard.missedSessions'), meta: t('dashboard.unexcusedThisMonth'), badge: '0', tone: 'default' },
            ],
        },
        'class-stats': {
            title: t('dashboard.classStatistics'),
            icon: 'bi-table',
            note: t('dashboard.classStatisticsNote'),
            rows: [
                { title: 'Power Core', meta: t('dashboard.fillRateThisWeek'), badge: '24 / 24', tone: 'started' },
                { title: 'Evening Yin', meta: t('dashboard.avgAttendanceFourWeeks'), badge: '91%', tone: 'started' },
                { title: 'Prenatal Flow', meta: t('dashboard.waitlistCount'), badge: '0', tone: 'default' },
                { title: 'Breathwork Lab', meta: t('dashboard.onlineEnrollment'), badge: '32 / 40', tone: 'default' },
            ],
        },
        'student-progress': {
            title: t('dashboard.studentProgress'),
            icon: 'bi-person-check',
            note: t('dashboard.studentProgressNote'),
            rows: [
                { title: t('dashboard.assessmentsDue'), meta: t('dashboard.byEndOfThisWeek'), badge: '8', tone: 'pending' },
                { title: t('dashboard.completedThisMonth'), meta: t('dashboard.progressNotesSubmitted'), badge: '14', tone: 'started' },
                { title: t('dashboard.studentsAdvancingLevel'), meta: t('dashboard.trackedProgressions'), badge: '6', tone: 'started' },
                { title: t('dashboard.onWatchList'), meta: t('dashboard.attendanceOrFormConcerns'), badge: '3', tone: 'pending' },
            ],
        },
        earnings: {
            title: t('dashboard.earnings'),
            icon: 'bi-cash-stack',
            note: t('dashboard.earningsNote'),
            rows: [
                { title: t('dashboard.earningsThisMonth'), meta: t('dashboard.calculatedSessions'), badge: '$1,840', tone: 'started' },
                { title: t('dashboard.sessionsPaid'), meta: t('dashboard.processedPayments'), badge: '38', tone: 'started' },
                { title: t('dashboard.pendingPayout'), meta: t('dashboard.awaitingCycleClose'), badge: '$420', tone: 'pending' },
                { title: t('dashboard.yearToDate'), meta: t('dashboard.total2026Earnings'), badge: '$6,720', tone: 'default' },
            ],
        },
    },
    member: {
        overview: {
            title: t('dashboard.activityOverview'),
            icon: 'bi-bar-chart',
            note: t('dashboard.activityOverviewNote'),
            rows: [
                { title: t('dashboard.sessionsThisMonth'), meta: t('dashboard.attendedVsBooked'), badge: '11 / 14', tone: 'started' },
                { title: t('dashboard.currentStreak'), meta: t('dashboard.consecutiveActiveWeeks'), badge: t('dashboard.fiveWeeks'), tone: 'started' },
                { title: t('dashboard.nextClass'), meta: t('dashboard.today1830EveningYin'), badge: t('dashboard.today'), tone: 'pending' },
                { title: t('dashboard.sessionsLeftOnGoal'), meta: t('dashboard.weeklyTargetThreeSessions'), badge: '2', tone: 'default' },
            ],
        },
        'my-progress': {
            title: t('dashboard.myProgress'),
            icon: 'bi-graph-up-arrow',
            note: t('dashboard.myProgressNote'),
            rows: [
                { title: t('dashboard.levelProgress'), meta: t('dashboard.beginnerToIntermediate'), badge: '68%', tone: 'started' },
                { title: t('dashboard.sessionsCompleted'), meta: t('dashboard.allTime'), badge: '38', tone: 'started' },
                { title: t('dashboard.monthlyAttendance'), meta: 'April 2026', badge: '11 / 14', tone: 'started' },
                { title: t('dashboard.assessmentsPassed'), meta: t('dashboard.skillCheckpoints'), badge: '3', tone: 'default' },
            ],
        },
        attendance: {
            title: t('dashboard.attendanceRecord'),
            icon: 'bi-clipboard-check',
            note: t('dashboard.attendanceRecordNote'),
            rows: [
                { title: 'Power Core', meta: 'Apr 24 · Daniel Park', badge: t('dashboard.attended'), tone: 'started' },
                { title: 'Evening Yin', meta: 'Apr 22 · Ari Gomez', badge: t('dashboard.attended'), tone: 'started' },
                { title: 'Mobility Reset', meta: 'Apr 19 · Lina Tran', badge: t('dashboard.attended'), tone: 'started' },
                { title: 'Weekend Flow', meta: 'Apr 18 · Mia Chen', badge: t('dashboard.attended'), tone: 'started' },
            ],
        },
        payments: {
            title: t('dashboard.paymentRecords'),
            icon: 'bi-cash-stack',
            note: t('dashboard.paymentRecordsNote'),
            rows: [
                { title: 'Apr 2026 Membership', meta: 'Premium Flow Annual', badge: t('dashboard.paid'), tone: 'started' },
                { title: 'Workshop Credit Pack', meta: 'Apr 12, 2026', badge: '$60', tone: 'default' },
                { title: 'Mar 2026 Membership', meta: t('dashboard.autoCharged'), badge: t('dashboard.paid'), tone: 'started' },
                { title: t('dashboard.guestPass'), meta: 'Mar 22, 2026', badge: t('dashboard.used'), tone: 'default' },
            ],
        },
        achievements: {
            title: t('dashboard.achievements'),
            icon: 'bi-trophy',
            note: t('dashboard.achievementsNote'),
            rows: [
                { title: t('dashboard.fiveWeekStreak'), meta: t('dashboard.attendedOncePerWeek'), badge: t('dashboard.earned'), tone: 'started' },
                { title: t('dashboard.firstWorkshop'), meta: t('dashboard.attendedWellnessWorkshop'), badge: t('dashboard.earned'), tone: 'started' },
                { title: t('dashboard.thirtySessions'), meta: t('dashboard.completedThirtySessions'), badge: t('dashboard.earned'), tone: 'started' },
                { title: t('dashboard.tenWeekStreak'), meta: t('dashboard.tenConsecutiveWeeks'), badge: t('dashboard.locked'), tone: 'default' },
            ],
        },
    },
    };
});

const currentViewConfig = computed(() => viewConfigs.value[userRole.value]?.[activeView.value] ?? null);

const activities = computed(() => {
    void currentLocale.value;
    return [
        {
            title: 'Handling trial-class schedules for this week',
            state: t('dashboard.notStarted'),
            stateClass: 'pending',
            when: 'Apr 20 11:00',
            context: 'Downtown Studio',
        },
        {
            title: 'Analyze attendance drop in evening classes',
            state: t('dashboard.planned'),
            stateClass: 'default',
            when: 'Apr 21',
            context: 'Weekly review',
        },
        {
            title: 'Send monthly updates to management',
            state: t('dashboard.planned'),
            stateClass: 'default',
            when: 'Apr 22 16:30',
            context: 'Head office',
        },
        {
            title: 'Prepare kids yoga class onboarding pack',
            state: t('dashboard.started'),
            stateClass: 'started',
            when: 'Apr 23',
            context: 'Uptown Branch',
        },
        {
            title: 'Review teacher substitution requests',
            state: t('dashboard.notStarted'),
            stateClass: 'pending',
            when: 'Apr 24',
            context: 'Staffing board',
        },
    ];
});

const weekDays = computed(() => {
    void currentLocale.value;
    return [t('dashboard.sun'), t('dashboard.mon'), t('dashboard.tue'), t('dashboard.wed'), t('dashboard.thu'), t('dashboard.fri'), t('dashboard.sat')];
});

const calendarCells = [
    { date: '29', muted: true, events: [] },
    { date: '30', muted: true, events: [] },
    { date: '31', muted: true, events: [] },
    { date: '1', muted: false, events: [] },
    { date: '2', muted: false, events: [] },
    { date: '3', muted: false, events: [] },
    { date: '4', muted: false, events: [] },
    {
        date: '5',
        muted: false,
        events: [
            { text: '10:00 Discussion offe...', colorClass: 'ym-event-chip--rose' },
        ],
    },
    { date: '6', muted: false, events: [] },
    { date: '7', muted: false, events: [] },
    {
        date: '8',
        muted: false,
        events: [
            { text: '08:15 Price discuss...', colorClass: 'ym-event-chip--rose' },
            { text: '10:30 Proposal rev...', colorClass: 'ym-event-chip--rose' },
        ],
    },
    {
        date: '9',
        muted: false,
        events: [
            { text: 'Handing trial class', colorClass: 'ym-event-chip--green' },
            { text: '16:15 Team call', colorClass: 'ym-event-chip--blue' },
        ],
    },
    { date: '10', muted: false, events: [] },
    {
        date: '11',
        muted: false,
        events: [
            { text: 'Analyze attendance', colorClass: 'ym-event-chip--green' },
            { text: '10:30 Mgmt sync', colorClass: 'ym-event-chip--blue' },
        ],
    },
];

const cases = computed(() => {
    void currentLocale.value;
    return [
        { id: 11, title: 'Asking for compensation', priority: t('dashboard.high'), type: t('dashboard.problem'), customer: 'Lotus Branch' },
        { id: 7, title: 'Discount issue', priority: t('dashboard.normal'), type: t('dashboard.incident'), customer: 'Westside Studio' },
        { id: 6, title: 'Delivery status check', priority: t('dashboard.low'), type: t('dashboard.question'), customer: 'Riverside Branch' },
        { id: 5, title: 'Product support question', priority: t('dashboard.normal'), type: t('dashboard.question'), customer: 'Downtown Studio' },
    ];
});

const leadSources = computed(() => {
    void currentLocale.value;
    return [
        { name: 'dashboard.leadCall', value: 20, color: '#5d8fc0' },
        { name: 'dashboard.leadEmail', value: 32, color: '#4968a8' },
        { name: 'dashboard.leadExistingCustomer', value: 16, color: '#6a3d8a' },
        { name: 'dashboard.leadPublicRelations', value: 6, color: '#bd6b4a' },
        { name: 'dashboard.leadWebsite', value: 22, color: '#4a8c6f' },
        { name: 'dashboard.leadCampaign', value: 4, color: '#b04a5e' },
    ];
});

const dashletCatalog = computed(() => {
    void currentLocale.value;
    return [
        {
            id: 'calendar',
            title: t('dashboard.calendar'),
            type: 'calendar',
            span: 8,
            required: true,
            description: t('dashboard.calendarDescription'),
            previewRows: [t('dashboard.todayTimeline'), t('dashboard.upcomingSessions')],
        },
        {
            id: 'memo',
            title: t('dashboard.memo'),
            type: 'memo',
            span: 6,
            description: t('dashboard.memoDescription'),
            previewRows: [t('dashboard.meetingHighlights'), t('dashboard.quickReminders')],
        },
        {
            id: 'activities',
            title: t('dashboard.myActivities'),
            type: 'activities',
            span: 4,
            required: true,
            description: t('dashboard.activitiesDescription'),
            previewRows: [t('dashboard.upcomingTasks'), t('dashboard.lateFollowUps')],
        },
        {
            id: 'cases',
            title: t('dashboard.myCases'),
            type: 'cases',
            span: 6,
            required: false,
            description: t('dashboard.casesDescription'),
            previewRows: [t('dashboard.pendingApprovals'), t('dashboard.escalatedTickets')],
        },
        {
            id: 'my-leads',
            title: t('dashboard.myLeads'),
            type: 'generic',
            span: 4,
            description: t('dashboard.leadsDescription'),
            previewRows: [t('dashboard.newIncomingLeads'), t('dashboard.qualifiedPipeline')],
        },
        {
            id: 'lead-source',
            title: t('dashboard.opportunitiesByLeadSource'),
            type: 'lead-source',
            span: 6,
            required: false,
            description: t('dashboard.leadSourceDescription'),
            previewRows: [t('dashboard.topChannels'), t('dashboard.campaignPerformance')],
        },
    ];
});

const cloneDashlet = (dashlet) => ({
    ...dashlet,
    previewRows: Array.isArray(dashlet.previewRows) ? [...dashlet.previewRows] : [],
});

const createDashletFromId = (id) => {
    const definition = dashletCatalog.value.find((item) => item.id === id);
    return definition ? cloneDashlet(definition) : null;
};

const defaultDashletLayout = [
    { id: 'calendar', span: 8 },
    { id: 'lead-source', span: 4 },
    { id: 'activities', span: 6 },
    { id: 'cases', span: 6 },
];

const buildDefaultDashlets = () =>
    defaultDashletLayout
        .map(({ id, span }) => {
            const dashlet = createDashletFromId(id);
            return dashlet ? { ...dashlet, span } : null;
        })
        .filter(Boolean);

const dashlets = ref(buildDefaultDashlets());
const lockDashboard = ref(false);
const isDraggingDashlet = ref(false);
const showAddDashletModal = ref(false);
const dashletSearchQuery = ref('');

const filteredDashletCatalog = computed(() => {
    const query = dashletSearchQuery.value.trim().toLowerCase();
    if (!query) {
        return dashletCatalog.value;
    }

    return dashletCatalog.value.filter((item) => item.title.toLowerCase().includes(query));
});

const dashletClasses = (dashlet) => {
    const validSpan = [4, 6, 8, 12].includes(dashlet.span) ? dashlet.span : 6;

    return [
        'ym-surface',
        'ym-dashlet-card',
        `ym-dashlet-span-${validSpan}`,
        {
            'ym-dashlet-card--locked': lockDashboard.value,
            'ym-dashlet-card--reordering': isDraggingDashlet.value,
        },
    ];
};

const isDashletActive = (dashletId) => dashlets.value.some((item) => item.id === dashletId);

const addDashlet = (dashlet) => {
    if (isDashletActive(dashlet.id)) {
        return;
    }

    dashlets.value = [...dashlets.value, cloneDashlet(dashlet)];
    showAddDashletModal.value = false;
};

const removeDashlet = (dashletId) => {
    if (lockDashboard.value) {
        return;
    }

    dashlets.value = dashlets.value.filter((item) => item.id !== dashletId);
};

const handleDashletSortStart = () => {
    isDraggingDashlet.value = true;
};

const handleDashletSortEnd = () => {
    isDraggingDashlet.value = false;
};

const toViewKey = (value) => value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

const buildDefaultDashboardTabs = (role) => {
    const config = roleDashboard.value[role] ?? roleDashboard.value.member;
    const isAdminRole = role === 'admin';

    return config.tabs.map((label, index) => {
        const viewKey = index === 0 ? 'homepage' : (toViewKey(label) || `view-${index + 1}`);
        return {
            label,
            viewKey,
            href: index === 0 ? route('cms.dashboard') : (isAdminRole ? route('cms.dashboard', { view: viewKey }) : ''),
        };
    });
};

const defaultDashboardTabs = computed(() => buildDefaultDashboardTabs(userRole.value));

const createTabsDraft = (tabs) => tabs.slice(0, maxTopTabs).map((tab, index) => ({
    id: `tab-${index}-${tab.viewKey ?? index}`,
    label: (tab.label ?? '').toString().trim(),
}));

const hydrateDashboardTabs = (incomingTabs = []) => {
    const sourceTabs = incomingTabs.length ? incomingTabs : defaultDashboardTabs.value;
    const tabs = createTabsDraft(sourceTabs);

    while (tabs.length < maxTopTabs) {
        const fallback = defaultDashboardTabs.value[tabs.length] ?? { label: `View ${tabs.length + 1}` };
        tabs.push({
            id: `tab-fill-${tabs.length}`,
            label: fallback.label,
        });
    }

    return tabs;
};

const showEditDashboardModal = ref(false);
const dashboardTabError = ref('');
const newDashboardTabLabel = ref('');
const dashboardTabsDraft = ref(hydrateDashboardTabs());

watch(userRole, () => {
    dashboardTabsDraft.value = hydrateDashboardTabs();
    if (!isAdmin.value) {
        showAddDashletModal.value = false;
    }
});

watch(currentLocale, () => {
    dashlets.value = buildDefaultDashlets();
    dashboardTabsDraft.value = hydrateDashboardTabs();
});

const removeDashboardTab = (index) => {
    dashboardTabsDraft.value = dashboardTabsDraft.value.filter((_, tabIndex) => tabIndex !== index);
    dashboardTabError.value = '';
};

const addDashboardTab = () => {
    if (dashboardTabsDraft.value.length >= maxTopTabs) {
        dashboardTabError.value = t('dashboard.tabLimitError', { max: maxTopTabs });
        return;
    }

    const label = newDashboardTabLabel.value.trim();
    if (!label) {
        dashboardTabError.value = t('dashboard.enterTabNameError');
        return;
    }

    dashboardTabsDraft.value = [
        ...dashboardTabsDraft.value,
        {
            id: `tab-new-${Date.now()}`,
            label,
        },
    ];

    newDashboardTabLabel.value = '';
    dashboardTabError.value = '';
};

const emitDashboardTabsUpdated = (tabs) => {
    window.dispatchEvent(new CustomEvent('ym-dashboard-tabs-updated', {
        detail: {
            tabs,
        },
    }));
};

const normalizeDashboardTabs = (draftTabs) => draftTabs.map((tab, index) => {
    const label = tab.label.trim();
    const viewKey = index === 0 ? 'homepage' : (toViewKey(label) || `view-${index + 1}`);

    return {
        label,
        viewKey,
        href: index === 0 ? route('cms.dashboard') : (isAdmin.value ? route('cms.dashboard', { view: viewKey }) : ''),
    };
});

const saveDashboardTabs = () => {
    dashboardTabError.value = '';

    if (dashboardTabsDraft.value.length !== maxTopTabs) {
        dashboardTabError.value = t('dashboard.tabCountError', { max: maxTopTabs });
        return;
    }

    for (let index = 0; index < dashboardTabsDraft.value.length; index += 1) {
        const currentLabel = dashboardTabsDraft.value[index].label.trim();
        if (!currentLabel) {
            dashboardTabError.value = t('dashboard.tabLabelRequiredError');
            return;
        }
    }

    emitDashboardTabsUpdated(normalizeDashboardTabs(dashboardTabsDraft.value));
    dashboardNotice.value = t('dashboard.tabsUpdatedNotice');
    showEditDashboardModal.value = false;
};

const resetDashboardTabs = () => {
    const resetTabs = defaultDashboardTabs.value;
    dashboardTabsDraft.value = hydrateDashboardTabs(resetTabs);
    emitDashboardTabsUpdated(resetTabs);
};

const badgeClass = (tone) => [
    'ym-status-pill',
    tone === 'pending' ? 'ym-status-pill--pending' : '',
    tone === 'started' ? 'ym-status-pill--started' : '',
];

const handleDashboardAction = (event) => {
    const action = event?.detail?.action;
    const tabs = event?.detail?.tabs;

    if (Array.isArray(tabs) && tabs.length) {
        dashboardTabsDraft.value = hydrateDashboardTabs(tabs);
    }

    if (action === 'edit-dashboard') {
        showEditDashboardModal.value = true;
        return;
    }

    if (action === 'view-change') {
        activeView.value = event?.detail?.viewKey ?? 'homepage';
        return;
    }

    if (action === 'add-dashlet') {
        if (isAdmin.value) {
            showAddDashletModal.value = true;
        } else {
            dashboardNotice.value = t('dashboard.dashletAdminOnly');
        }
        return;
    }

    if (action === 'reset-dashboard') {
        if (isAdmin.value) {
            dashlets.value = buildDefaultDashlets();
        }
        activeView.value = 'homepage';
        resetDashboardTabs();
    }
};

const openActionFromUrl = () => {
    const currentUrl = getCurrentUrl();
    if (!currentUrl.startsWith(route('cms.dashboard', undefined, false))) {
        return;
    }

    const query = currentUrl.split('?')[1] ?? '';
    const params = new URLSearchParams(query);
    const action = params.get('dashboardAction');

    if (action) {
        handleDashboardAction({ detail: { action } });
        params.delete('dashboardAction');
        const nextQuery = params.toString();
        const dashboardPath = route('cms.dashboard', undefined, false);
        window.history.replaceState({}, '', nextQuery ? `${dashboardPath}?${nextQuery}` : dashboardPath);
    }
};

onMounted(() => {
    window.addEventListener('ym-dashboard-action', handleDashboardAction);
    openActionFromUrl();
});

onBeforeUnmount(() => {
    window.removeEventListener('ym-dashboard-action', handleDashboardAction);
});
</script>

<template>
    <AppLayout :title="pageTitle">
        <template v-if="isAdmin">
            <section :key="renderKey" class="ym-dashlet-grid">
                <Draggable
                    v-model="dashlets"
                    item-key="id"
                    tag="div"
                    class="ym-dashlet-grid-inner"
                    handle=".ym-drag-handle"
                    ghost-class="ym-dashlet-card--placeholder"
                    chosen-class="ym-dashlet-card--dragging"
                    drag-class="ym-dashlet-card--sorting"
                    :animation="240"
                    :disabled="lockDashboard"
                    @start="handleDashletSortStart"
                    @end="handleDashletSortEnd"
                >
                    <template #item="{ element: dashlet }">
                        <article :class="dashletClasses(dashlet)">
                            <header class="ym-panel-head">
                                <div class="ym-dashlet-head">
                                    <span class="ym-drag-handle" aria-hidden="true">
                                        <i class="bi bi-grip-vertical" />
                                    </span>
                                    <h2 class="ym-panel-title">{{ dashlet.title }}</h2>
                                </div>

                                <button
                                    type="button"
                                    class="ym-dashlet-remove"
                                    :disabled="lockDashboard"
                                    :aria-label="$t('dashboard.removeDashlet')"
                                    @click="removeDashlet(dashlet.id)"
                                >
                                    <i class="bi bi-x-lg" />
                                </button>
                            </header>

                            <div v-if="dashlet.type === 'activities'" class="ym-activity-list">
                                <article v-for="activity in activities" :key="activity.title" class="ym-activity-item">
                                    <a href="#" class="ym-activity-title">{{ activity.title }}</a>
                                    <p class="ym-activity-meta">
                                        <span
                                            :class="[
                                                'ym-status-pill',
                                                activity.stateClass === 'pending' ? 'ym-status-pill--pending' : '',
                                                activity.stateClass === 'started' ? 'ym-status-pill--started' : '',
                                            ]"
                                        >
                                            {{ activity.state }}
                                        </span>
                                        <span>{{ activity.when }}</span>
                                        <span>{{ activity.context }}</span>
                                    </p>
                                </article>
                                <a href="#" class="ym-show-more">{{ $t('dashboard.showMore') }}</a>
                            </div>

                            <div v-else-if="dashlet.type === 'calendar'">
                                <div class="ym-calendar-grid">
                                    <div v-for="day in weekDays" :key="day" class="ym-calendar-day-name">{{ day }}</div>

                                    <div
                                        v-for="cell in calendarCells"
                                        :key="`${cell.date}-${cell.muted ? 'm' : 'a'}`"
                                        :class="['ym-calendar-cell', { 'ym-calendar-cell--muted': cell.muted }]"
                                    >
                                        <span class="ym-calendar-date">{{ cell.date }}</span>
                                        <span
                                            v-for="event in cell.events"
                                            :key="event.text"
                                            :class="['ym-event-chip', event.colorClass]"
                                        >
                                            {{ event.text }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="dashlet.type === 'cases'" class="ym-section">
                                <div class="ym-case-list">
                                    <article v-for="caseItem in cases" :key="caseItem.id" class="ym-case-row">
                                        <span class="ym-case-id">{{ caseItem.id }}</span>
                                        <div>
                                            <p class="ym-case-name">{{ caseItem.title }}</p>
                                            <p class="ym-case-meta">
                                                <span :class="['ym-status-pill', caseItem.priority === 'High' ? 'ym-status-pill--pending' : '']">
                                                    {{ caseItem.priority }}
                                                </span>
                                                <span>{{ caseItem.type }}</span>
                                                <span>{{ caseItem.customer }}</span>
                                            </p>
                                        </div>
                                    </article>
                                </div>
                            </div>

                            <div v-else-if="dashlet.type === 'lead-source'" class="ym-section">
                                <div class="ym-pie-wrap">
                                    <div class="ym-pie" role="img" :aria-label="$t('dashboard.opportunitiesByLeadSource')" />
                                    <div class="ym-legend">
                                        <div v-for="item in leadSources" :key="item.name" class="ym-legend-item">
                                            <span class="ym-legend-dot" :style="{ background: item.color }" />
                                            <span>{{ $t(item.name) }} ({{ item.value }}%)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="dashlet.type === 'memo'" class="ym-section">
                                <p class="ym-card-note">{{ $t('dashboard.memoHint') }}</p>
                                <div class="ym-note-banner">
                                    {{ $t('dashboard.memoContent') }}
                                </div>
                            </div>

                            <div v-else class="ym-section">
                                <p class="ym-card-note">{{ dashlet.description }}</p>
                                <div class="ym-list">
                                    <div v-for="item in dashlet.previewRows" :key="item" class="ym-list-item">
                                        <span>{{ item }}</span>
                                        <span class="ym-list-meta">{{ $t('dashboard.preview') }}</span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </template>
                </Draggable>
            </section>
        </template>

        <template v-else>
            <div
                :key="renderKey"
            >
                <div
                    v-if="dashboardNotice"
                    class="ym-info-row"
                    style="border-radius: 0.42rem; margin-bottom: 0.75rem;"
                >
                    <i class="bi bi-info-circle ym-info-icon" />
                    <span>{{ dashboardNotice }}</span>
                </div>

                <template v-if="activeView === 'homepage'">
                    <div class="ym-stat-strip">
                        <div v-for="metric in dashboardConfig.metrics" :key="metric.label" class="ym-stat">
                            <p class="ym-stat-label">{{ metric.label }}</p>
                            <p class="ym-stat-value">{{ metric.value }}</p>
                            <p class="ym-stat-note">{{ metric.note }}</p>
                        </div>
                    </div>

                    <div class="ym-page-cols">
                        <div class="ym-pane">
                            <div class="ym-pane-head">
                                <div class="ym-pane-title-wrap">
                                    <i :class="['bi', dashboardConfig.primaryPanel.icon, 'ym-pane-icon']" />
                                    <h2 class="ym-pane-title">{{ dashboardConfig.primaryPanel.title }}</h2>
                                </div>
                            </div>
                            <div class="ym-pane-body">
                                <div class="ym-row-list">
                                    <div
                                        v-for="item in dashboardConfig.primaryPanel.rows"
                                        :key="item.title"
                                        class="ym-row"
                                    >
                                        <div class="ym-row-main">
                                            <p class="ym-row-title">{{ item.title }}</p>
                                            <p class="ym-row-meta">{{ item.meta }}</p>
                                        </div>
                                        <div class="ym-row-aside">
                                            <span :class="badgeClass(item.tone)">{{ item.badge }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ym-pane">
                            <div class="ym-pane-head">
                                <div class="ym-pane-title-wrap">
                                    <i :class="['bi', dashboardConfig.secondaryPanel.icon, 'ym-pane-icon']" />
                                    <h2 class="ym-pane-title">{{ dashboardConfig.secondaryPanel.title }}</h2>
                                </div>
                            </div>
                            <div class="ym-pane-body">
                                <div class="ym-row-list">
                                    <div
                                        v-for="item in dashboardConfig.secondaryPanel.rows"
                                        :key="item.title"
                                        class="ym-row"
                                    >
                                        <div class="ym-row-main">
                                            <p class="ym-row-title">{{ item.title }}</p>
                                            <p class="ym-row-meta">{{ item.meta }}</p>
                                        </div>
                                        <div class="ym-row-aside">
                                            <span :class="badgeClass(item.tone)">{{ item.badge }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ym-pane mt-4">
                        <div class="ym-pane-head">
                            <div class="ym-pane-title-wrap">
                                <i class="bi bi-calendar-week ym-pane-icon" />
                                <h2 class="ym-pane-title">{{ dashboardConfig.weeklyPanel.title }}</h2>
                            </div>
                        </div>
                        <div class="ym-pane-body">
                            <div class="ym-timetable-scroll">
                                <div class="ym-timetable">
                                    <div
                                        v-for="day in dashboardConfig.weeklyPanel.days"
                                        :key="day.day"
                                        class="ym-timetable-col"
                                    >
                                        <div class="ym-timetable-head">
                                            <p class="ym-timetable-day">{{ day.dayShort }}</p>
                                            <p class="ym-timetable-date">{{ day.date }}</p>
                                        </div>
                                        <div class="ym-timetable-body">
                                            <div
                                                v-for="entry in day.entries"
                                                :key="entry.title"
                                                :class="['ym-timetable-slot', { 'ym-timetable-slot--coach': isCoach }]"
                                            >
                                                <p class="ym-timetable-time">{{ entry.time }}</p>
                                                <p class="ym-timetable-name">{{ entry.title }}</p>
                                                <p class="ym-timetable-sub">{{ entry.meta }}</p>
                                            </div>
                                            <div v-if="!day.entries.length" class="ym-timetable-empty">
                                                {{ dashboardConfig.weeklyPanel.emptyMessage }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-else-if="currentViewConfig">
                    <div class="ym-pane">
                        <div class="ym-pane-head">
                            <div class="ym-pane-title-wrap">
                                <i :class="['bi', currentViewConfig.icon, 'ym-pane-icon']" />
                                <h2 class="ym-pane-title">{{ currentViewConfig.title }}</h2>
                            </div>
                        </div>
                        <div class="ym-pane-body">
                            <div class="ym-row-list">
                                <div
                                    v-for="item in currentViewConfig.rows"
                                    :key="item.title"
                                    class="ym-row"
                                >
                                    <div class="ym-row-main">
                                        <p class="ym-row-title">{{ item.title }}</p>
                                        <p class="ym-row-meta">{{ item.meta }}</p>
                                    </div>
                                    <div class="ym-row-aside">
                                        <span :class="badgeClass(item.tone)">{{ item.badge }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="ym-info-row">
                                <i class="bi bi-info-circle ym-info-icon" />
                                <span>{{ currentViewConfig.note }}</span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <Modal :show="showEditDashboardModal" :title="$t('dashboard.editDashboardTabs')" @close="showEditDashboardModal = false">
            <div class="ym-dashboard-modal-actions">
                <button type="button" class="ym-btn-sm" @click="saveDashboardTabs">{{ $t('common.save') }}</button>
                <button type="button" class="ym-btn-outline" @click="showEditDashboardModal = false">{{ $t('common.cancel') }}</button>
            </div>

            <p v-if="dashboardTabError" class="ym-field-error">{{ dashboardTabError }}</p>

            <p class="ym-card-note">
                {{ $t('dashboard.configureTabsNote') }}
            </p>

            <div class="ym-tab-editor-list mt-3">
                <div v-for="(tab, index) in dashboardTabsDraft" :key="tab.id" class="ym-tab-editor-row">
                    <span class="ym-tab-handle" aria-hidden="true">
                        <i class="bi bi-grip-vertical" />
                    </span>
                    <input v-model="tab.label" type="text" class="ym-input" />
                    <button
                        type="button"
                        class="ym-tab-remove"
                        :disabled="dashboardTabsDraft.length === 1"
                        @click="removeDashboardTab(index)"
                    >
                        <i class="bi bi-x-lg" />
                    </button>
                </div>

                <div class="ym-tab-editor-row ym-tab-editor-row--add">
                    <input
                        v-model="newDashboardTabLabel"
                        type="text"
                        class="ym-input"
                        :placeholder="$t('dashboard.typeAndPressEnter')"
                        @keydown.enter.prevent="addDashboardTab"
                    />
                    <button type="button" class="ym-tab-add" :disabled="dashboardTabsDraft.length >= maxTopTabs" @click="addDashboardTab">
                        <i class="bi bi-plus-lg" />
                    </button>
                </div>
            </div>

            <label v-if="isAdmin" class="ym-lock-toggle mt-3">
                <span>{{ $t('dashboard.lockDashboard') }}</span>
                <input v-model="lockDashboard" type="checkbox" />
            </label>
        </Modal>

        <Modal
            v-if="isAdmin"
            :show="showAddDashletModal"
            :title="$t('dashboard.addDashlet')"
            @close="showAddDashletModal = false"
        >
            <label class="ym-search-wrap ym-dashboard-modal-search" :aria-label="$t('common.search')">
                <i class="bi bi-search ym-search-icon" />
                <input
                    v-model="dashletSearchQuery"
                    type="search"
                    class="ym-search"
                    :placeholder="$t('common.search')"
                    :aria-label="$t('common.search')"
                />
            </label>

            <div class="ym-dashlet-catalog">
                <button
                    v-for="item in filteredDashletCatalog"
                    :key="item.id"
                    type="button"
                    class="ym-dashlet-catalog-item"
                    :disabled="isDashletActive(item.id)"
                    @click="addDashlet(item)"
                >
                    <span>{{ item.title }}</span>
                    <small>{{ isDashletActive(item.id) ? $t('dashboard.added') : $t('dashboard.add') }}</small>
                </button>
            </div>
        </Modal>
    </AppLayout>
</template>