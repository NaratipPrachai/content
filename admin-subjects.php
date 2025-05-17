<?php
// หน้าจัดการรายวิชา
session_start();
include 'db.php';

// ตรวจสอบผู้ใช้ต้องเป็น admin เท่านั้น
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$message = '';
$error = '';

// เพิ่มรายวิชาใหม่
if(isset($_POST['add_subject'])) {
    $subject_name = $_POST['subject_name'];
    $department_id = $_POST['department_id'];
    
    // ตรวจสอบว่ามีรายวิชานี้อยู่แล้วหรือไม่
    $check = $conn->query("SELECT 1 FROM subjects WHERE name = '$subject_name' AND department_id = $department_id");
    
    if($check->num_rows > 0) {
        $error = "มีรายวิชา '$subject_name' อยู่ในสาขาวิชานี้แล้ว";
    } else {
        $sql = "INSERT INTO subjects (name, department_id) VALUES ('$subject_name', $department_id)";
        
        if($conn->query($sql)) {
            $message = "เพิ่มรายวิชา '$subject_name' สำเร็จ";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// ลบรายวิชา
if(isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // ลบสื่อที่เกี่ยวข้อง
    $conn->query("DELETE FROM media_files WHERE subject_id = $delete_id");
    
    // ลบสิทธิ์ที่เกี่ยวข้อง
    $conn->query("DELETE FROM user_subjects WHERE subject_id = $delete_id");
    
    // ลบรายวิชา
    if($conn->query("DELETE FROM subjects WHERE id = $delete_id")) {
        $message = "ลบรายวิชาเรียบร้อยแล้ว";
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// แก้ไขรายวิชา
if(isset($_POST['edit_subject'])) {
    $edit_id = $_POST['edit_id'];
    $edit_name = $_POST['edit_name'];
    $edit_department_id = $_POST['edit_department_id'];
    
    // ตรวจสอบว่ามีรายวิชาซ้ำหรือไม่
    $check = $conn->query("SELECT 1 FROM subjects WHERE name = '$edit_name' AND department_id = $edit_department_id AND id != $edit_id");
    
    if($check->num_rows > 0) {
        $error = "มีรายวิชา '$edit_name' อยู่ในสาขาวิชานี้แล้ว";
    } else {
        $sql = "UPDATE subjects SET name = '$edit_name', department_id = $edit_department_id WHERE id = $edit_id";
        
        if($conn->query($sql)) {
            $message = "แก้ไขรายวิชาเป็น '$edit_name' เรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// ดึงข้อมูลสาขาวิชา
$departments = $conn->query("SELECT * FROM departments ORDER BY name");

// ฟิลเตอร์ตามสาขาวิชา
$filter_department = isset($_GET['department']) ? intval($_GET['department']) : 0;

// ดึงรายวิชาทั้งหมด
$subjects_sql = "SELECT s.*, d.name as department_name,
                (SELECT COUNT(*) FROM media_files WHERE subject_id = s.id) as media_count,
                (SELECT COUNT(*) FROM user_subjects WHERE subject_id = s.id) as user_count 
                FROM subjects s
                JOIN departments d ON s.department_id = d.id";

if($filter_department > 0) {
    $subjects_sql .= " WHERE s.department_id = $filter_department";
}

$subjects_sql .= " ORDER BY d.name, s.name";
$subjects = $conn->query($subjects_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>จัดการรายวิชา - ระบบสื่อการเรียนรู้</title>
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
                    <h2 class="text-2xl font-bold">จัดการรายวิชา</h2>
                    <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded" onclick="document.getElementById('addSubjectModal').classList.remove('hidden')">
                        <i class="fas fa-plus mr-2"></i> เพิ่มรายวิชาใหม่
                    </button>
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
                
                <!-- ตัวกรอง -->
                <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                    <form method="GET" class="flex flex-wrap md:flex-nowrap gap-4">
                        <div class="w-full">
                            <label class="block text-gray-700 text-sm font-bold mb-2">กรองตามสาขาวิชา</label>
                            <select name="department" class="w-full px-3 py-2 border rounded" onchange="this.form.submit()">
                                <option value="0">-- ทั้งหมด --</option>
                                <?php 
                                $departments_filter = $conn->query("SELECT * FROM departments ORDER BY name");
                                while($department = $departments_filter->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $department['id']; ?>" <?php echo ($filter_department == $department['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($department['name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </form>
                </div>
                
                <!-- ตารางรายวิชา -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สาขาวิชา</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนสื่อ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนผู้มีสิทธิ์</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $i = 1;
                            while($subject = $subjects->fetch_assoc()): 
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $i++; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($subject['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs">
                                        <?php echo htmlspecialchars($subject['department_name']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                                        <?php echo $subject['media_count']; ?> รายการ
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                        <?php echo $subject['user_count']; ?> คน
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button class="text-blue-500 hover:text-blue-700 mr-3" onclick="openEditModal(
                                        <?php echo $subject['id']; ?>, 
                                        '<?php echo htmlspecialchars($subject['name']); ?>', 
                                        <?php echo $subject['department_id']; ?>
                                    )">
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </button>
                                    
                                    <a href="?delete_id=<?php echo $subject['id']; ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('ยืนยันการลบรายวิชา <?php echo htmlspecialchars($subject['name']); ?> ? \nการลบจะทำให้สื่อและสิทธิ์ที่เกี่ยวข้องถูกลบด้วย')">
                                        <i class="fas fa-trash-alt"></i> ลบ
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if($subjects->num_rows == 0): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">ไม่มีรายวิชาในระบบ</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal เพิ่มรายวิชา -->
    <div id="addSubjectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">เพิ่มรายวิชาใหม่</h3>
                <button onclick="document.getElementById('addSubjectModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">สาขาวิชา</label>
                    <select name="department_id" class="w-full px-3 py-2 border rounded" required>
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
                    <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อรายวิชา</label>
                    <input type="text" name="subject_name" class="w-full px-3 py-2 border rounded" required>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" onclick="document.getElementById('addSubjectModal').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded mr-2">
                        ยกเลิก
                    </button>
                    <button type="submit" name="add_subject" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        เพิ่มรายวิชา
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal แก้ไขรายวิชา -->
    <div id="editSubjectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">แก้ไขรายวิชา</h3>
                <button onclick="document.getElementById('editSubjectModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST">
                <input type="hidden" id="edit_id" name="edit_id" value="">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">สาขาวิชา</label>
                    <select id="edit_department_id" name="edit_department_id" class="w-full px-3 py-2 border rounded" required>
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
                    <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อรายวิชา</label>
                    <input type="text" id="edit_name" name="edit_name" class="w-full px-3 py-2 border rounded" required>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" onclick="document.getElementById('editSubjectModal').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded mr-2">
                        ยกเลิก
                    </button>
                    <button type="submit" name="edit_subject" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openEditModal(id, name, department_id) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_department_id').value = department_id;
            document.getElementById('editSubjectModal').classList.remove('hidden');
        }
    </script>
</body>
</html>