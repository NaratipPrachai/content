<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// หน้าจัดการสิทธิ์เฉพาะวิชาข้ามแผนก
session_start();
include 'db.php';

// ตรวจสอบผู้ใช้ต้องเป็น admin เท่านั้น
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// ตรวจสอบการส่งค่า user_id
if(!isset($_GET['user_id'])) {
    header("Location: manage-department-permissions.php");
    exit;
}

$user_id = intval($_GET['user_id']);
$message = '';
$error = '';

// ดึงข้อมูลผู้ใช้
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_sql);

if($user_result->num_rows == 0) {
    header("Location: manage-department-permissions.php");
    exit;
}

$user = $user_result->fetch_assoc();

// เพิ่มสิทธิ์การเข้าถึงวิชาเฉพาะข้ามแผนก
if(isset($_POST['add_cross_subject'])) {
    $subject_id = intval($_POST['subject_id']);
    
    // ตรวจสอบว่าวิชานี้มีอยู่จริงหรือไม่
    $check_subject = $conn->query("SELECT * FROM subjects WHERE id = $subject_id");
    
    if($check_subject->num_rows == 0) {
        $error = "ไม่พบรายวิชาที่ระบุ";
    } else {
        $subject = $check_subject->fetch_assoc();
        
        // ตรวจสอบว่ามีสิทธิ์เข้าถึงวิชานี้แล้วหรือไม่
        $check_permission = $conn->query("SELECT 1 FROM user_subjects WHERE user_id = $user_id AND subject_id = $subject_id");
        
        if($check_permission->num_rows > 0) {
            $error = "ผู้ใช้นี้มีสิทธิ์เข้าถึงวิชานี้อยู่แล้ว";
        } else {
            // ตรวจสอบว่าถูกยกเว้นอยู่หรือไม่
            $check_exclusion = $conn->query("SELECT 1 FROM subject_exclusions WHERE user_id = $user_id AND subject_id = $subject_id");
            
            if($check_exclusion->num_rows > 0) {
                // ลบข้อมูลในตารางยกเว้น
                $conn->query("DELETE FROM subject_exclusions WHERE user_id = $user_id AND subject_id = $subject_id");
            }
            
            // เพิ่มสิทธิ์การเข้าถึงวิชา
            $sql = "INSERT INTO user_subjects (user_id, subject_id) VALUES ($user_id, $subject_id)";
            
            if($conn->query($sql)) {
                $message = "เพิ่มสิทธิ์การเข้าถึงวิชา \"" . htmlspecialchars($subject['name']) . "\" สำเร็จ";
            } else {
                $error = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        }
    }
}

// ลบสิทธิ์การเข้าถึงวิชาเฉพาะ
if(isset($_GET['remove_subject_id'])) {
    $subject_id = intval($_GET['remove_subject_id']);
    
    // ตรวจสอบว่ามีสิทธิ์เข้าถึงวิชานี้หรือไม่
    $check_permission = $conn->query("SELECT us.*, s.name as subject_name, d.name as department_name 
                                  FROM user_subjects us 
                                  JOIN subjects s ON us.subject_id = s.id 
                                  JOIN departments d ON s.department_id = d.id 
                                  WHERE us.user_id = $user_id AND us.subject_id = $subject_id");
    
    if($check_permission->num_rows == 0) {
        $error = "ไม่พบข้อมูลการให้สิทธิ์วิชานี้";
    } else {
        $permission = $check_permission->fetch_assoc();
        
        // ลบสิทธิ์การเข้าถึงวิชา
        $sql = "DELETE FROM user_subjects WHERE user_id = $user_id AND subject_id = $subject_id";
        
        if($conn->query($sql)) {
            $message = "ลบสิทธิ์การเข้าถึงวิชา \"" . htmlspecialchars($permission['subject_name']) . "\" จากแผนก \"" . htmlspecialchars($permission['department_name']) . "\" สำเร็จ";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// ดึงรายการสาขาวิชาทั้งหมด
$departments = $conn->query("SELECT * FROM departments ORDER BY name");

// ดึงวิชาที่ผู้ใช้มีสิทธิ์เข้าถึงแล้ว โดยไม่ได้มาจากสิทธิ์ระดับสาขาวิชา
$cross_subjects_sql = "SELECT us.*, s.name as subject_name, s.code as subject_code, d.name as department_name, d.id as department_id
                      FROM user_subjects us
                      JOIN subjects s ON us.subject_id = s.id
                      JOIN departments d ON s.department_id = d.id
                      WHERE us.user_id = $user_id
                      AND s.department_id NOT IN (
                          SELECT department_id FROM user_departments WHERE user_id = $user_id
                      )
                      ORDER BY d.name, s.name";
$cross_subjects = $conn->query($cross_subjects_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>จัดการสิทธิ์การเข้าถึงวิชาข้ามแผนก - ระบบสื่อการเรียนรู้</title>
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
                        <h2 class="text-2xl font-bold">จัดการสิทธิ์การเข้าถึงวิชาข้ามแผนก</h2>
                        <p class="text-gray-600">
                            ผู้ใช้: <span class="font-semibold"><?php echo htmlspecialchars($user['username']); ?></span>
                        </p>
                    </div>
                    <a href="manage-department-permissions.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
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
                    <!-- เพิ่มสิทธิ์การเข้าถึงวิชาข้ามแผนก -->
                    <div class="w-full md:w-1/2">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-xl font-bold mb-4">เพิ่มสิทธิ์การเข้าถึงวิชาข้ามแผนก</h3>
                            
                            <form method="POST" id="add-cross-subject-form">
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">แผนกวิชา</label>
                                    <select id="department_id" class="w-full px-3 py-2 border rounded">
                                        <option value="">-- เลือกแผนกวิชา --</option>
                                        <?php while($department = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $department['id']; ?>">
                                            <?php echo htmlspecialchars($department['name']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">รายวิชา</label>
                                    <select name="subject_id" id="subject_id" class="w-full px-3 py-2 border rounded" required>
                                        <option value="">-- กรุณาเลือกแผนกวิชาก่อน --</option>
                                    </select>
                                </div>
                                
                                <button type="submit" name="add_cross_subject" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                                    <i class="fas fa-plus mr-2"></i> เพิ่มสิทธิ์การเข้าถึงวิชา
                                </button>
                            </form>
                        </div>
                        
                        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mt-4">
                            <h4 class="font-bold">คำอธิบาย</h4>
                            <p class="mt-2">
                                หน้านี้ใช้สำหรับให้สิทธิ์ผู้ใช้เข้าถึงวิชาในแผนกที่ผู้ใช้ไม่มีสิทธิ์เข้าถึงทั้งแผนก เช่น นักศึกษาในสาขาคอมพิวเตอร์ที่ต้องเรียนบางวิชาในแผนกบัญชีเพื่อปรับพื้นฐาน 
                                การให้สิทธิ์นี้จะเป็นการให้สิทธิ์เฉพาะวิชาเท่านั้น ไม่ได้ให้สิทธิ์ในแผนกทั้งหมด
                            </p>
                        </div>
                    </div>
                    
                    <!-- รายการวิชาที่มีสิทธิ์เข้าถึงข้ามแผนก -->
                    <div class="w-full md:w-1/2">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-xl font-bold mb-4">รายการวิชาที่มีสิทธิ์เข้าถึงข้ามแผนก</h3>
                            
                            <?php if($cross_subjects->num_rows > 0): ?>
                            <div class="overflow-hidden rounded-lg border">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">แผนกวิชา</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">รหัสวิชา</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อวิชา</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php while($subject = $cross_subjects->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs">
                                                    <i class="fas fa-university mr-1"></i> <?php echo htmlspecialchars($subject['department_name']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap"><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap"><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <a href="?user_id=<?php echo $user_id; ?>&remove_subject_id=<?php echo $subject['subject_id']; ?>" 
                                                   class="text-red-500 hover:text-red-700" 
                                                   onclick="return confirm('ยืนยันการลบสิทธิ์การเข้าถึงวิชานี้?')">
                                                    <i class="fas fa-times"></i> ลบสิทธิ์
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-gray-500">
                                <p>ไม่มีรายการวิชาที่ผู้ใช้มีสิทธิ์เข้าถึงข้ามแผนก</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const departmentSelect = document.getElementById('department_id');
            const subjectSelect = document.getElementById('subject_id');
            
            // เมื่อเลือกแผนกวิชา ให้ดึงรายวิชาในแผนกนั้น
            departmentSelect.addEventListener('change', function() {
                const departmentId = this.value;
                
                // รีเซ็ตค่าเดิม
                subjectSelect.innerHTML = '<option value="">-- เลือกรายวิชา --</option>';
                
                if (departmentId) {
                    // ดึงรายวิชาจาก AJAX
                    fetch(`get-subjects-by-department.php?department_id=${departmentId}&user_id=<?php echo $user_id; ?>`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.length > 0) {
                                data.forEach(subject => {
                                    const option = document.createElement('option');
                                    option.value = subject.id;
                                    option.textContent = `${subject.code} - ${subject.name}`;
                                    subjectSelect.appendChild(option);
                                });
                            } else {
                                subjectSelect.innerHTML = '<option value="">-- ไม่พบรายวิชาที่ยังไม่มีสิทธิ์เข้าถึง --</option>';
                            }
                        })
                        .catch(error => {
                            console.error('เกิดข้อผิดพลาด:', error);
                            subjectSelect.innerHTML = '<option value="">-- เกิดข้อผิดพลาดในการดึงข้อมูล --</option>';
                        });
                }
            });
        });
    </script>
</body>
</html>