<?php
// หน้าดูสื่อแบบเต็มหน้าจอ
session_start();
include 'db.php';

// ตรวจสอบการล็อกอิน
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$media_id = intval($_GET['id']);

// ดึงข้อมูลสื่อ
$stmt = $conn->prepare("SELECT m.*, s.name as subject_name, u.username as created_by_name FROM media_files m JOIN subjects s ON m.subject_id = s.id LEFT JOIN users u ON m.created_by = u.id WHERE m.id = ?");
$stmt->bind_param("i", $media_id);
$stmt->execute();
$media = $stmt->get_result()->fetch_assoc();

if(!$media) {
    header("Location: index.php");
    exit;
}

// ตรวจสอบสิทธิ์การเข้าถึงสื่อ
$can_access = false;
if($role == 'admin') {
    $can_access = true;
} else {
    // ตรวจสอบสิทธิ์จากทั้งตาราง user_subjects และ user_departments
    $check_sql = "SELECT 1 FROM subjects s
                LEFT JOIN user_subjects us ON s.id = us.subject_id
                LEFT JOIN user_departments ud ON s.department_id = ud.department_id
                WHERE s.id = {$media['subject_id']}
                AND (us.user_id = $user_id OR ud.user_id = $user_id)";
    $check_result = $conn->query($check_sql);
    $can_access = ($check_result->num_rows > 0);
}

if(!$can_access) {
    header("Location: dashboard.php");
    exit;
}

// ดึงสื่ออื่นๆ ในวิชาเดียวกัน (แนะนำสื่ออื่นๆ)
$related_media = $conn->query("SELECT id, title, file_type FROM media_files 
                              WHERE subject_id = {$media['subject_id']} 
                              AND id != $media_id 
                              ORDER BY title 
                              LIMIT 5");

// ดึงลิงก์ Google Drive ทั้งหมดพร้อม ep_name
$links = [];
$link_stmt = $conn->prepare("SELECT google_drive_file_id, ep_name FROM media_links WHERE media_id = ?");
$link_stmt->bind_param("i", $media_id);
$link_stmt->execute();
$link_result = $link_stmt->get_result();
while($row = $link_result->fetch_assoc()) {
    $links[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($media['title']); ?> - ระบบสื่อการเรียนรู้</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <?php include 'styles.php'; ?>
</head>
<body class="bg-gray-100 custom-scrollbar" oncontextmenu="return false;">
    <div class="wave-bg"></div>
    
    <div class="container mx-auto px-4 py-8">
        <?php include 'navbar.php'; ?>
        <?php include 'header_banner.php'; ?>
        
        <!-- ส่วนแสดงสื่อ -->
        <div class="w-full">
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="flex items-center mb-4">
                    <?php 
                    // แสดงไอคอนตามประเภทสื่อ
                    switch($media['file_type']) {
                        case 'video': echo '<i class="fas fa-video text-red-500 text-2xl mr-3"></i>'; break;
                        case 'pdf': echo '<i class="fas fa-file-pdf text-red-500 text-2xl mr-3"></i>'; break;
                        case 'word': echo '<i class="fas fa-file-word text-blue-500 text-2xl mr-3"></i>'; break;
                        case 'image': echo '<i class="fas fa-image text-green-500 text-2xl mr-3"></i>'; break;
                        default: echo '<i class="fas fa-file text-gray-500 text-2xl mr-3"></i>'; break;
                    }
                    ?>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold"><?php echo htmlspecialchars($media['title']); ?></h1>
                        <p class="text-gray-600">
                            <i class="fas fa-book mr-1"></i> <?php echo htmlspecialchars($media['subject_name']); ?> |
                            <i class="fas fa-university mr-1"></i> <?php echo htmlspecialchars($media['department_name']); ?>
                        </p>
                    </div>
                </div>

                <?php if(!empty($media['document_references'])): ?>
                <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                        <i class="fas fa-bookmark text-blue-500 mr-2"></i>เอกสารอ้างอิง
                    </h3>
                    <div class="text-gray-700 whitespace-pre-line">
                        <?php echo nl2br(htmlspecialchars($media['document_references'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($media['illustrations'])): ?>
                <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                        <i class="fas fa-images text-green-500 mr-2"></i>ภาพประกอบ
                    </h3>
                    <div class="text-gray-700 whitespace-pre-line">
                        <?php
                        if(strpos($media['illustrations'], 'uploads/illustrations/') === 0) {
                            echo '<img src="' . htmlspecialchars($media['illustrations']) . '" class="max-w-full h-auto rounded shadow">';
                        } else {
                            echo nl2br(htmlspecialchars($media['illustrations']));
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="media-container w-full rounded overflow-hidden mb-4">
                    <?php
                    // ตรวจสอบว่าเป็นรูปภาพที่อัพโหลดหรือไม่
                    if($media['file_type'] === 'image' && strpos($media['google_drive_file_id'], 'uploads/images/') === 0) {
                        // แสดงรูปภาพที่อัพโหลด
                        echo '<div class="flex justify-center items-center h-full bg-gray-100">';
                        echo '<img src="' . htmlspecialchars($media['google_drive_file_id']) . '" class="max-w-full max-h-[600px] mx-auto rounded" alt="' . htmlspecialchars($media['title']) . '">';
                        echo '</div>';
                    } else if($media['file_type'] === 'video' && strpos($media['google_drive_file_id'], 'uploads/videos/') === 0) {
                        // แสดงวิดีโอที่อัพโหลดในเซิร์ฟเวอร์
                        echo '<div class="relative w-full" style="padding-top:56.25%;">';
                        echo '<video class="absolute top-0 left-0 w-full h-full rounded" controls>';
                        echo '<source src="' . htmlspecialchars($media['google_drive_file_id']) . '" type="video/mp4">';
                        echo 'เบราว์เซอร์ของคุณไม่รองรับการเล่นวิดีโอ';
                        echo '</video>';
                        echo '</div>';
                    } else if($media['file_type'] === 'video') {
                        // กรณีวิดีโอที่ยังเป็น Google Drive (ยังคงใช้ iframe)
                        $url = $media['google_drive_file_id'];
                        if (preg_match('/\/d\/([^\/]+)/', $url, $matches)) {
                            $file_id = $matches[1];
                        } else {
                            $file_id = $url;
                        }
                        $preview_url = "https://drive.google.com/file/d/" . $file_id . "/preview";
                        echo '<div class="relative w-full" style="padding-top:56.25%;">';
                        echo '<iframe src="' . $preview_url . '" class="absolute top-0 left-0 w-full h-full border-0 rounded" allowfullscreen></iframe>';
                        echo '</div>';
                    } else {
                        // กรณีไม่มี drive_links ให้ใช้ google_drive_file_id เดิม
                        $url = $media['google_drive_file_id'];
                        if (preg_match('/\/d\/([^\/]+)/', $url, $matches)) {
                            $file_id = $matches[1];
                        } else {
                            $file_id = $url;
                        }
                        $preview_url = "https://drive.google.com/file/d/" . $file_id . "/preview";
                        switch($media['file_type']) {
                            case 'pdf':
                                echo '<iframe src="' . $preview_url . '" width="100%" height="600" class="border-0 rounded" allowfullscreen></iframe>';
                                break;
                            case 'word':
                                echo '<iframe src="https://docs.google.com/document/d/' . $file_id . '/preview" width="100%" height="600" class="border-0 rounded" allowfullscreen></iframe>';
                                break;
                            case 'image':
                                echo '<div class="flex justify-center items-center h-full bg-gray-100">';
                                echo '<img src="https://drive.google.com/uc?export=view&id=' . $file_id . '" class="max-w-full max-h-[600px] mx-auto rounded" alt="' . htmlspecialchars($media['title']) . '">';
                                echo '</div>';
                                break;
                            default:
                                echo '<iframe src="' . $preview_url . '" width="100%" height="600" class="border-0 rounded" allowfullscreen></iframe>';
                                break;
                        }
                    }
                    ?>
                </div>
            </div>
            
            <!-- ส่วนแสดงผลลิงก์ -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">ลิงก์ที่เกี่ยวข้อง</h3>
                <?php if(!empty($links)): ?>
                    <div class="space-y-3">
                        <?php foreach($links as $index => $link): ?>
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition duration-300">
                            <div class="flex-shrink-0">
                                <?php if($media['file_type'] === 'video'): ?>
                                    <i class="fas fa-video text-red-500 text-xl"></i>
                                <?php elseif($media['file_type'] === 'pdf'): ?>
                                    <i class="fas fa-file-pdf text-blue-500 text-xl"></i>
                                <?php elseif($media['file_type'] === 'word'): ?>
                                    <i class="fas fa-file-word text-indigo-500 text-xl"></i>
                                <?php elseif($media['file_type'] === 'image'): ?>
                                    <i class="fas fa-image text-green-500 text-xl"></i>
                                <?php else: ?>
                                    <i class="fas fa-file text-gray-500 text-xl"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow">
                                <span class="font-bold text-blue-700 mr-2"><?php echo htmlspecialchars($link['ep_name']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 italic">ไม่มีลิงก์ที่เกี่ยวข้อง</p>
                <?php endif; ?>
            </div>
            
            <?php if($related_media->num_rows > 0): ?>
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="text-lg font-bold mb-4">สื่ออื่นๆ ในวิชา <?php echo htmlspecialchars($media['subject_name']); ?></h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php while($item = $related_media->fetch_assoc()): ?>
                    <a href="view_media.php?id=<?php echo $item['id']; ?>" class="block p-3 rounded border hover:bg-gray-50 transition-colors related-media">
                        <?php 
                        // แสดงไอคอนตามประเภทสื่อ
                        switch($item['file_type']) {
                            case 'video': echo '<i class="fas fa-video text-red-500 mr-2"></i>'; break;
                            case 'pdf': echo '<i class="fas fa-file-pdf text-red-500 mr-2"></i>'; break;
                            case 'word': echo '<i class="fas fa-file-word text-blue-500 mr-2"></i>'; break;
                            case 'image': echo '<i class="fas fa-image text-green-500 mr-2"></i>'; break;
                            default: echo '<i class="fas fa-file text-gray-500 mr-2"></i>'; break;
                        }
                        echo htmlspecialchars($item['title']); 
                        ?>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    <?php include 'scripts.php'; ?>
</body>
</html>