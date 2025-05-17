<?php
require_once 'db.php'; // เชื่อมต่อฐานข้อมูล
if (!isset($_GET['id'])) {
    echo "ไม่พบข้อมูล";
    exit;
}
$media_id = intval($_GET['id']);

// ดึงข้อมูล media และ EP ทั้งหมดของสื่อนี้
$sql = "SELECT m.*, s.name as subject_name, ml.id as link_id, ml.google_drive_file_id, ml.ep_name
        FROM media_files m
        JOIN subjects s ON m.subject_id = s.id
        LEFT JOIN media_links ml ON m.id = ml.media_id
        WHERE m.id = $media_id";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    echo "ไม่พบข้อมูล";
    exit;
}
$media = $result->fetch_assoc();
$result->data_seek(0); // reset pointer
?>
<<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($media['title']); ?></title>
    <!-- Tailwind CSS v3 จาก CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Font: Prompt -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        // กำหนด config สำหรับ Tailwind CSS
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'prompt': ['Prompt', 'sans-serif'],
                    },
                    colors: {
                        'primary': {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                        },
                    },
                }
            }
        }
    </script>
    <style type="text/css">
        body {
            font-family: 'Prompt', sans-serif;
        }
    </style>
    <script>
        // ฟังก์ชันสำหรับคัดลอกลิงก์ไปยังคลิปบอร์ด
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // สร้าง notification แจ้งว่าคัดลอกแล้ว
                const notification = document.createElement('div');
                notification.className = 'fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-2 rounded-lg shadow-lg';
                notification.textContent = 'คัดลอกลิงก์แล้ว';
                document.body.appendChild(notification);
                
                // ให้ notification หายไปหลังจาก 2 วินาที
                setTimeout(() => {
                    notification.remove();
                }, 2000);
            });
        }
    </script>
</head>
<body class="bg-gray-50 font-prompt">
    <div class="container mx-auto p-4 sm:p-6 md:p-8">
        <!-- Header Section with Gradient Background -->
        <div class="bg-gradient-to-r from-primary-600 to-purple-600 rounded-xl p-6 mb-8 shadow-lg text-white">
            <h1 class="text-2xl sm:text-3xl font-bold mb-3">
                <?php echo htmlspecialchars($media['title']); ?>
            </h1>
            <div class="flex flex-wrap gap-2 mb-2">
                <span class="px-3 py-1 bg-white bg-opacity-20 rounded-full text-sm font-medium backdrop-blur-sm">
                    <i class="fas fa-book mr-1"></i> <?php echo htmlspecialchars($media['subject_name']); ?>
                </span>
                <?php if(isset($media['teacher_name'])): ?>
                <span class="px-3 py-1 bg-white bg-opacity-20 rounded-full text-sm font-medium backdrop-blur-sm">
                    <i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($media['teacher_name']); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="mb-8 rounded-xl bg-white shadow-md hover:shadow-xl transition-all duration-300 p-6 border border-gray-100">
                <!-- Episode Header -->
                <!-- Metadata and Tags -->
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                        <i class="fas fa-film mr-1"></i>
                        <?php echo htmlspecialchars($row['ep_name'] ?: 'ไม่มีชื่อ EP'); ?>
                    </span>
                    <?php if(isset($row['created_date'])): ?>
                    <span class="inline-flex items-center px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">
                        <i class="fas fa-calendar mr-1"></i>
                        <?php echo date('d M Y', strtotime($row['created_date'])); ?>
                    </span>
                    <?php endif; ?>
                    <?php if(isset($row['file_type'])): ?>
                    <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">
                        <i class="fas fa-file mr-1"></i>
                        <?php 
                            $file_type_icons = [
                                'video' => '<i class="fas fa-video"></i>',
                                'pdf' => '<i class="fas fa-file-pdf"></i>',
                                'word' => '<i class="fas fa-file-word"></i>',
                                'image' => '<i class="fas fa-image"></i>',
                                'audio' => '<i class="fas fa-music"></i>',
                                'powerpoint' => '<i class="fas fa-file-powerpoint"></i>',
                                'excel' => '<i class="fas fa-file-excel"></i>',
                            ];
                            $icon = isset($file_type_icons[$row['file_type']]) ? $file_type_icons[$row['file_type']] : '<i class="fas fa-file"></i>';
                            echo $icon . ' ' . ucfirst($row['file_type']);
                        ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <!-- Media Content -->
                <div class="mb-4">
                    <?php
                    $file_id = $row['google_drive_file_id'];
                    $preview_url = "https://drive.google.com/file/d/" . $file_id . "/preview";
                    switch($row['file_type']) {
                        case 'video':
                            echo '<div class="relative w-full mb-2 rounded-lg overflow-hidden shadow-md" style="padding-top:56.25%;">';
                            echo '<iframe src="' . $preview_url . '" class="absolute top-0 left-0 w-full h-full border-0" allowfullscreen></iframe>';
                            echo '</div>';
                            break;
                        case 'pdf':
                            echo '<iframe src="' . $preview_url . '" width="100%" height="500" class="border-0 rounded-lg mb-2 shadow-md" allowfullscreen></iframe>';
                            break;
                        case 'word':
                            echo '<iframe src="https://docs.google.com/document/d/' . $file_id . '/preview" width="100%" height="500" class="border-0 rounded-lg mb-2 shadow-md" allowfullscreen></iframe>';
                            break;
                        case 'image':
                            echo '<div class="flex justify-center items-center bg-gray-100 mb-2 rounded-lg p-4 shadow-md">';
                            echo '<img src="https://drive.google.com/uc?export=view&id=' . $file_id . '" class="max-w-full max-h-96 mx-auto rounded-lg" alt="">';
                            echo '</div>';
                            break;
                        default:
                            echo '<iframe src="' . $preview_url . '" width="100%" height="500" class="border-0 rounded-lg mb-2 shadow-md" allowfullscreen></iframe>';
                            break;
                    }
                    ?>
                </div>
                
                <!-- Google Drive Link and Download Options -->
                <?php if($row['google_drive_file_id']): ?>
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <a href="https://drive.google.com/file/d/<?php echo $row['google_drive_file_id']; ?>/view"
                           target="_blank"
                           class="inline-flex items-center px-4 py-2 bg-primary-100 text-primary-700 rounded-full text-sm font-medium hover:bg-primary-200 transition-colors duration-200 shadow-sm hover:shadow">
                           <i class="fab fa-google-drive mr-2"></i>
                           ดูใน Google Drive
                        </a>
                        <a href="https://drive.google.com/uc?export=download&id=<?php echo $row['google_drive_file_id']; ?>"
                           target="_blank"
                           class="inline-flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm font-medium hover:bg-green-200 transition-colors duration-200 shadow-sm hover:shadow">
                           <i class="fas fa-download mr-2"></i>
                           ดาวน์โหลด
                        </a>
                        <button onclick="copyToClipboard('https://drive.google.com/file/d/<?php echo $row['google_drive_file_id']; ?>/view')" 
                                class="inline-flex items-center px-4 py-2 bg-purple-100 text-purple-700 rounded-full text-sm font-medium hover:bg-purple-200 transition-colors duration-200 shadow-sm hover:shadow">
                            <i class="fas fa-share-alt mr-2"></i>
                            แชร์ลิงก์
                        </button>
                        <div class="flex items-center mt-2 w-full">
                            <span class="text-xs text-gray-500 break-all">
                                https://drive.google.com/file/d/<?php echo $row['google_drive_file_id']; ?>/view
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
        
        <!-- Back Button with Action Menu -->
        <div class="mt-8 mb-6 flex flex-wrap items-center gap-3">
            <a href="media_content.php" class="inline-flex items-center px-4 py-2 bg-primary-100 text-primary-700 rounded-full hover:bg-primary-200 transition-all duration-200 font-medium shadow-sm hover:shadow">
                <i class="fas fa-arrow-left mr-2"></i> กลับหน้ารายการสื่อ
            </a>
            
            <!-- Additional Action Buttons -->
            <div class="dropdown inline-block relative group">
                <button class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition-all duration-200 font-medium shadow-sm hover:shadow">
                    <i class="fas fa-ellipsis-h mr-2"></i> ตัวเลือกเพิ่มเติม
                </button>
                <div class="dropdown-menu absolute hidden pt-2 group-hover:block z-10" style="min-width: 200px;">
                    <div class="bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden">
                        <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 border-b border-gray-100">
                            <i class="fas fa-print mr-2"></i> พิมพ์หน้านี้
                        </a>
                        <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 border-b border-gray-100">
                            <i class="fas fa-star mr-2"></i> เพิ่มในรายการโปรด
                        </a>
                        <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-flag mr-2"></i> รายงานปัญหา
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>