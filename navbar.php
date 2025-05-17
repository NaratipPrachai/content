<?php
// ตรวจสอบการล็อกอิน
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>

<!-- Navbar -->
<div class="navbar p-4 mb-8 flex justify-between items-center">
    <div class="flex items-center">
        <div class="mr-4 text-indigo-600">
            <i class="fas fa-book-reader text-3xl"></i>
        </div>
        <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600">สื่อ Content</h1>
    </div>
    <div class="flex items-center">
        <div class="mr-6 flex items-center">
            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 mr-2">
                <i class="fas fa-user"></i>
            </div>
            <span class="font-medium">สวัสดี, <?php echo !empty($_SESSION['name_1']) ? htmlspecialchars($_SESSION['name_1']) : '<span class="text-red-500">ไปเพิ่มชื่อเดี๋ยวนี้เลยนะ</span>'; ?> 
                <span class="text-xs text-gray-500">(<?php echo $role == 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้ใช้งาน'; ?>)</span>
            </span>
        </div>
        <?php if($role == 'admin'): ?>
        <a href="admin-dashboard.php" class="btn-primary text-white px-4 py-2 rounded-lg mr-2 transition duration-300 flex items-center">
            <i class="fas fa-cog mr-2"></i> จัดการระบบ
        </a>
        <?php endif; ?>
        <a href="logout.php" class="btn-danger text-white px-4 py-2 rounded-lg transition duration-300 flex items-center">
            <i class="fas fa-sign-out-alt mr-2"></i> ออกจากระบบ
        </a>
    </div>
</div> 