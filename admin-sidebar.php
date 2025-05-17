<?php
// ตรวจสอบผู้ใช้ต้องเป็น admin เท่านั้น
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// ตรวจสอบหน้าปัจจุบัน
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="bg-gray-800 text-white w-64 min-h-screen p-4">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold">ระบบจัดการสื่อ</h2>
        <p class="text-gray-400 text-sm mt-1">ผู้ดูแลระบบ</p>
    </div>
    
    <nav>
        <ul>
            <li class="mb-2">
                <a href="admin-dashboard.php" class="block p-3 rounded <?php echo ($current_page == 'admin-dashboard.php') ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-tachometer-alt mr-2"></i> หน้าหลัก
                </a>
            </li>
            <li class="mb-2">
                <a href="admin-users.php" class="block p-3 rounded <?php echo ($current_page == 'admin-users.php') ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-users mr-2"></i> จัดการผู้ใช้
                </a>
            </li>
            <li class="mb-2">
                <a href="admin-departments.php" class="block p-3 rounded <?php echo ($current_page == 'admin-departments.php') ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-university mr-2"></i> จัดการสาขาวิชา
                </a>
            </li>
            <li class="mb-2">
                <a href="admin-subjects.php" class="block p-3 rounded <?php echo ($current_page == 'admin-subjects.php') ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-book mr-2"></i> จัดการรายวิชา
                </a>
            </li>
            <li class="mb-2">
                <a href="admin-media.php" class="block p-3 rounded <?php echo ($current_page == 'admin-media.php') ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-photo-video mr-2"></i> จัดการสื่อ
                </a>
            </li>
            <li class="mb-2">
                <a href="manage-department-permissions.php" class="block p-3 rounded <?php echo ($current_page == 'manage-department-permissions.php') ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-unlock-alt mr-2"></i> จัดการสิทธิ์ตามแผนก
                </a>
            </li>
            <li class="mb-2">
                <a href="admin-permissions.php" class="block p-3 rounded <?php echo ($current_page == 'admin-permissions.php') ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-lock mr-2"></i> จัดการสิทธิ์ทั่วไป
                </a>
            </li>
            <li class="mb-2">
                <a href="manage-cross-subjects.php" class="block p-3 rounded <?php echo ($current_page == 'manage-cross-subjects.php') ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-exchange-alt mr-2"></i> จัดการวิชาข้ามแผนก
                </a>
            </li>
            <li class="mb-2">
                <a href="manage_banners.php" class="block p-3 rounded <?php echo ($current_page == 'manage_banners.php') ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-images mr-2"></i> จัดการแบนเนอร์
                </a>
            </li>
            <li class="mt-8">
                <a href="dashboard.php" class="block p-3 rounded hover:bg-gray-700">
                    <i class="fas fa-desktop mr-2"></i> หน้าผู้ใช้
                </a>
            </li>
            <li class="mt-2">
                <a href="logout.php" class="block p-3 rounded hover:bg-gray-700 text-red-400">
                    <i class="fas fa-sign-out-alt mr-2"></i> ออกจากระบบ
                </a>
            </li>
        </ul>
    </nav>
</div>