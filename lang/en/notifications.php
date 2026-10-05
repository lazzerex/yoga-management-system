<?php

return [
    'tuitionDueSoon' => [
        'label' => 'Tuition due soon',
        'subject' => 'Invoice :invoice is due on :date',
        'line' => 'Your invoice :invoice for :amount VND is due on :date.',
        'action' => 'View my membership',
        'bell' => 'Invoice :invoice is due on :date',
    ],
    'tuitionOverdue' => [
        'label' => 'Tuition overdue',
        'subject' => 'Invoice :invoice is overdue',
        'line' => 'Your invoice :invoice for :amount VND was due on :date and is still open.',
        'action' => 'View my membership',
        'bell' => 'Invoice :invoice was due on :date',
    ],
    'classReminder' => [
        'label' => 'Class reminder',
        'subject' => 'Your :class class is tomorrow',
        'line' => ':class starts tomorrow, :date at :time.',
        'action' => 'View my schedule',
        'bell' => ':class tomorrow at :time',
    ],
    'enrollmentPromoted' => [
        'label' => 'Moved off the waiting list',
        'subject' => 'You have a place in :class',
        'line' => 'A place opened up and you moved off the waiting list for :class on :date at :time.',
        'action' => 'View my classes',
        'bell' => 'You moved off the waiting list for :class on :date',
    ],
    'lessonPlanSubmitted' => [
        'label' => 'Lesson plan submitted for review',
        'subject' => 'Lesson plan waiting for review: :title',
        'line' => ':coach submitted the lesson plan :title for review.',
        'action' => 'Review the plan',
        'bell' => ':coach submitted :title for review',
    ],
    'lessonPlanReviewed' => [
        'label' => 'Lesson plan reviewed',
        'subject' => 'Your lesson plan :title was :status',
        'line' => 'Your lesson plan :title was :status by :reviewer.',
        'action' => 'View the plan',
        'bellApproved' => ':title was approved',
        'bellRejected' => ':title was rejected',
    ],
    'memberRegistered' => [
        'label' => 'New member registered',
        'subject' => 'New member registration: :name',
        'line' => ':name registered an account with the username :username.',
        'action' => 'View the account',
        'bell' => ':name registered an account',
    ],
    'enrollmentCancelledByStaff' => [
        'label' => 'Booking cancelled by the centre',
        'subject' => 'Your booking for :class was cancelled',
        'line' => 'Your booking for :class on :date at :time was cancelled by the centre.',
        'action' => 'View my classes',
        'bell' => 'Your booking for :class on :date was cancelled',
    ],
    'classCancelled' => [
        'label' => 'Class cancelled',
        'subject' => ':class on :date has been cancelled',
        'line' => 'The :class class on :date at :time has been cancelled.',
        'action' => 'View my schedule',
        'bell' => ':class on :date was cancelled',
    ],
    'status' => [
        'approved' => 'approved',
        'rejected' => 'rejected',
    ],
];
