<?php

return [
    'tuitionDueSoon' => [
        'label' => 'Học phí sắp đến hạn',
        'subject' => 'Hoá đơn :invoice đến hạn ngày :date',
        'line' => 'Hoá đơn :invoice với số tiền :amount VND đến hạn vào ngày :date.',
        'action' => 'Xem học phí của tôi',
        'bell' => 'Hoá đơn :invoice đến hạn ngày :date',
    ],
    'tuitionOverdue' => [
        'label' => 'Học phí quá hạn',
        'subject' => 'Hoá đơn :invoice đã quá hạn',
        'line' => 'Hoá đơn :invoice với số tiền :amount VND đã đến hạn ngày :date và vẫn chưa thanh toán.',
        'action' => 'Xem học phí của tôi',
        'bell' => 'Hoá đơn :invoice đã đến hạn ngày :date',
    ],
    'classReminder' => [
        'label' => 'Nhắc lịch lớp',
        'subject' => 'Lớp :class của bạn diễn ra vào ngày mai',
        'line' => 'Lớp :class bắt đầu vào ngày mai, :date lúc :time.',
        'action' => 'Xem lịch của tôi',
        'bell' => 'Lớp :class ngày mai lúc :time',
    ],
    'enrollmentPromoted' => [
        'label' => 'Được chuyển khỏi danh sách chờ',
        'subject' => 'Bạn đã có chỗ trong lớp :class',
        'line' => 'Lớp :class ngày :date lúc :time vừa có chỗ trống và bạn đã được xếp chỗ từ danh sách chờ.',
        'action' => 'Xem lớp của tôi',
        'bell' => 'Bạn đã được chuyển khỏi danh sách chờ lớp :class ngày :date',
    ],
    'lessonPlanSubmitted' => [
        'label' => 'Giáo án mới chờ duyệt',
        'subject' => 'Giáo án chờ duyệt: :title',
        'line' => ':coach đã gửi giáo án :title để chờ duyệt.',
        'action' => 'Duyệt giáo án',
        'bell' => ':coach đã gửi :title để chờ duyệt',
    ],
    'lessonPlanReviewed' => [
        'label' => 'Kết quả duyệt giáo án',
        'subject' => 'Giáo án :title của bạn đã :status',
        'line' => 'Giáo án :title của bạn đã :status (người duyệt: :reviewer).',
        'action' => 'Xem giáo án',
        'bellApproved' => ':title đã được duyệt',
        'bellRejected' => ':title đã bị từ chối duyệt',
    ],
    'memberRegistered' => [
        'label' => 'Học viên mới đăng ký',
        'subject' => 'Học viên mới đăng ký: :name',
        'line' => ':name đã đăng ký tài khoản với tên đăng nhập :username.',
        'action' => 'Xem tài khoản',
        'bell' => ':name đã đăng ký tài khoản',
    ],
    'enrollmentCancelledByStaff' => [
        'label' => 'Lượt đăng ký bị trung tâm huỷ',
        'subject' => 'Lượt đăng ký lớp :class của bạn đã bị huỷ',
        'line' => 'Lượt đăng ký lớp :class ngày :date lúc :time của bạn đã bị trung tâm huỷ.',
        'action' => 'Xem lớp của tôi',
        'bell' => 'Lượt đăng ký lớp :class ngày :date đã bị huỷ',
    ],
    'classCancelled' => [
        'label' => 'Lớp bị huỷ',
        'subject' => 'Lớp :class ngày :date đã bị huỷ',
        'line' => 'Lớp :class ngày :date lúc :time đã bị huỷ.',
        'action' => 'Xem lịch của tôi',
        'bell' => 'Lớp :class ngày :date đã bị huỷ',
    ],
    'status' => [
        'approved' => 'được duyệt',
        'rejected' => 'bị từ chối duyệt',
    ],
];
