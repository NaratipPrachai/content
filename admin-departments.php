<?php
// หน้าจัดการสาขาวิชา
session_start();
include 'db.php';

// ตรวจสอบผู้ใช้ต้องเป็น admin เท่านั้น
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$message = '';
$error = '';

// เพิ่มสาขาวิชาใหม่
if(isset($_POST['add_department'])) {
    $department_name = $_POST['department_name'];
    
    // ตรวจสอบว่ามีสาขาวิชานี้อยู่แล้วหรือไม่
    $check = $conn->query("SELECT 1 FROM departments WHERE name = '$department_name'");
    
    if($check->num_rows > 0) {
        $error = "มีสาขาวิชา '$department_name' อยู่ในระบบแล้ว";
    } else {
        $sql = "INSERT INTO departments (name) VALUES ('$department_name')";
        
        if($conn->query($sql)) {
            $message = "เพิ่มสาขาวิชา '$department_name' สำเร็จ";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// ลบสาขาวิชา
if(isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // ตรวจสอบว่ามีรายวิชาที่อยู่ในสาขาวิชานี้หรือไม่
    $check_subjects = $conn->query("SELECT 1 FROM subjects WHERE department_id = $delete_id");
    
    if($check_subjects->num_rows > 0) {
        $error = "ไม่สามารถลบสาขาวิชานี้ได้ เนื่องจากมีรายวิชาในสาขานี้อยู่";
    } else {
        if($conn->query("DELETE FROM departments WHERE id = $delete_id")) {
            $message = "ลบสาขาวิชาเรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// แก้ไขสาขาวิชา
if(isset($_POST['edit_department'])) {
    $edit_id = $_POST['edit_id'];
    $edit_name = $_POST['edit_name'];
    
    // ตรวจสอบว่ามีสาขาวิชาซ้ำหรือไม่
    $check = $conn->query("SELECT 1 FROM departments WHERE name = '$edit_name' AND id != $edit_id");
    
    if($check->num_rows > 0) {
        $error = "มีสาขาวิชา '$edit_name' อยู่ในระบบแล้ว";
    } else {
        $sql = "UPDATE departments SET name = '$edit_name' WHERE id = $edit_id";
        
        if($conn->query($sql)) {
            $message = "แก้ไขสาขาวิชาเป็น '$edit_name' เรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// ดึงรายการสาขาวิชาทั้งหมด
$departments = $conn->query("SELECT d.*, 
                            (SELECT COUNT(*) FROM subjects WHERE department_id = d.id) as subject_count 
                            FROM departments d ORDER BY d.name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>จัดการสาขาวิชา - ระบบสื่อการเรียนรู้</title>
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
                    <h2 class="text-2xl font-bold">จัดการสาขาวิชา</h2>
                    <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded" onclick="document.getElementById('addDepartmentModal').classList.remove('hidden')">
                        <i class="fas fa-plus mr-2"></i> เพิ่มสาขาวิชาใหม่
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
                
                <!-- ตารางสาขาวิชา -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลำดับ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อสาขาวิชา</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนรายวิชา</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $i = 1;
                            while($department = $departments->fetch_assoc()): 
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $i++; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($department['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                                        <?php echo $department['subject_count']; ?> รายวิชา
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button class="text-blue-500 hover:text-blue-700 mr-3" onclick="openEditModal(<?php echo $department['id']; ?>, '<?php echo htmlspecialchars($department['name']); ?>')">
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </button>
                                    
                                    <?php if($department['subject_count'] == 0): ?>
                                    <a href="?delete_id=<?php echo $department['id']; ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('ยืนยันการลบสาขาวิชา <?php echo htmlspecialchars($department['name']); ?> ?')">
                                        <i class="fas fa-trash-alt"></i> ลบ
                                    </a>
                                    <?php else: ?>
                                    <span class="text-gray-400 cursor-not-allowed" title="ไม่สามารถลบได้เนื่องจากมีรายวิชาอยู่ในสาขานี้">
                                        <i class="fas fa-trash-alt"></i> ลบ
                                    </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if($departments->num_rows == 0): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">ไม่มีสาขาวิชาในระบบ</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal เพิ่มสาขาวิชา -->
    <div id="addDepartmentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">เพิ่มสาขาวิชาใหม่</h3>
                <button onclick="document.getElementById('addDepartmentModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อสาขาวิชา</label>
                    <input type="text" name="department_name" class="w-full px-3 py-2 border rounded" required>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" onclick="document.getElementById('addDepartmentModal').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded mr-2">
                        ยกเลิก
                    </button>
                    <button type="submit" name="add_department" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        เพิ่มสาขาวิชา
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal แก้ไขสาขาวิชา -->
    <div id="editDepartmentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">แก้ไขสาขาวิชา</h3>
                <button onclick="document.getElementById('editDepartmentModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST">
                <input type="hidden" id="edit_id" name="edit_id" value="">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อสาขาวิชา</label>
                    <input type="text" id="edit_name" name="edit_name" class="w-full px-3 py-2 border rounded" required>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" onclick="document.getElementById('editDepartmentModal').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded mr-2">
                        ยกเลิก
                    </button>
                    <button type="submit" name="edit_department" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openEditModal(id, name) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('editDepartmentModal').classList.remove('hidden');
        }
    </script>
</body>
</html>