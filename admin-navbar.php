<?php
// ตรวจสอบผู้ใช้ต้องเป็น admin เท่านั้น
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
?>

<div class="bg-white shadow-md px-4 py-3">
    <div class="flex justify-between items-center">
        <div class="flex items-center">
            <h1 class="text-xl font-bold text-gray-800">ระบบจัดการสื่อการเรียนรู้</h1>
        </div>
        
        <div class="flex items-center">
            <div class="mr-4">
                <span class="text-gray-600 mr-1">ผู้ใช้:</span>
                <span class="font-medium"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=4F46E5&color=fff" class="w-8 h-8 rounded-full" alt="Profile">
        </div>
    </div>
</div>