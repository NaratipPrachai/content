<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// เพิ่มสิทธิ์การเข้าถึงวิชาข้ามแผนก
if(isset($_POST['add_subjects'])) {
    $subject_ids = $_POST['subject_ids'];
    
    if(!empty($subject_ids)) {
        $success = true;
        foreach($subject_ids as $subject_id) {
            $subject_id = intval($subject_id);
            
            // ตรวจสอบว่ามีสิทธิ์ในวิชานี้แล้วหรือไม่
            $check = $conn->query("SELECT 1 FROM user_subjects WHERE user_id = $user_id AND subject_id = $subject_id");
            
            if($check->num_rows == 0) {
                $sql = "INSERT INTO user_subjects (user_id, subject_id) VALUES ($user_id, $subject_id)";
                if(!$conn->query($sql)) {
                    $success = false;
                    $error = "เกิดข้อผิดพลาด: " . $conn->error;
                    break;
                }
            }
        }
        
        if($success) {
            $message = "เพิ่มสิทธิ์การเข้าถึงวิชาข้ามแผนกสำเร็จ";
        }
    }
}

// ลบสิทธิ์การเข้าถึงวิชาข้ามแผนก
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
$cross_subjects_sql = "SELECT us.*, s.name as subject_name, d.name as department_name, d.id as department_id
                      FROM user_subjects us
                      JOIN subjects s ON us.subject_id = s.id
                      JOIN departments d ON s.department_id = d.id
                      WHERE us.user_id = $user_id
                      AND s.department_id NOT IN (
                          SELECT department_id FROM user_departments WHERE user_id = $user_id
                      )
                      ORDER BY d.name, s.name";
$cross_subjects = $conn->query($cross_subjects_sql);

// นับจำนวนวิชาข้ามแผนกทั้งหมด
$total_cross_subjects = $cross_subjects->num_rows;
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
<body class="bg-gray-100 min-h-screen">
    <div class="flex">
        <?php include 'admin-sidebar.php'; ?>
        
        <div class="flex-1 p-8">
            <div class="max-w-6xl mx-auto">
                <!-- หัวข้อและปุ่มกลับ -->
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-exchange-alt mr-2"></i>
                            จัดการสิทธิ์การเข้าถึงวิชาข้ามแผนก
                        </h1>
                        <p class="text-gray-600 mt-1">
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- เพิ่มสิทธิ์การเข้าถึงวิชาข้ามแผนก -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold mb-4">เพิ่มสิทธิ์การเข้าถึงวิชาข้ามแผนก</h2>
                        
                        <form method="POST" id="add-cross-subject-form">
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">แผนกวิชา</label>
                                <select id="department_id" class="w-full px-3 py-2 border rounded">
                                    <option value="">-- เลือกแผนกวิชา --</option>
                                    <?php 
                                    // รีเซ็ต departments result
                                    $departments = $conn->query("SELECT * FROM departments ORDER BY name");
                                    while($department = $departments->fetch_assoc()): 
                                    ?>
                                    <option value="<?php echo $department['id']; ?>">
                                        <?php echo htmlspecialchars($department['name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">รายวิชา</label>
                                <select id="subject_id" name="subject_ids[]" class="w-full px-3 py-2 border rounded" multiple size="5">
                                    <option value="" disabled>-- เลือกแผนกวิชาก่อน --</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">
                                    * กด Ctrl (หรือ Command บน Mac) เพื่อเลือกหลายรายวิชา
                                </p>
                            </div>

                            <button type="submit" name="add_subjects" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                                <i class="fas fa-plus mr-2"></i> เพิ่มสิทธิ์
                            </button>
                        </form>
                    </div>

                    <!-- สรุปข้อมูลสิทธิ์ -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold mb-4">สรุปข้อมูลสิทธิ์</h2>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-exchange-alt text-2xl text-blue-500 mr-3"></i>
                                    <div>
                                        <h3 class="font-bold text-blue-800">วิชาข้ามแผนกทั้งหมด</h3>
                                        <p class="text-2xl font-bold text-blue-600"><?php echo $total_cross_subjects; ?> รายวิชา</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h3 class="font-bold text-gray-700 mb-3">คำอธิบาย</h3>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li><i class="fas fa-info-circle text-blue-500 mr-2"></i> วิชาข้ามแผนกคือวิชาที่ผู้ใช้มีสิทธิ์เข้าถึง แต่ไม่ได้มาจากสิทธิ์ระดับสาขาวิชา</li>
                                <li><i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i> การลบสิทธิ์วิชาข้ามแผนกจะไม่ส่งผลต่อสิทธิ์การเข้าถึงวิชาอื่นๆ</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- รายการวิชาข้ามแผนก -->
                <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4">รายการวิชาข้ามแผนก</h2>
                    
                    <?php if($total_cross_subjects > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">แผนกวิชา</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อวิชา</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                // รีเซ็ต cross_subjects result
                                $cross_subjects = $conn->query($cross_subjects_sql);
                                while($subject = $cross_subjects->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs">
                                            <i class="fas fa-university mr-1"></i> <?php echo htmlspecialchars($subject['department_name']); ?>
                                        </span>
                                    </td>
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
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-info-circle text-4xl mb-4"></i>
                        <p>ไม่มีรายการวิชาข้ามแผนก</p>
                    </div>
                    <?php endif; ?>
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
                const userId = <?php echo $user_id; ?>;
                
                if(departmentId) {
                    // ดึงรายวิชาจาก API
                    fetch(`get-subjects-by-department.php?department_id=${departmentId}&user_id=${userId}`)
                        .then(response => response.json())
                        .then(subjects => {
                            subjectSelect.innerHTML = '';
                            if(subjects.error) {
                                // แสดง error ที่ได้จาก PHP
                                const option = document.createElement('option');
                                option.value = '';
                                option.textContent = '-- เกิดข้อผิดพลาด: ' + subjects.error + ' --';
                                option.disabled = true;
                                subjectSelect.appendChild(option);
                            } else if(subjects.length > 0) {
                                subjects.forEach(subject => {
                                    const option = document.createElement('option');
                                    option.value = subject.id;
                                    option.textContent = `${subject.code} - ${subject.name}`;
                                    subjectSelect.appendChild(option);
                                });
                            } else {
                                const option = document.createElement('option');
                                option.value = '';
                                option.textContent = '-- ไม่มีรายวิชาที่สามารถเพิ่มได้ --';
                                option.disabled = true;
                                subjectSelect.appendChild(option);
                            }
                        })
                        .catch(error => {
                            subjectSelect.innerHTML = `<option value="" disabled>-- เกิดข้อผิดพลาด JS: ${error} --</option>`;
                            console.error('Error:', error);
                        });
                } else {
                    subjectSelect.innerHTML = '<option value="" disabled>-- เลือกแผนกวิชาก่อน --</option>';
                }
            });
        });
    </script>
</body>
</html>

