<?php
// Navbar สำหรับ admin
session_start();
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
?>
<nav class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="font-bold text-lg text-gray-800">ระบบสื่อการเรียนรู้ (Admin)</div>
    <div class="flex items-center space-x-4">
        <span class="text-gray-600">สวัสดี, <?php echo htmlspecialchars(
            $username); ?></span>
        <a href="../view_media.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">กลับหน้าหลัก</a>
    </div>
</nav> 