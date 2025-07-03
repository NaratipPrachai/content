<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// หน้าจัดการการยกเว้นรายวิชาสำหรับผู้ใช้และสาขาวิชาที่กำหนด
session_start();
include 'db.php';

// ตรวจสอบผู้ใช้ต้องเป็น admin เท่านั้น
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// ตรวจสอบการส่งค่า user_id และ department_id
if(!isset($_GET['user_id']) || !isset($_GET['department_id'])) {
    header("Location: admin-permissions.php");
    exit;
}

$user_id = intval($_GET['user_id']);
$department_id = intval($_GET['department_id']);
$message = '';
$error = '';

// ตรวจสอบว่า user_id และ department_id นี้มีสิทธิ์ตามสาขาวิชาหรือไม่
$check_permission = $conn->query("SELECT ud.*, u.username, d.name as department_name 
                              FROM user_departments ud 
                              JOIN users u ON ud.user_id = u.id 
                              JOIN departments d ON ud.department_id = d.id 
                              WHERE ud.user_id = $user_id AND ud.department_id = $department_id");

if($check_permission->num_rows == 0) {
    header("Location: admin-permissions.php");
    exit;
}

$permission = $check_permission->fetch_assoc();

// เพิ่มวิชาที่ยกเว้น (รองรับการเลือกหลายรายการ)
if(isset($_POST['add_exclusions'])) {
    if(isset($_POST['subject_ids']) && is_array($_POST['subject_ids']) && count($_POST['subject_ids']) > 0) {
        $successCount = 0;
        $errorCount = 0;
        
        foreach($_POST['subject_ids'] as $subject_id) {
            $subject_id = intval($subject_id);
            
            // ตรวจสอบว่าวิชานี้อยู่ในสาขาวิชาที่กำหนดหรือไม่
            $check_subject = $conn->query("SELECT 1 FROM subjects WHERE id = $subject_id AND department_id = $department_id");
            
            if($check_subject->num_rows == 0) {
                $errorCount++;
                continue;
            }
            
            // ตรวจสอบว่ามีการยกเว้นแล้วหรือไม่
            $check_exclusion = $conn->query("SELECT 1 FROM subject_exclusions WHERE user_id = $user_id AND subject_id = $subject_id");
            
            if($check_exclusion->num_rows > 0) {
                $errorCount++;
                continue;
            }
            
            // เพิ่มการยกเว้น
            $sql = "INSERT INTO subject_exclusions (user_id, subject_id) VALUES ($user_id, $subject_id)";
            
            if($conn->query($sql)) {
                // ลบสิทธิ์การเข้าถึงวิชานี้
                $conn->query("DELETE FROM user_subjects WHERE user_id = $user_id AND subject_id = $subject_id");
                $successCount++;
            } else {
                $errorCount++;
            }
        }
        
        if($successCount > 0) {
            $message = "เพิ่มการยกเว้นวิชาสำเร็จจำนวน $successCount รายการ";
        }
        
        if($errorCount > 0) {
            $error = "มีข้อผิดพลาดเกิดขึ้นจำนวน $errorCount รายการ";
        }
    } else {
        $error = "กรุณาเลือกวิชาที่ต้องการยกเว้น";
    }
}

// ลบวิชาที่ยกเว้น
if(isset($_GET['remove_exclusion'])) {
    $subject_id = intval($_GET['remove_exclusion']);
    
    // ตรวจสอบว่ามีการยกเว้นหรือไม่
    $check_exclusion = $conn->query("SELECT 1 FROM subject_exclusions WHERE user_id = $user_id AND subject_id = $subject_id");
    
    if($check_exclusion->num_rows == 0) {
        $error = "ไม่พบข้อมูลการยกเว้นวิชานี้";
    } else {
        // ลบการยกเว้น
        $sql = "DELETE FROM subject_exclusions WHERE user_id = $user_id AND subject_id = $subject_id";
        
        if($conn->query($sql)) {
            // เพิ่มสิทธิ์การเข้าถึงวิชานี้
            $check_access = $conn->query("SELECT 1 FROM user_subjects WHERE user_id = $user_id AND subject_id = $subject_id");
            if($check_access->num_rows == 0) {
                $conn->query("INSERT INTO user_subjects (user_id, subject_id) VALUES ($user_id, $subject_id)");
            }
            $message = "ลบการยกเว้นวิชาสำเร็จ";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// ลบวิชาที่ยกเว้นหลายรายการ
if(isset($_POST['remove_exclusions'])) {
    if(isset($_POST['excluded_subject_ids']) && is_array($_POST['excluded_subject_ids']) && count($_POST['excluded_subject_ids']) > 0) {
        $successCount = 0;
        $errorCount = 0;
        
        foreach($_POST['excluded_subject_ids'] as $subject_id) {
            $subject_id = intval($subject_id);
            
            // ตรวจสอบว่ามีการยกเว้นหรือไม่
            $check_exclusion = $conn->query("SELECT 1 FROM subject_exclusions WHERE user_id = $user_id AND subject_id = $subject_id");
            
            if($check_exclusion->num_rows == 0) {
                $errorCount++;
                continue;
            }
            
            // ลบการยกเว้น
            $sql = "DELETE FROM subject_exclusions WHERE user_id = $user_id AND subject_id = $subject_id";
            
            if($conn->query($sql)) {
                // เพิ่มสิทธิ์การเข้าถึงวิชานี้
                $check_access = $conn->query("SELECT 1 FROM user_subjects WHERE user_id = $user_id AND subject_id = $subject_id");
                if($check_access->num_rows == 0) {
                    $conn->query("INSERT INTO user_subjects (user_id, subject_id) VALUES ($user_id, $subject_id)");
                }
                $successCount++;
            } else {
                $errorCount++;
            }
        }
        
        if($successCount > 0) {
            $message = "ลบการยกเว้นวิชาสำเร็จจำนวน $successCount รายการ";
        }
        
        if($errorCount > 0) {
            $error = "มีข้อผิดพลาดเกิดขึ้นจำนวน $errorCount รายการ";
        }
    } else {
        $error = "กรุณาเลือกวิชาที่ต้องการยกเลิกการยกเว้น";
    }
}

// ดึงรายวิชาในสาขาที่ยังไม่ได้ยกเว้น
$available_subjects_sql = "SELECT s.* FROM subjects s 
                         WHERE s.department_id = $department_id 
                         AND s.id NOT IN (
                             SELECT subject_id FROM subject_exclusions 
                             WHERE user_id = $user_id
                         )
                         ORDER BY s.name";
$available_subjects = $conn->query($available_subjects_sql);

// ดึงรายวิชาที่ยกเว้น
$excluded_subjects_sql = "SELECT s.*, se.user_id FROM subjects s 
                         JOIN subject_exclusions se ON s.id = se.subject_id 
                         WHERE se.user_id = $user_id AND s.department_id = $department_id 
                         ORDER BY s.name";
$excluded_subjects = $conn->query($excluded_subjects_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>จัดการยกเว้นรายวิชา - ระบบสื่อการเรียนรู้</title>
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
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold">จัดการยกเว้นรายวิชา</h2>
                        <p class="text-gray-600">
                            ผู้ใช้: <span class="font-semibold"><?php echo htmlspecialchars($permission['username']); ?></span> | 
                            สาขาวิชา: <span class="font-semibold"><?php echo htmlspecialchars($permission['department_name']); ?></span>
                        </p>
                    </div>
                    <a href="admin-permissions.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-arrow-left mr-2"></i> กลับ
                    </a>
                </div>
                
                <?php if($message): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                    <p><?php echo $message; ?></p>
                </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <p><?php echo $error; ?></p>
                </div>
                <?php endif; ?>
                
                <div class="flex flex-col md:flex-row gap-6">
                    <!-- เพิ่มวิชาที่ยกเว้น -->
                    <div class="w-full md:w-1/2">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-xl font-bold mb-4">เพิ่มวิชาที่ต้องการยกเว้น</h3>
                            
                            <?php if($available_subjects->num_rows > 0): ?>
                            <form method="POST" id="add-exclusions-form">
                                <div class="mb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <label class="block text-gray-700 text-sm font-bold">วิชาที่ต้องการยกเว้นไม่ให้เข้าถึง</label>
                                        <div>
                                            <span class="text-sm text-blue-600 cursor-pointer" id="select-all-available">เลือกทั้งหมด</span> | 
                                            <span class="text-sm text-blue-600 cursor-pointer" id="deselect-all-available">ยกเลิกทั้งหมด</span>
                                        </div>
                                    </div>
                                    
                                    <div class="relative mb-2">
                                        <input type="text" id="search-available" placeholder="ค้นหาวิชา..." class="w-full px-3 py-2 border rounded">
                                        <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                                    </div>
                                    
                                    <div class="max-h-80 overflow-y-auto border rounded p-3">
                                        <div id="available-subjects-container" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <?php while($subject = $available_subjects->fetch_assoc()): ?>
                                            <div class="subject-item">
                                                <label class="flex items-start p-2 hover:bg-gray-50 rounded">
                                                    <input type="checkbox" name="subject_ids[]" value="<?php echo $subject['id']; ?>" class="mt-1 mr-2">
                                                    <span><?php echo htmlspecialchars($subject['name']); ?></span>
                                                </label>
                                            </div>
                                            <?php endwhile; ?>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500 mt-2">
                                        <span id="selected-count">0</span> วิชาที่เลือก จากทั้งหมด <?php echo $available_subjects->num_rows; ?> วิชา
                                    </div>
                                </div>
                                
                                <button type="submit" name="add_exclusions" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                                    <i class="fas fa-plus mr-2"></i> เพิ่มการยกเว้น
                                </button>
                            </form>
                            <?php else: ?>
                            <div class="text-center text-gray-500">
                                <p>ไม่มีรายวิชาที่สามารถเพิ่มการยกเว้นได้</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- รายการวิชาที่ยกเว้น -->
                    <div class="w-full md:w-1/2">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-xl font-bold mb-4">รายการวิชาที่ยกเว้นไม่ให้เข้าถึง</h3>
                            
                            <?php if($excluded_subjects->num_rows > 0): ?>
                            <form method="POST" id="remove-exclusions-form">
                                <div class="mb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <label class="block text-gray-700 text-sm font-bold">วิชาที่ยกเว้นในปัจจุบัน</label>
                                        <div>
                                            <span class="text-sm text-blue-600 cursor-pointer" id="select-all-excluded">เลือกทั้งหมด</span> | 
                                            <span class="text-sm text-blue-600 cursor-pointer" id="deselect-all-excluded">ยกเลิกทั้งหมด</span>
                                        </div>
                                    </div>
                                    
                                    <div class="relative mb-2">
                                        <input type="text" id="search-excluded" placeholder="ค้นหาวิชา..." class="w-full px-3 py-2 border rounded">
                                        <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                                    </div>
                                    
                                    <div class="max-h-80 overflow-y-auto border rounded p-3">
                                        <div id="excluded-subjects-container" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <?php while($subject = $excluded_subjects->fetch_assoc()): ?>
                                            <div class="subject-item">
                                                <label class="flex items-start p-2 hover:bg-gray-50 rounded">
                                                    <input type="checkbox" name="excluded_subject_ids[]" value="<?php echo $subject['id']; ?>" class="mt-1 mr-2">
                                                    <span><?php echo htmlspecialchars($subject['name']); ?></span>
                                                </label>
                                            </div>
                                            <?php endwhile; ?>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500 mt-2">
                                        <span id="excluded-selected-count">0</span> วิชาที่เลือก จากทั้งหมด <?php echo $excluded_subjects->num_rows; ?> วิชา
                                    </div>
                                </div>
                                
                                <button type="submit" name="remove_exclusions" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                                    <i class="fas fa-times mr-2"></i> ยกเลิกการยกเว้น
                                </button>
                            </form>
                            <?php else: ?>
                            <div class="text-center text-gray-500">
                                <p>ไม่มีรายวิชาที่ถูกยกเว้นไม่ให้เข้าถึง</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6">
                    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4">
                        <h4 class="font-bold">คำอธิบาย</h4>
                        <ul class="list-disc ml-6 mt-2">
                            <li>วิชาที่ถูกยกเว้นคือวิชาที่ผู้ใช้จะไม่สามารถเข้าถึงสื่อการเรียนรู้ได้ แม้ว่าจะมีสิทธิ์ในสาขาวิชานั้นก็ตาม</li>
                            <li>เมื่อเพิ่มการยกเว้นวิชา ระบบจะเพิกถอนสิทธิ์การเข้าถึงวิชานั้นโดยอัตโนมัติ</li>
                            <li>เมื่อยกเลิกการยกเว้นวิชา ระบบจะให้สิทธิ์การเข้าถึงวิชานั้นโดยอัตโนมัติ</li>
                            <li>หากมีการอัพเดทรายวิชาในสาขาวิชานี้ ผู้ใช้จะได้รับสิทธิ์การเข้าถึงรายวิชาใหม่โดยอัตโนมัติ ยกเว้นวิชาที่ถูกกำหนดให้ยกเว้นไว้</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // ฟังก์ชันสำหรับการค้นหาและกรองรายการวิชา
        function setupSearch(searchInputId, containerSelector) {
            const searchInput = document.getElementById(searchInputId);
            const container = document.querySelector(containerSelector);
            if (!searchInput || !container) return;
            const items = container.querySelectorAll('.subject-item');
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                items.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }
        
        // ฟังก์ชันสำหรับนับจำนวนรายการที่เลือก
        function updateSelectedCount(formId, checkboxSelector, countElementId) {
            const form = document.getElementById(formId);
            if (!form) return;
            const checkboxes = form.querySelectorAll(checkboxSelector);
            const countElement = document.getElementById(countElementId);
            function updateCount() {
                const selectedCount = [...checkboxes].filter(cb => cb.checked).length;
                if (countElement) countElement.textContent = selectedCount;
            }
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateCount);
            });
            updateCount(); // อัพเดทครั้งแรก
        }
        
        // ฟังก์ชันสำหรับเลือก/ยกเลิกการเลือกทั้งหมด
        function setupBulkSelection(selectAllId, deselectAllId, formId, checkboxSelector) {
            const selectAllBtn = document.getElementById(selectAllId);
            const deselectAllBtn = document.getElementById(deselectAllId);
            const form = document.getElementById(formId);
            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', function() {
                    if (!form) { console.log('Form not found:', formId); return; }
                    const checkboxes = form.querySelectorAll(checkboxSelector);
                    const visibleCheckboxes = [...checkboxes].filter(cb => {
                        return cb.closest('.subject-item').style.display !== 'none';
                    });
                    visibleCheckboxes.forEach(cb => {
                        cb.checked = true;
                        cb.dispatchEvent(new Event('change'));
                    });
                });
            } else { console.log('SelectAll button not found:', selectAllId); }
            if (deselectAllBtn) {
                deselectAllBtn.addEventListener('click', function() {
                    if (!form) { console.log('Form not found:', formId); return; }
                    const checkboxes = form.querySelectorAll(checkboxSelector);
                    checkboxes.forEach(cb => {
                        cb.checked = false;
                        cb.dispatchEvent(new Event('change'));
                    });
                });
            } else { console.log('DeselectAll button not found:', deselectAllId); }
        }
        
        // ตั้งค่าการทำงานเมื่อหน้าเว็บโหลดเสร็จ
        document.addEventListener('DOMContentLoaded', function() {
            // ตั้งค่าการค้นหา
            setupSearch('search-available', '#available-subjects-container');
            setupSearch('search-excluded', '#excluded-subjects-container');
            
            // ตั้งค่าการนับจำนวนที่เลือก
            updateSelectedCount('add-exclusions-form', 'input[name="subject_ids[]"]', 'selected-count');
            updateSelectedCount('remove-exclusions-form', 'input[name="excluded_subject_ids[]"]', 'excluded-selected-count');
            
            // ตั้งค่าการเลือก/ยกเลิกทั้งหมด
            setupBulkSelection('select-all-available', 'deselect-all-available', 'add-exclusions-form', 'input[name="subject_ids[]"]');
            setupBulkSelection('select-all-excluded', 'deselect-all-excluded', 'remove-exclusions-form', 'input[name="excluded_subject_ids[]"]');
        });
    </script>
</body>
</html>