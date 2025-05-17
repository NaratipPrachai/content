<?php
// หน้าแดชบอร์ดสำหรับผู้ดูแลระบบ
session_start();
include 'db.php';

// ตรวจสอบผู้ใช้ต้องเป็น admin เท่านั้น
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// ตรวจสอบหน้าที่ต้องการแสดง
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// นับจำนวนข้อมูลในระบบ
$count_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$count_departments = $conn->query("SELECT COUNT(*) as total FROM departments")->fetch_assoc()['total'];
$count_subjects = $conn->query("SELECT COUNT(*) as total FROM subjects")->fetch_assoc()['total'];
$count_media = $conn->query("SELECT COUNT(*) as total FROM media_files")->fetch_assoc()['total'];
$count_permissions = $conn->query("SELECT COUNT(*) as total FROM user_subjects")->fetch_assoc()['total'];

// สื่อที่เพิ่มล่าสุด 5 รายการ
$recent_media = $conn->query("SELECT m.*, s.name as subject_name, d.name as department_name 
                              FROM media_files m 
                              JOIN subjects s ON m.subject_id = s.id
                              JOIN departments d ON s.department_id = d.id
                              ORDER BY m.created_at DESC LIMIT 5");

// ผู้ใช้ที่เพิ่มล่าสุด 5 คน
$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html>
<head>
    <title>หน้าควบคุม - ระบบสื่อการเรียนรู้</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include 'admin-sidebar.php'; ?>

        <div class="flex-1">
            <!-- Navbar -->
            <?php include 'admin-navbar.php'; ?>

            <!-- Main Content -->
            <div class="p-6">
                <?php if($page == 'banners'): ?>
                    <?php include 'manage_banners.php'; ?>
                <?php else: ?>
                    <h2 class="text-2xl font-bold mb-6">แดชบอร์ด</h2>
                    
                    <!-- Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                        <div class="bg-white rounded-lg shadow-md p-6 flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                <i class="fas fa-users text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">ผู้ใช้ทั้งหมด</p>
                                <p class="text-2xl font-bold"><?php echo $count_users; ?></p>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-md p-6 flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                <i class="fas fa-university text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">สาขาวิชา</p>
                                <p class="text-2xl font-bold"><?php echo $count_departments; ?></p>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-md p-6 flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                <i class="fas fa-book text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">รายวิชาทั้งหมด</p>
                                <p class="text-2xl font-bold"><?php echo $count_subjects; ?></p>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-md p-6 flex items-center">
                            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                                <i class="fas fa-photo-video text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">สื่อทั้งหมด</p>
                                <p class="text-2xl font-bold"><?php echo $count_media; ?></p>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-md p-6 flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                <i class="fas fa-lock text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">การกำหนดสิทธิ์</p>
                                <p class="text-2xl font-bold"><?php echo $count_permissions; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- สื่อที่เพิ่มล่าสุด -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-bold mb-4">สื่อที่เพิ่มล่าสุด</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อสื่อ</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วิชา/สาขา</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ดู</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php while($media = $recent_media->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($media['title']); ?></td>
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <?php 
                                                switch($media['file_type']) {
                                                    case 'video': echo '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">วิดีโอ</span>'; break;
                                                    case 'pdf': echo '<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">PDF</span>'; break;
                                                    case 'word': echo '<span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs">Word</span>'; break;
                                                    case 'image': echo '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">รูปภาพ</span>'; break;
                                                    default: echo '<span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">อื่นๆ</span>'; break;
                                                }
                                                ?>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <div class="flex flex-col">
                                                    <span><?php echo htmlspecialchars($media['subject_name']); ?></span>
                                                    <span class="text-xs text-gray-500"><?php echo htmlspecialchars($media['department_name']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <a href="view_media.php?id=<?php echo $media['id']; ?>" class="text-blue-500 hover:text-blue-700">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if($recent_media->num_rows == 0): ?>
                                        <tr>
                                            <td colspan="4" class="px-4 py-2 text-center text-gray-500">ไม่มีสื่อในระบบ</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 text-right">
                                <a href="admin-media.php" class="text-blue-500 hover:text-blue-700">ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i></a>
                            </div>
                        </div>
                        
                        <!-- ผู้ใช้ที่เพิ่มล่าสุด -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-bold mb-4">ผู้ใช้ที่เพิ่มล่าสุด</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อผู้ใช้</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สิทธิ์</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่สร้าง</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php while($user = $recent_users->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <?php if($user['role'] == 'admin'): ?>
                                                    <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs">ผู้ดูแลระบบ</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">ผู้ใช้งาน</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if($recent_users->num_rows == 0): ?>
                                        <tr>
                                            <td colspan="3" class="px-4 py-2 text-center text-gray-500">ไม่มีผู้ใช้ในระบบ</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 text-right">
                                <a href="admin-users.php" class="text-blue-500 hover:text-blue-700">ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- สถิติรายสาขา -->
                    <div class="mt-6">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-bold mb-4">สถิติตามสาขาวิชา</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลำดับ</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สาขาวิชา</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนรายวิชา</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนสื่อ</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php 
                                        $dept_stats = $conn->query("SELECT d.id, d.name, 
                                                                  (SELECT COUNT(*) FROM subjects WHERE department_id = d.id) as subject_count,
                                                                  (SELECT COUNT(*) FROM media_files m JOIN subjects s ON m.subject_id = s.id WHERE s.department_id = d.id) as media_count
                                                                  FROM departments d
                                                                  ORDER BY d.name");
                                        $i = 1;
                                        while($dept = $dept_stats->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td class="px-4 py-2 whitespace-nowrap"><?php echo $i++; ?></td>
                                            <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($dept['name']); ?></td>
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                                                    <?php echo $dept['subject_count']; ?> รายวิชา
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                                    <?php echo $dept['media_count']; ?> รายการ
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <a href="admin-subjects.php?department=<?php echo $dept['id']; ?>" class="text-blue-500 hover:text-blue-700">
                                                    <i class="fas fa-eye mr-1"></i> ดูรายวิชา
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if($dept_stats->num_rows == 0): ?>
                                        <tr>
                                            <td colspan="5" class="px-4 py-2 text-center text-gray-500">ไม่มีสาขาวิชาในระบบ</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 text-right">
                                <a href="admin-departments.php" class="text-blue-500 hover:text-blue-700">จัดการสาขาวิชา <i class="fas fa-arrow-right ml-1"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>