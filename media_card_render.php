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

// ดึงรูปภาพที่เกี่ยวข้อง
$related_images = [];
if($media['file_type'] === 'image') {
    // ตรวจสอบว่าเป็นรูปภาพที่อัพโหลดหรือไม่
    if(strpos($media['google_drive_file_id'], 'uploads/images/') === 0) {
        $related_images[] = $media['google_drive_file_id'];
    } else {
        // ถ้าเป็น Google Drive ID
        $related_images[] = "https://drive.google.com/uc?export=view&id=" . $media['google_drive_file_id'];
    }
}

// ดึงรูปภาพประกอบ
if(!empty($media['illustrations'])) {
    if(strpos($media['illustrations'], 'uploads/illustrations/') === 0) {
        $related_images[] = $media['illustrations'];
    }
}

// ดึงรูปภาพตัวอย่าง
$example_sql = "SELECT image_path FROM media_examples WHERE media_id = {$media['id']}";
$example_result = $conn->query($example_sql);
while($example = $example_result->fetch_assoc()) {
    $related_images[] = $example['image_path'];
}
?>

<div class="media-card bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300 cursor-pointer overflow-hidden" 
     data-detail-url="<?php echo $detail_url; ?>"
     data-can-access="<?php echo $can_access ? '1' : '0'; ?>">
    
    <!-- ส่วนแสดงรูปภาพ -->
    <?php if(!empty($related_images)): ?>
    <div class="relative h-48 bg-gray-100">
        <img src="<?php echo htmlspecialchars($related_images[0]); ?>" 
             class="w-full h-full object-cover" 
             alt="<?php echo htmlspecialchars($media['title']); ?>">
        <?php if(count($related_images) > 1): ?>
        <div class="absolute bottom-2 right-2 bg-black bg-opacity-50 text-white px-2 py-1 rounded-full text-xs">
            <i class="fas fa-images mr-1"></i> <?php echo count($related_images); ?> รูป
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- ส่วนหัวการ์ด -->
    <div class="p-4 border-b border-gray-100">
        <div class="flex items-center justify-between mb-2">
            <span class="text-2xl"><?php echo $icon; ?></span>
            <div class="flex items-center space-x-2">
                <?php if($ep_count > 0): ?>
                <span class="px-2 py-1 bg-blue-100 text-blue-600 rounded-full text-xs">
                    <i class="fas fa-list-ol mr-1"></i> <?php echo $ep_count; ?> EP
                </span>
                <?php endif; ?>
                <span class="text-sm text-gray-500">
                    <?php echo ucfirst($media['file_type']); ?>
                </span>
            </div>
        </div>
        <h3 class="text-lg font-semibold text-gray-800 mb-1 line-clamp-2">
            <?php echo htmlspecialchars($media['title']); ?>
        </h3>
        <div class="flex flex-col space-y-1">
            <p class="text-sm text-gray-600">
                <i class="fas fa-book mr-1"></i> <?php echo htmlspecialchars($media['subject_name']); ?>
            </p>
            <?php if(isset($media['department_name'])): ?>
            <p class="text-sm text-gray-500">
                <i class="fas fa-university mr-1"></i> <?php echo htmlspecialchars($media['department_name']); ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- ส่วนท้ายการ์ด -->
    <div class="p-4 bg-gray-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-500">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    <?php echo date('d M Y', strtotime($media['created_at'])); ?>
                </span>
                <?php if(isset($media['created_by_name'])): ?>
                <span class="text-sm text-gray-500">
                    <i class="fas fa-user mr-1"></i>
                    <?php echo htmlspecialchars($media['created_by_name']); ?>
                </span>
                <?php endif; ?>
            </div>
            <a href="<?php echo $detail_url; ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                <i class="fas fa-eye mr-1"></i> ดูรายละเอียด
            </a>
        </div>
    </div>
</div> 