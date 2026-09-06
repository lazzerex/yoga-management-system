<?php

return [
    'tuitionDueSoon' => [
        'label' => 'Học phí sắp đến hạn',
        'subject' => 'Hóa đơn :invoice đến hạn ngày :date',
        'line' => 'Hóa đơn :invoice với số tiền :amount VND đến hạn vào ngày :date.',
        'action' => 'Xem học phí của tôi',
        'bell' => 'Hóa đơn :invoice đến hạn ngày :date',
    ],
    'tuitionOverdue' => [
        'label' => 'Học phí quá hạn',
        'subject' => 'Hóa đơn :invoice đã quá hạn',
        'line' => 'Hóa đơn :invoice với số tiền :amount VND đã đến hạn ngày :date và vẫn chưa thanh toán.',
        'action' => 'Xem học phí của tôi',
        'bell' => 'Hóa đơn :invoice đã đến hạn ngày :date',
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
        'line' => 'Một chỗ trống vừa xuất hiện và bạn đã được chuyển từ danh sách chờ vào lớp :class ngày :date lúc :time.',
        'action' => 'Xem lớp của tôi',
        'bell' => 'Bạn đã được chuyển khỏi danh sách chờ lớp :class ngày :date',
    ],
    'lessonPlanSubmitted' => [
        'label' => 'Giáo án gửi chờ duyệt',
        'subject' => 'Giáo án chờ duyệt: :title',
        'line' => ':coach đã gửi giáo án :title để chờ duyệt.',
        'action' => 'Duyệt giáo án',
        'bell' => ':coach đã gửi :title để chờ duyệt',
    ],
    'lessonPlanReviewed' => [
        'label' => 'Giáo án đã được duyệt',
        'subject' => 'Giáo án :title của bạn đã được :status',
        'line' => 'Giáo án :title của bạn đã được :reviewer :status.',
        'action' => 'Xem giáo án',
        'bell' => ':title đã được :status',
    ],
    'memberRegistered' => [
        'label' => 'Thành viên mới đăng ký',
        'subject' => 'Thành viên mới đăng ký: :name',
        'line' => ':name đã đăng ký tài khoản với tên đăng nhập :username.',
        'action' => 'Xem tài khoản',
        'bell' => ':name đã đăng ký tài khoản',
    ],
    'enrollmentCancelledByStaff' => [
        'label' => 'Lượt đăng ký bị trung tâm hủy',
        'subject' => 'Lượt đăng ký lớp :class của bạn đã bị hủy',
        'line' => 'Lượt đăng ký lớp :class ngày :date lúc :time của bạn đã bị trung tâm hủy.',
        'action' => 'Xem lớp của tôi',
        'bell' => 'Lượt đăng ký lớp :class ngày :date đã bị hủy',
    ],
    'classCancelled' => [
        'label' => 'Lớp bị hủy',
        'subject' => 'Lớp :class ngày :date đã bị hủy',
        'line' => 'Lớp :class ngày :date lúc :time đã bị hủy.',
        'action' => 'Xem lịch của tôi',
        'bell' => 'Lớp :class ngày :date đã bị hủy',
    ],
    'status' => [
        'approved' => 'duyệt',
        'rejected' => 'từ chối',
    ],
];
