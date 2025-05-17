<?php
// Sidebar สำหรับ admin
?>
<aside class="w-64 h-screen bg-gray-800 text-white flex flex-col">
    <div class="p-6 font-bold text-xl border-b border-gray-700">Admin Panel</div>
    <nav class="flex-1 p-4">
        <ul class="space-y-2">
            <li><a href="index.php" class="block px-4 py-2 rounded hover:bg-gray-700">Dashboard</a></li>
            <li><a href="users.php" class="block px-4 py-2 rounded hover:bg-gray-700">จัดการผู้ใช้</a></li>
            <li><a href="subjects.php" class="block px-4 py-2 rounded hover:bg-gray-700">จัดการวิชา</a></li>
            <li><a href="media.php" class="block px-4 py-2 rounded hover:bg-gray-700">จัดการสื่อ</a></li>
        </ul>
    </nav>
    <div class="p-4 border-t border-gray-700">
        <a href="../logout.php" class="block px-4 py-2 rounded bg-red-500 hover:bg-red-600 text-center">ออกจากระบบ</a>
    </div>
</aside> 