<?php
// ตรวจสอบว่ามีข้อมูลสื่อหรือไม่
if(!isset($media)) {
    return;
}

// กำหนด URL สำหรับดูรายละเอียด
$detail_url = "view_media.php?id=" . $media['id'];

// กำหนดไอคอนตามประเภทไฟล์
$file_type_icons = [
    'video' => '<i class="fas fa-video text-red-500"></i>',
    'pdf' => '<i class="fas fa-file-pdf text-red-500"></i>',
    'word' => '<i class="fas fa-file-word text-blue-500"></i>',
    'image' => '<i class="fas fa-image text-green-500"></i>',
    'powerpoint' => '<i class="fas fa-file-powerpoint text-orange-500"></i>',
    'excel' => '<i class="fas fa-file-excel text-green-500"></i>'
];

$icon = isset($file_type_icons[$media['file_type']]) ? $file_type_icons[$media['file_type']] : '<i class="fas fa-file text-gray-500"></i>';

// ตรวจสอบสิทธิ์การเข้าถึง
$can_access = true;
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $user_id = $_SESSION['user_id'];
    $check_sql = "SELECT 1 FROM subjects s
                LEFT JOIN user_subjects us ON s.id = us.subject_id
                LEFT JOIN user_departments ud ON s.department_id = ud.department_id
                WHERE s.id = {$media['subject_id']}
                AND (us.user_id = $user_id OR ud.user_id = $user_id)
                AND s.id NOT IN (
                    SELECT subject_id FROM subject_exclusions WHERE user_id = $user_id
                )";
    $check_result = $conn->query($check_sql);
    $can_access = ($check_result->num_rows > 0);
}

// ดึงข้อมูลจำนวน EP
$ep_count = 0;
if(isset($media['drive_links'])) {
    $ep_count = count(explode('|', $media['drive_links']));
}

// แสดงเฉพาะ thumbnail เป็นรูปภาพหลัก
$display_image = '';
$has_thumbnail = false;

// ตรวจสอบว่ามี thumbnail หรือไม่ (แสดงเฉพาะ thumbnail เท่านั้น)
if(!empty($media['thumbnail']) && file_exists(__DIR__ . '/' . $media['thumbnail'])) {
    $display_image = $media['thumbnail'];
    $has_thumbnail = true;
}
// ไม่ใช้รูปภาพเริ่มต้นอื่น ๆ - แสดงเฉพาะ thumbnail ตามที่ผู้ใช้ต้องการ
?>

<div class="media-card bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300 cursor-pointer overflow-hidden" 
     data-detail-url="<?php echo $detail_url; ?>"
     data-can-access="<?php echo $can_access ? '1' : '0'; ?>">
    
    <!-- ส่วนแสดง thumbnail -->
    <?php if(!empty($display_image)): ?>
    <div class="relative h-48 bg-gray-100">
        <img src="<?php echo htmlspecialchars($display_image); ?>" 
             class="w-full h-full object-cover" 
             alt="<?php echo htmlspecialchars($media['title']); ?>"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <!-- Fallback สำหรับกรณีรูปภาพ load ไม่ได้ -->
        <div class="hidden absolute inset-0 bg-gray-200 items-center justify-center">
            <div class="text-center text-gray-500">
                <i class="fas fa-image text-4xl mb-2"></i>
                <p class="text-sm">ไม่สามารถแสดงภาพได้</p>
            </div>
        </div>
        <div class="absolute top-2 left-2 bg-green-500 text-white px-2 py-1 rounded-full text-xs">
            <i class="fas fa-image mr-1"></i> มีปกคลิป
        </div>
    </div>
    <?php else: ?>
    <!-- Placeholder สำหรับกรณีไม่มี thumbnail - แสดงเฉพาะ icon กับข้อความ -->
    <div class="relative h-48 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
        <div class="text-center text-gray-500">
            <span class="text-4xl mb-2"><?php echo $icon; ?></span>
            <p class="text-sm font-medium"><?php echo ucfirst($media['file_type']); ?></p>
            <p class="text-xs text-gray-400 mt-1">ยังไม่มีปกคลิป</p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- ส่วนหัวการ์ด - เฉพาะชื่อเรื่อง -->
    <div class="p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-2 line-clamp-2">
            <?php echo htmlspecialchars($media['title']); ?>
        </h3>
        <a href="<?php echo $detail_url; ?>" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 text-sm font-medium">
            <i class="fas fa-eye mr-1"></i> ดูรายละเอียด
        </a>
    </div>
</div> 