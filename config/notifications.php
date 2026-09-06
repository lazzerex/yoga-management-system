<?php

// 'audience' is a permission list: it decides which rows a viewer sees on their
// preference card, never what they may access. Adding 'mail' to 'channels' is what
// turns email on; until then every mail default below is inert.

return [

    'channels' => ['database'],

    'events' => [

        'tuition.due_soon' => [
            'label' => 'notifications.tuitionDueSoon.label',
            'channels' => ['mail' => true, 'database' => true],
            'audience' => ['member.dashboard.view'],
        ],

        'tuition.overdue' => [
            'label' => 'notifications.tuitionOverdue.label',
            'channels' => ['mail' => true, 'database' => true],
            'audience' => ['member.dashboard.view'],
        ],

        'class.reminder' => [
            'label' => 'notifications.classReminder.label',
            'channels' => ['mail' => false, 'database' => true],
            'audience' => ['member.dashboard.view', 'coach.dashboard.view'],
        ],

        'enrollment.promoted' => [
            'label' => 'notifications.enrollmentPromoted.label',
            'channels' => ['mail' => true, 'database' => true],
            'audience' => ['member.enrollments.manage'],
        ],

        'lesson_plan.submitted' => [
            'label' => 'notifications.lessonPlanSubmitted.label',
            'channels' => ['mail' => false, 'database' => true],
            'audience' => ['operations.plans.review'],
        ],

        'lesson_plan.reviewed' => [
            'label' => 'notifications.lessonPlanReviewed.label',
            'channels' => ['mail' => true, 'database' => true],
            'audience' => ['operations.plans.manage'],
        ],

        'member.registered' => [
            'label' => 'notifications.memberRegistered.label',
            'channels' => ['mail' => true, 'database' => true],
            'audience' => ['admin.users.view'],
        ],

        'enrollment.cancelled_by_staff' => [
            'label' => 'notifications.enrollmentCancelledByStaff.label',
            'channels' => ['mail' => true, 'database' => true],
            'audience' => ['member.enrollments.manage'],
        ],

        'class.cancelled' => [
            'label' => 'notifications.classCancelled.label',
            'channels' => ['mail' => true, 'database' => true],
            'audience' => ['member.enrollments.manage', 'coach.dashboard.view'],
        ],

    ],

];
