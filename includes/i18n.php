<?php
/**
 * Internationalization (i18n) & Auto Device Language Detection Helper
 * Supports: English ('en'), Chinese ('zh'), Bahasa Melayu ('ms')
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class I18n {
    private static ?string $currentLang = null;

    private static array $supported = ['en', 'zh', 'ms'];

    private static array $translations = [
        'en' => [
            // Brand & Nav
            'brand_title' => 'UE Campus Facility Booking System',
            'nav_facilities' => 'Facilities',
            'nav_my_bookings' => 'My Bookings',
            'nav_equipment' => 'Equipment',
            'nav_maintenance' => 'Maintenance',
            'nav_feedback' => 'Feedback',
            'theme_toggle' => 'Toggle Light/Dark Theme',
            'lang_label' => 'Language',

            // Home Page
            'hero_badge' => 'TAR UMT Centralized Campus Facility Booking Portal',
            'hero_title' => 'UE Campus Facility Booking System',
            'hero_subtitle' => 'Reserve sports courts and academic discussion rooms in real time. Use your dynamic QR code for swift on-site entrance verification, or lodge maintenance defect reports and feedback to keep campus facilities in top condition.',
            'btn_browse_facilities' => 'Browse Available Facilities ↓',
            'btn_my_reservations' => 'My Reservations & QR Check-In ↓',
            'quick_actions' => 'Quick Actions',
            'btn_report_defect' => '+ Report Facility Defect',
            'btn_rate_completed' => '+ Rate Completed Session',
            'btn_view_reviews' => '★ View Facility Reviews',

            // Home Reservations & QR
            'sec_reservations' => 'My Active & Upcoming Reservations',
            'sec_reservations_sub' => 'Check in at the venue entrance using your dynamic QR code, or rate completed sessions.',
            'lbl_booking' => 'Booking',
            'lbl_location' => 'Location:',
            'lbl_date' => 'Date:',
            'lbl_time' => 'Time:',
            'lbl_purpose' => 'Purpose:',
            'lbl_today' => '(Today)',
            'status_confirmed' => 'Confirmed',
            'status_checked_in' => 'Checked-In',
            'status_completed' => 'Completed',
            'status_no_show' => 'No-Show',
            'msg_checked_in_at' => 'Attendance Verified: Checked in at',
            'msg_expired_noshow' => 'Expired No-Show: Check-in window passed (15-min limit). Facility released.',
            'btn_qr_checkin' => 'QR Check-In',
            'btn_view_qr' => 'View Pass / QR',
            'btn_rate_review' => 'Rate & Review',
            'btn_report_short' => 'Report Defect',
            'no_bookings_msg' => 'You currently have no active facility bookings.',
            'btn_book_now' => 'Book a Campus Facility Now',

            // Auto-Release Engine
            'uf_badge' => 'Unique Feature Demonstration (Student 2: Tie Sing Wee)',
            'uf_title' => '15-Minute No-Show Auto-Release Engine',
            'uf_desc' => 'Prevents venue hoarding and idle courts. If a student does not perform a QR check-in within 15 minutes of their reserved session start time, the auto-release engine cancels the reservation as No-Show and unlocks the time slot for other students.',
            'btn_seed_overdue' => '+ Seed Overdue Test Session',
            'btn_run_autorelease' => '⚡ Run Auto-Release Engine Now',

            // Home Facilities Directory
            'sec_facilities' => 'Browse Campus Facilities',
            'sec_facilities_sub' => 'Explore sports courts and academic study rooms. Check verified ratings and availability before booking.',
            'filter_all' => 'All Facilities',
            'filter_sports' => 'Sports Facilities',
            'filter_academic' => 'Academic Facilities',
            'search_facility_ph' => 'Search venue name or block...',
            'btn_search' => 'Search',
            'status_available' => 'Available',
            'status_maintenance' => 'Under Maintenance',
            'lbl_capacity' => 'Capacity:',
            'lbl_pax' => 'pax',
            'lbl_reviews_count' => 'reviews',
            'btn_quick_book' => 'Quick Book',
            'btn_locked_maint' => 'Locked for Maintenance',
            'btn_reviews' => 'Reviews',
            'btn_report' => 'Report',

            // Home Service Tiles
            'srv_defect_title' => 'Report Facility Defect',
            'srv_defect_desc' => 'Found broken equipment, faulty lighting, or an electrical issue? Lodge a maintenance ticket with category filtering and real-time technician tracking.',
            'srv_defect_btn' => 'Lodge Defect Ticket →',
            'srv_rate_title' => 'Rate & Review Session',
            'srv_rate_desc' => 'Completed your booking session? Share your feedback on venue quality and equipment condition to guide other students and campus facilities staff.',
            'srv_rate_btn' => 'Submit Session Review →',
            'srv_logs_title' => 'Facility Logs & Moderation',
            'srv_logs_desc' => 'Access the Maintenance Ticket Log to monitor repair progress, or inspect official administrator replies and review moderation records.',
            'srv_ticket_log' => 'Ticket Log',
            'srv_all_reviews' => 'All Reviews',

            // Maintenance Pages
            'maint_title' => 'Maintenance Ticket Log & Work Order Dashboard',
            'maint_sub' => 'Monitor reported facility defects, filter by urgency or repair status, and track technician assignments.',
            'btn_log_ticket' => '+ Log New Defect Ticket',
            'kpi_active' => 'Total Active Tickets',
            'kpi_locked' => 'Critical / Locked Venues',
            'kpi_progress' => 'In Progress Repairs',
            'kpi_resolved' => 'Resolved This Month',
            'btn_apply_filter' => 'Apply Filter',
            'col_ticket_id' => 'Ticket ID',
            'col_facility' => 'Facility',
            'col_category' => 'Defect Category',
            'col_urgency' => 'Urgency',
            'col_tech' => 'Assigned Technician',
            'col_status' => 'Status',
            'col_reported' => 'Reported At',
            'col_action' => 'Action',
            'btn_manage_update' => 'Manage / Update',
            'btn_view_details' => 'View Details',

            // Report Defect
            'report_title' => 'Report Facility Defect / Damage',
            'report_sub' => 'Submit a maintenance ticket for broken equipment, electrical faults, or safety hazards.',
            'lbl_affected_facility' => 'Affected Facility *',
            'lbl_defect_category' => 'Defect Category *',
            'lbl_severity' => 'Severity Level *',
            'lbl_defect_desc' => 'Defect Description *',
            'btn_submit_ticket' => 'Submit Defect Ticket',

            // Update Ticket
            'update_title' => 'Update Repair Status, Assign Technician & Close/Delete Ticket',
            'btn_back_ticket_log' => '← Back to Ticket Log',
            'btn_save_repair' => 'Save Repair Update',

            // Feedback Pages
            'fb_title' => 'Facility Reviews & Average Rating Summary',
            'fb_sub' => 'Browse verified student reviews and edit your own submitted feedback.',
            'btn_submit_feedback' => '+ Submit Feedback',
            'btn_admin_mod' => 'Admin Moderation →',
            'avg_rating_label' => 'Avg Facility Rating',
            'your_review_tag' => '(Your Review)',
            'btn_edit_my_review' => 'Edit My Review',
            'btn_moderate_reply' => 'Moderate / Reply',

            // Completed Sessions
            'comp_title' => 'Completed Sessions',
            'comp_sub' => 'Select a completed facility booking below to submit or update your session feedback.',
            'comp_empty' => 'You do not have any completed booking sessions yet.',
            'comp_browse' => 'Browse Campus Facilities',
            'comp_reviewed' => 'Reviewed',
            'btn_view_all_reviews' => 'View All Facility Reviews',
            'btn_back_home' => '← Back to Home',
            'btn_back_completed' => '← Back to Completed Sessions',
            'btn_back_reviews' => '← Back to Reviews',

            // Submit / Edit Feedback
            'sub_fb_title' => 'Submit Session Feedback & Star Rating',
            'sub_fb_sub' => 'Rate your completed facility booking experience to help improve campus spaces.',
            'edit_fb_title' => 'Edit Session Feedback & Star Rating',
            'lbl_star_rating' => 'Facility Star Rating *',
            'lbl_review_comment' => 'Review Comment *',
            'btn_submit_fb_action' => 'Submit Feedback',
            'btn_save_review' => 'Save Updated Review',
            'btn_cancel' => 'Cancel',

            // Footer
            'footer_maint' => 'Facility Maintenance & Damage Reporting',
            'footer_fb' => 'User Feedback & Reviews',
        ],
        'zh' => [
            // Brand & Nav
            'brand_title' => 'UE 校园设施预订系统',
            'nav_facilities' => '设施列表',
            'nav_my_bookings' => '我的预订',
            'nav_equipment' => '器材管理',
            'nav_maintenance' => '维修管理',
            'nav_feedback' => '评价反馈',
            'theme_toggle' => '切换浅色/深色主题',
            'lang_label' => '语言',

            // Home Page
            'hero_badge' => 'TAR UMT 校园设施集中预订平台',
            'hero_title' => 'UE 校园设施预订系统',
            'hero_subtitle' => '实时预订体育场馆与学术讨论室。到场时使用动态二维码快速核验签到，或提交设施报修与使用评价，共同维护优质校园环境。',
            'btn_browse_facilities' => '浏览可用设施 ↓',
            'btn_my_reservations' => '我的预订与扫码签到 ↓',
            'quick_actions' => '快捷操作',
            'btn_report_defect' => '+ 报告设施故障',
            'btn_rate_completed' => '+ 评价已完成时段',
            'btn_view_reviews' => '★ 查看设施评价',

            // Home Reservations & QR
            'sec_reservations' => '我的当前与即将开始的预订',
            'sec_reservations_sub' => '在场地入口使用动态二维码签到，或对已完成的场次进行评分。',
            'lbl_booking' => '预订编号',
            'lbl_location' => '地点：',
            'lbl_date' => '日期：',
            'lbl_time' => '时间：',
            'lbl_purpose' => '用途：',
            'lbl_today' => '（今天）',
            'status_confirmed' => '已确认',
            'status_checked_in' => '已签到',
            'status_completed' => '已完成',
            'status_no_show' => '爽约',
            'msg_checked_in_at' => '签到已核验：签到时间',
            'msg_expired_noshow' => '已过期爽约：已超过签到时间窗口（15分钟限制），场地已释放。',
            'btn_qr_checkin' => '扫码签到',
            'btn_view_qr' => '查看通行证 / 二维码',
            'btn_rate_review' => '评分与评价',
            'btn_report_short' => '报修',
            'no_bookings_msg' => '您目前没有进行中的场地预订。',
            'btn_book_now' => '立即预订校园设施',

            // Auto-Release Engine
            'uf_badge' => '特色功能演示（学生2：Tie Sing Wee）',
            'uf_title' => '15分钟爽约自动释放引擎',
            'uf_desc' => '防止占用场地与资源闲置。若学生未在预订开始时间后的 15 分钟内完成扫码签到，系统将自动把该预订标记为“爽约”并释放该时段供其他学生预订。',
            'btn_seed_overdue' => '+ 生成超时测试时段',
            'btn_run_autorelease' => '⚡ 立即运行自动释放引擎',

            // Home Facilities Directory
            'sec_facilities' => '浏览校园设施',
            'sec_facilities_sub' => '探索体育场馆与学术讨论室，预订前可查看已验证评分与实时可用状态。',
            'filter_all' => '全部设施',
            'filter_sports' => '体育设施',
            'filter_academic' => '学术设施',
            'search_facility_ph' => '搜索场地名称或楼栋...',
            'btn_search' => '搜索',
            'status_available' => '可用',
            'status_maintenance' => '维修中',
            'lbl_capacity' => '容量：',
            'lbl_pax' => '人',
            'lbl_reviews_count' => '条评价',
            'btn_quick_book' => '快速预订',
            'btn_locked_maint' => '维修锁定中',
            'btn_reviews' => '评价',
            'btn_report' => '报修',

            // Home Service Tiles
            'srv_defect_title' => '报告设施故障',
            'srv_defect_desc' => '发现设备损坏、照明故障或电路问题？提交带有分类筛选与实时维修跟踪的报修单。',
            'srv_defect_btn' => '提交报修单 →',
            'srv_rate_title' => '场次评分与评价',
            'srv_rate_desc' => '已完成您的预订时段？分享场地环境与设备状况反馈，帮助其他同学和校园管理团队。',
            'srv_rate_btn' => '提交场次评价 →',
            'srv_logs_title' => '设施工单与审核',
            'srv_logs_desc' => '进入维修工单日志查看维修进度，或查看管理员官方回复及评价审核记录。',
            'srv_ticket_log' => '工单日志',
            'srv_all_reviews' => '全部评价',

            // Maintenance Pages
            'maint_title' => '维修工单记录与工作台',
            'maint_sub' => '查看已提交的设施故障报修，按紧急程度或维修状态筛选，并跟踪技术员指派进度。',
            'btn_log_ticket' => '+ 提交新报修单',
            'kpi_active' => '当前活跃工单',
            'kpi_locked' => '严重/锁定场地',
            'kpi_progress' => '维修进行中',
            'kpi_resolved' => '本月已解决',
            'btn_apply_filter' => '应用筛选',
            'col_ticket_id' => '工单编号',
            'col_facility' => '设施场地',
            'col_category' => '故障类别',
            'col_urgency' => '紧急程度',
            'col_tech' => '指派技术员',
            'col_status' => '状态',
            'col_reported' => '提交时间',
            'col_action' => '操作',
            'btn_manage_update' => '管理 / 更新',
            'btn_view_details' => '查看详情',

            // Report Defect
            'report_title' => '报告设施故障 / 损坏',
            'report_sub' => '提交设备损坏、电路故障或安全隐患的维修工单。',
            'lbl_affected_facility' => '受影响设施 *',
            'lbl_defect_category' => '故障类别 *',
            'lbl_severity' => '严重程度 *',
            'lbl_defect_desc' => '故障描述 *',
            'btn_submit_ticket' => '提交报修单',

            // Update Ticket
            'update_title' => '更新维修状态、指派技术员及关闭/删除工单',
            'btn_back_ticket_log' => '← 返回工单列表',
            'btn_save_repair' => '保存维修更新',

            // Feedback Pages
            'fb_title' => '设施评价与平均评分汇总',
            'fb_sub' => '浏览已验证的学生评价，或编辑您自己提交的反馈。',
            'btn_submit_feedback' => '+ 提交评价',
            'btn_admin_mod' => '管理员审核 →',
            'avg_rating_label' => '设施平均评分',
            'your_review_tag' => '（我的评价）',
            'btn_edit_my_review' => '编辑我的评价',
            'btn_moderate_reply' => '审核 / 回复',

            // Completed Sessions
            'comp_title' => '已完成的预订时段',
            'comp_sub' => '选择以下已完成的场地预订以提交或更新您的评价。',
            'comp_empty' => '您目前还没有已完成的预订时段。',
            'comp_browse' => '浏览校园设施',
            'comp_reviewed' => '已评价',
            'btn_view_all_reviews' => '查看所有设施评价',
            'btn_back_home' => '← 返回首页',
            'btn_back_completed' => '← 返回已完成时段',
            'btn_back_reviews' => '← 返回评价列表',

            // Submit / Edit Feedback
            'sub_fb_title' => '提交场次评价与星级评分',
            'sub_fb_sub' => '评价您已完成的场地使用体验，帮助我们持续改进校园空间。',
            'edit_fb_title' => '编辑场次评价与星级评分',
            'lbl_star_rating' => '设施星级评分 *',
            'lbl_review_comment' => '评价内容 *',
            'btn_submit_fb_action' => '提交评价',
            'btn_save_review' => '保存修改',
            'btn_cancel' => '取消',

            // Footer
            'footer_maint' => '设施维修与故障报修',
            'footer_fb' => '用户反馈与评价',
        ],
        'ms' => [
            // Brand & Nav
            'brand_title' => 'Sistem Tempahan Fasiliti Kampus UE',
            'nav_facilities' => 'Fasiliti',
            'nav_my_bookings' => 'Tempahan Saya',
            'nav_equipment' => 'Peralatan',
            'nav_maintenance' => 'Penyelenggaraan',
            'nav_feedback' => 'Maklum Balas',
            'theme_toggle' => 'Tukar Tema Cerah/Gelap',
            'lang_label' => 'Bahasa',

            // Home Page
            'hero_badge' => 'Portal Tempahan Fasiliti Kampus Berpusat TAR UMT',
            'hero_title' => 'Sistem Tempahan Fasiliti Kampus UE',
            'hero_subtitle' => 'Tempah gelanggang sukan dan bilik perbincangan akademik secara masa nyata. Gunakan kod QR dinamik anda untuk pengesahan masuk yang pantas, atau laporkan kerosakan dan berikan maklum balas.',
            'btn_browse_facilities' => 'Layari Fasiliti Tersedia ↓',
            'btn_my_reservations' => 'Tempahan Saya & Daftar Masuk QR ↓',
            'quick_actions' => 'Tindakan Pantas',
            'btn_report_defect' => '+ Lapor Kerosakan Fasiliti',
            'btn_rate_completed' => '+ Nilai Sesi Selesai',
            'btn_view_reviews' => '★ Lihat Ulasan Fasiliti',

            // Home Reservations & QR
            'sec_reservations' => 'Tempahan Aktif & Akan Datang Saya',
            'sec_reservations_sub' => 'Daftar masuk di pintu masuk lokasi menggunakan kod QR dinamik, atau nilai sesi yang telah selesai.',
            'lbl_booking' => 'Tempahan',
            'lbl_location' => 'Lokasi:',
            'lbl_date' => 'Tarikh:',
            'lbl_time' => 'Masa:',
            'lbl_purpose' => 'Tujuan:',
            'lbl_today' => '(Hari Ini)',
            'status_confirmed' => 'Disahkan',
            'status_checked_in' => 'Daftar Masuk',
            'status_completed' => 'Selesai',
            'status_no_show' => 'Tidak Hadir',
            'msg_checked_in_at' => 'Kehadiran Disahkan: Daftar masuk pada',
            'msg_expired_noshow' => 'Tamat Tempoh: Had masa daftar masuk (15 minit) telah berlalu. Fasiliti dilepaskan.',
            'btn_qr_checkin' => 'Daftar Masuk QR',
            'btn_view_qr' => 'Lihat Pas / QR',
            'btn_rate_review' => 'Nilai & Ulas',
            'btn_report_short' => 'Lapor Kerosakan',
            'no_bookings_msg' => 'Anda tiada tempahan fasiliti yang aktif buat masa ini.',
            'btn_book_now' => 'Tempah Fasiliti Kampus Sekarang',

            // Auto-Release Engine
            'uf_badge' => 'Demonstrasi Ciri Unik (Pelajar 2: Tie Sing Wee)',
            'uf_title' => 'Enjin Pelepasan Automatik 15 Minit (Tidak Hadir)',
            'uf_desc' => 'Mengelakkan penahanan lokasi dan gelanggang terbiar. Jika pelajar tidak mendaftar masuk QR dalam tempoh 15 minit selepas waktu mula sesi, enjin akan membatalkan tempahan sebagai Tidak Hadir dan membuka semula slot untuk pelajar lain.',
            'btn_seed_overdue' => '+ Cipta Slot Ujian Lewat',
            'btn_run_autorelease' => '⚡ Jalankan Enjin Pelepasan Automatik',

            // Home Facilities Directory
            'sec_facilities' => 'Layari Fasiliti Kampus',
            'sec_facilities_sub' => 'Terokai gelanggang sukan dan bilik perbincangan akademik. Semak penilaian yang disahkan dan ketersediaan sebelum menempah.',
            'filter_all' => 'Semua Fasiliti',
            'filter_sports' => 'Fasiliti Sukan',
            'filter_academic' => 'Fasiliti Akademik',
            'search_facility_ph' => 'Cari nama lokasi atau blok...',
            'btn_search' => 'Cari',
            'status_available' => 'Tersedia',
            'status_maintenance' => 'Dalam Penyelenggaraan',
            'lbl_capacity' => 'Kapasiti:',
            'lbl_pax' => 'orang',
            'lbl_reviews_count' => 'ulasan',
            'btn_quick_book' => 'Tempah Pantas',
            'btn_locked_maint' => 'Dikunci untuk Penyelenggaraan',
            'btn_reviews' => 'Ulasan',
            'btn_report' => 'Lapor',

            // Home Service Tiles
            'srv_defect_title' => 'Lapor Kerosakan Fasiliti',
            'srv_defect_desc' => 'Menjumpai peralatan rosak, lampu bermasalah, atau isu elektrik? Daftar tiket penyelenggaraan dengan penapisan kategori dan penjejakan juruteknik masa nyata.',
            'srv_defect_btn' => 'Daftar Tiket Kerosakan →',
            'srv_rate_title' => 'Nilai & Ulas Sesi',
            'srv_rate_desc' => 'Sudah menamatkan sesi tempahan anda? Kongsi maklum balas tentang kualiti lokasi dan keadaan peralatan untuk membantu pelajar lain dan pihak pengurusan kampus.',
            'srv_rate_btn' => 'Hantar Ulasan Sesi →',
            'srv_logs_title' => 'Log Fasiliti & Moderasi',
            'srv_logs_desc' => 'Akses Log Tiket Penyelenggaraan untuk memantau kemajuan pembaikan, atau semak balasan rasmi pentadbir dan rekod moderasi ulasan.',
            'srv_ticket_log' => 'Log Tiket',
            'srv_all_reviews' => 'Semua Ulasan',

            // Maintenance Pages
            'maint_title' => 'Log Tiket Penyelenggaraan & Papan Pemuka Kerja',
            'maint_sub' => 'Pantau laporan kerosakan fasiliti, tapis mengikut tahap kecemasan atau status pembaikan, dan jejak tugasan juruteknik.',
            'btn_log_ticket' => '+ Daftar Tiket Kerosakan Baharu',
            'kpi_active' => 'Jumlah Tiket Aktif',
            'kpi_locked' => 'Lokasi Kritikal / Dikunci',
            'kpi_progress' => 'Pembaikan Sedang Dijalankan',
            'kpi_resolved' => 'Selesai Bulan Ini',
            'btn_apply_filter' => 'Tapis',
            'col_ticket_id' => 'ID Tiket',
            'col_facility' => 'Fasiliti',
            'col_category' => 'Kategori Kerosakan',
            'col_urgency' => 'Kecemasan',
            'col_tech' => 'Juruteknik Ditugaskan',
            'col_status' => 'Status',
            'col_reported' => 'Tarikh Lapor',
            'col_action' => 'Tindakan',
            'btn_manage_update' => 'Urus / Kemas Kini',
            'btn_view_details' => 'Lihat Butiran',

            // Report Defect
            'report_title' => 'Lapor Kerosakan / Kecacatan Fasiliti',
            'report_sub' => 'Hantar tiket penyelenggaraan untuk peralatan rosak, kerosakan elektrik, atau bahaya keselamatan.',
            'lbl_affected_facility' => 'Fasiliti Terlibat *',
            'lbl_defect_category' => 'Kategori Kerosakan *',
            'lbl_severity' => 'Tahap Keterukan *',
            'lbl_defect_desc' => 'Penerangan Kerosakan *',
            'btn_submit_ticket' => 'Hantar Tiket Kerosakan',

            // Update Ticket
            'update_title' => 'Kemas Kini Status Pembaikan, Tugaskan Juruteknik & Tutup/Padam Tiket',
            'btn_back_ticket_log' => '← Kembali ke Log Tiket',
            'btn_save_repair' => 'Simpan Kemas Kini Pembaikan',

            // Feedback Pages
            'fb_title' => 'Ulasan Fasiliti & Ringkasan Penilaian Purata',
            'fb_sub' => 'Layari ulasan pelajar yang disahkan dan sunting maklum balas anda sendiri.',
            'btn_submit_feedback' => '+ Hantar Maklum Balas',
            'btn_admin_mod' => 'Moderasi Admin →',
            'avg_rating_label' => 'Purata Penilaian Fasiliti',
            'your_review_tag' => '(Ulasan Anda)',
            'btn_edit_my_review' => 'Sunting Ulasan Saya',
            'btn_moderate_reply' => 'Moderasi / Balas',

            // Completed Sessions
            'comp_title' => 'Sesi Selesai',
            'comp_sub' => 'Pilih tempahan fasiliti yang telah selesai di bawah untuk menghantar atau mengemas kini ulasan anda.',
            'comp_empty' => 'Anda belum mempunyai sebarang sesi tempahan yang selesai.',
            'comp_browse' => 'Layari Fasiliti Kampus',
            'comp_reviewed' => 'Telah Diulas',
            'btn_view_all_reviews' => 'Lihat Semua Ulasan Fasiliti',
            'btn_back_home' => '← Kembali ke Laman Utama',
            'btn_back_completed' => '← Kembali ke Sesi Selesai',
            'btn_back_reviews' => '← Kembali ke Ulasan',

            // Submit / Edit Feedback
            'sub_fb_title' => 'Hantar Maklum Balas Sesi & Penilaian Bintang',
            'sub_fb_sub' => 'Nilai pengalaman tempahan fasiliti anda untuk membantu menambah baik ruang kampus.',
            'edit_fb_title' => 'Sunting Maklum Balas Sesi & Penilaian Bintang',
            'lbl_star_rating' => 'Penilaian Bintang Fasiliti *',
            'lbl_review_comment' => 'Ulasan *',
            'btn_submit_fb_action' => 'Hantar Maklum Balas',
            'btn_save_review' => 'Simpan Ulasan Dikemas Kini',
            'btn_cancel' => 'Batal',

            // Footer
            'footer_maint' => 'Penyelenggaraan & Laporan Kerosakan',
            'footer_fb' => 'Maklum Balas & Ulasan Pengguna',
        ],
    ];

    public static function getLang(): string {
        if (self::$currentLang !== null) {
            return self::$currentLang;
        }

        // 1. Explicit GET switch (?lang=en|zh|ms)
        if (isset($_GET['lang']) && in_array($_GET['lang'], self::$supported, true)) {
            $lang = $_GET['lang'];
            $_SESSION['lang'] = $lang;
            if (!headers_sent()) {
                setcookie('ue_lang', $lang, time() + (86400 * 30), '/');
            }
            self::$currentLang = $lang;
            return $lang;
        }

        // 2. Saved Session or Cookie preference
        if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], self::$supported, true)) {
            self::$currentLang = $_SESSION['lang'];
            return self::$currentLang;
        }
        if (!empty($_COOKIE['ue_lang']) && in_array($_COOKIE['ue_lang'], self::$supported, true)) {
            $_SESSION['lang'] = $_COOKIE['ue_lang'];
            self::$currentLang = $_COOKIE['ue_lang'];
            return self::$currentLang;
        }

        // 3. Auto-detect from device/browser Accept-Language header
        $detected = self::detectBrowserLanguage();
        $_SESSION['lang'] = $detected;
        self::$currentLang = $detected;
        return $detected;
    }

    private static function detectBrowserLanguage(): string {
        $header = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        if ($header === '') {
            return 'en';
        }
        $parts = explode(',', $header);
        foreach ($parts as $part) {
            $code = trim(explode(';', $part)[0]);
            if (str_starts_with($code, 'zh')) {
                return 'zh';
            }
            if (str_starts_with($code, 'ms')) {
                return 'ms';
            }
            if (str_starts_with($code, 'en')) {
                return 'en';
            }
        }
        return 'en';
    }

    public static function get(string $key): string {
        $lang = self::getLang();
        return self::$translations[$lang][$key] ?? self::$translations['en'][$key] ?? $key;
    }
}

function t(string $key): string {
    return I18n::get($key);
}
