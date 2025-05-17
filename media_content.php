<?php
// ตรวจสอบการล็อกอิน
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$dept_id = isset($_GET['dept']) ? intval($_GET['dept']) : 0;
$subject_id = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// ใช้ $media_files ที่ถูกกรองสิทธิ์จาก dashboard.php
?>

<!-- รายการสื่อ -->
<div class="w-full md:w-3/4">
    <div class="media-content-area p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <?php if(!empty($search)): ?>
                <h2 class="text-xl font-bold flex items-center text-indigo-700">
                    <i class="fas fa-search mr-3"></i> ผลการค้นหา: <span class="ml-2 text-purple-600">"<?php echo htmlspecialchars($search); ?>"</span>
                </h2>
                <?php elseif($subject_id > 0): ?>
                <?php 
                $subject_info = $conn->query("SELECT name FROM subjects WHERE id = $subject_id")->fetch_assoc();
                $subject_name = $subject_info ? $subject_info['name'] : "";
                ?>
                <h2 class="text-xl font-bold flex items-center text-indigo-700">
                    <i class="fas fa-book mr-3"></i> สื่อในวิชา: <span class="ml-2 text-purple-600"><?php echo htmlspecialchars($subject_name); ?></span>
                </h2>
                <?php elseif($dept_id > 0): ?>
                <h2 class="text-xl font-bold flex items-center text-indigo-700">
                    <i class="fas fa-university mr-3"></i> สื่อทั้งหมดในสาขา: <span class="ml-2 text-purple-600"><?php echo htmlspecialchars($dept_name); ?></span>
                </h2>
                <?php else: ?>
                <h2 class="text-xl font-bold flex items-center text-indigo-700">
                    <i class="fas fa-photo-video mr-3"></i> สื่อทั้งหมด
                </h2>
                <?php endif; ?>
            </div>
            <?php if($role === 'admin'): ?>
            <a href="admin-media.php" class="btn-primary text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-plus mr-2"></i> เพิ่มสื่อใหม่
            </a>
            <?php endif; ?>
        </div>

        <div class="transition-all duration-300">
            <?php if(empty($media_files) || (is_object($media_files) && $media_files->num_rows === 0)): ?>
            <div class="no-media-container p-8 bg-white rounded-lg shadow-sm">
                <div class="text-center">
                    <div class="text-gray-400 text-5xl mb-4">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <p class="text-gray-500 text-lg">ไม่พบรายการสื่อการเรียนรู้</p>
                    <p class="text-gray-400 text-sm mt-2">
                        <?php if(!empty($search)): ?>
                        ลองค้นหาด้วยคำค้นอื่น หรือเลือกสาขาวิชาอื่น
                        <?php else: ?>
                        ยังไม่มีสื่อในหมวดหมู่นี้ หรือคุณอาจไม่มีสิทธิ์เข้าถึง
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php else: ?>
            <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                <?php if(is_object($media_files)): ?>
                    <?php while($media = $media_files->fetch_assoc()): ?>
                        <?php if(file_exists(__DIR__ . '/media_card_render.php')): ?>
                            <?php include 'media_card_render.php'; ?>
                        <?php else: ?>
                            <div class="bg-white rounded-lg shadow-md p-4">
                                <p class="text-red-500">ไม่พบไฟล์ media_card_render.php</p>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <?php foreach($media_files as $media): ?>
                        <?php if(file_exists(__DIR__ . '/media_card_render.php')): ?>
                            <?php include 'media_card_render.php'; ?>
                        <?php else: ?>
                            <div class="bg-white rounded-lg shadow-md p-4">
                                <p class="text-red-500">ไม่พบไฟล์ media_card_render.php</p>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.media-card').forEach(card => {
    card.addEventListener('click', function() {
        if(this.dataset.canAccess === '0') {
            Swal.fire({
                icon: 'error',
                title: 'ไม่มีสิทธิ์เข้าถึง',
                text: 'คุณไม่มีสิทธิ์เข้าถึงสื่อนี้',
                confirmButtonText: 'ตกลง'
            });
            return;
        }
        const url = this.getAttribute('data-detail-url');
        if(url) window.location.href = url;
    });
});
</script> 