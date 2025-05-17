<?php
// Dashboard หลักของ admin
include 'nav.php';
?>
<div class="flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 p-8 bg-gray-100 min-h-screen">
        <h1 class="text-2xl font-bold mb-6">แดชบอร์ดผู้ดูแลระบบ</h1>
        <div class="bg-white p-6 rounded shadow">
            <p>ยินดีต้อนรับสู่ระบบจัดการสื่อการเรียนรู้สำหรับผู้ดูแลระบบ</p>
            <ul class="list-disc ml-6 mt-4 text-gray-700">
                <li>จัดการผู้ใช้</li>
                <li>จัดการวิชา</li>
                <li>จัดการสื่อ</li>
                <li>ดูสถิติและภาพรวมระบบ</li>
            </ul>
        </div>
    </main>
</div> 