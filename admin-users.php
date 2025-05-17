<?php
// หน้าจัดการผู้ใช้
session_start();
include 'db.php';

// ตรวจสอบผู้ใช้ต้องเป็น admin เท่านั้น
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$message = '';
$error = '';

// เพิ่มผู้ใช้ใหม่
if(isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $name_1 = $_POST['name_1'];
    $expired_at = !empty($_POST['expired_at']) ? $_POST['expired_at'] : NULL;
    
    // ตรวจสอบว่ามีชื่อผู้ใช้นี้อยู่แล้วหรือไม่
    $check = $conn->query("SELECT 1 FROM users WHERE username = '$username'");
    
    if($check->num_rows > 0) {
        $error = "ชื่อผู้ใช้ '$username' มีอยู่ในระบบแล้ว";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // เพิ่มฟิลด์ name_1 และ expired_at
        $sql = "INSERT INTO users (username, password_hash, role, name_1, expired_at) 
                VALUES ('$username', '$password_hash', '$role', '$name_1', " . ($expired_at ? "'$expired_at'" : "NULL") . ")";
        
        if($conn->query($sql)) {
            $message = "เพิ่มผู้ใช้ '$username' สำเร็จ";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// ลบผู้ใช้
if(isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // ไม่ให้ลบตัวเอง
    if($delete_id == $_SESSION['user_id']) {
        $error = "ไม่สามารถลบบัญชีของตัวเองได้";
    } else {
        // ลบสิทธิ์ที่เกี่ยวข้อง
        $conn->query("DELETE FROM user_subjects WHERE user_id = $delete_id");
        
        // ลบผู้ใช้
        if($conn->query("DELETE FROM users WHERE id = $delete_id")) {
            $message = "ลบผู้ใช้เรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// แก้ไขผู้ใช้
if(isset($_POST['edit_user'])) {
    $edit_id = $_POST['edit_id'];
    $edit_username = $_POST['edit_username'];
    $edit_role = $_POST['edit_role'];
    $edit_password = $_POST['edit_password'];
    $edit_name_1 = $_POST['edit_name_1'];
    $edit_expired_at = !empty($_POST['edit_expired_at']) ? $_POST['edit_expired_at'] : NULL;
    
    // ตรวจสอบว่ามีชื่อผู้ใช้ซ้ำหรือไม่
    $check = $conn->query("SELECT 1 FROM users WHERE username = '$edit_username' AND id != $edit_id");
    
    if($check->num_rows > 0) {
        $error = "ชื่อผู้ใช้ '$edit_username' มีอยู่ในระบบแล้ว";
    } else {
        if(!empty($edit_password)) {
            // เปลี่ยนรหัสผ่านด้วย
            $password_hash = password_hash($edit_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET 
                    username = '$edit_username', 
                    role = '$edit_role', 
                    password_hash = '$password_hash', 
                    name_1 = '$edit_name_1', 
                    expired_at = " . ($edit_expired_at ? "'$edit_expired_at'" : "NULL") . " 
                    WHERE id = $edit_id";
        } else {
            // ไม่เปลี่ยนรหัสผ่าน
            $sql = "UPDATE users SET 
                    username = '$edit_username', 
                    role = '$edit_role', 
                    name_1 = '$edit_name_1', 
                    expired_at = " . ($edit_expired_at ? "'$edit_expired_at'" : "NULL") . " 
                    WHERE id = $edit_id";
        }
        
        if($conn->query($sql)) {
            $message = "แก้ไขผู้ใช้ '$edit_username' เรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// ดึงรายชื่อผู้ใช้ทั้งหมด
$users = $conn->query("SELECT * FROM users ORDER BY username");
?>

<!DOCTYPE html>
<html>
<head>
    <title>จัดการผู้ใช้ - ระบบสื่อการเรียนรู้</title>
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
                    <h2 class="text-2xl font-bold">จัดการผู้ใช้</h2>
                    <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded" onclick="document.getElementById('addUserModal').classList.remove('hidden')">
                        <i class="fas fa-plus mr-2"></i> เพิ่มผู้ใช้ใหม่
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
                
                <!-- ตารางผู้ใช้ -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลำดับ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อผู้ใช้</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สิทธิ์</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่สร้าง</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันหมดอายุ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $i = 1;
                            while($user = $users->fetch_assoc()): 
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $i++; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['name_1'] ?? ''); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if($user['role'] == 'admin'): ?>
                                        <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs">ผู้ดูแลระบบ</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">ผู้ใช้งาน</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if(!empty($user['expired_at'])): ?>
                                        <?php 
                                        $expired_date = new DateTime($user['expired_at']);
                                        $today = new DateTime();
                                        $date_display = date('d/m/Y', strtotime($user['expired_at']));
                                        
                                        if($expired_date < $today): 
                                        ?>
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs"><?php echo $date_display; ?> (หมดอายุ)</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs"><?php echo $date_display; ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">ไม่กำหนด</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button class="text-blue-500 hover:text-blue-700 mr-3" 
                                    onclick="openEditModal(
                                        <?php echo $user['id']; ?>, 
                                        '<?php echo htmlspecialchars($user['username']); ?>', 
                                        '<?php echo $user['role']; ?>',
                                        '<?php echo htmlspecialchars($user['name_1'] ?? ''); ?>',
                                        '<?php echo $user['expired_at'] ?? ''; ?>'
                                    )">
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </button>
                                    
                                    <?php if($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="?delete_id=<?php echo $user['id']; ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('ยืนยันการลบผู้ใช้ <?php echo htmlspecialchars($user['username']); ?> ?')">
                                        <i class="fas fa-trash-alt"></i> ลบ
                                    </a>
                                    <?php else: ?>
                                    <span class="text-gray-400 cursor-not-allowed">
                                        <i class="fas fa-trash-alt"></i> ลบ
                                    </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if($users->num_rows == 0): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">ไม่มีผู้ใช้ในระบบ</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal เพิ่มผู้ใช้ -->
    <div id="addUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">เพิ่มผู้ใช้ใหม่</h3>
                <button onclick="document.getElementById('addUserModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อผู้ใช้</label>
                    <input type="text" name="username" class="w-full px-3 py-2 border rounded" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อ</label>
                    <input type="text" name="name_1" class="w-full px-3 py-2 border rounded">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">รหัสผ่าน</label>
                    <input type="password" name="password" class="w-full px-3 py-2 border rounded" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">สิทธิ์</label>
                    <select name="role" class="w-full px-3 py-2 border rounded">
                        <option value="user">ผู้ใช้งาน</option>
                        <option value="admin">ผู้ดูแลระบบ</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">วันหมดอายุ</label>
                    <input type="date" name="expired_at" class="w-full px-3 py-2 border rounded">
                    <p class="text-xs text-gray-500 mt-1">เว้นว่างไว้หากไม่ต้องการกำหนดวันหมดอายุ</p>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded mr-2">
                        ยกเลิก
                    </button>
                    <button type="submit" name="add_user" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        เพิ่มผู้ใช้
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal แก้ไขผู้ใช้ -->
    <div id="editUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">แก้ไขผู้ใช้</h3>
                <button onclick="document.getElementById('editUserModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST">
                <input type="hidden" id="edit_id" name="edit_id" value="">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อผู้ใช้</label>
                    <input type="text" id="edit_username" name="edit_username" class="w-full px-3 py-2 border rounded" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อ</label>
                    <input type="text" id="edit_name_1" name="edit_name_1" class="w-full px-3 py-2 border rounded">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">รหัสผ่านใหม่ (เว้นว่างไว้หากไม่ต้องการเปลี่ยน)</label>
                    <input type="password" name="edit_password" class="w-full px-3 py-2 border rounded">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">สิทธิ์</label>
                    <select id="edit_role" name="edit_role" class="w-full px-3 py-2 border rounded">
                        <option value="user">ผู้ใช้งาน</option>
                        <option value="admin">ผู้ดูแลระบบ</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">วันหมดอายุ</label>
                    <input type="date" id="edit_expired_at" name="edit_expired_at" class="w-full px-3 py-2 border rounded">
                    <p class="text-xs text-gray-500 mt-1">เว้นว่างไว้หากไม่ต้องการกำหนดวันหมดอายุ</p>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded mr-2">
                        ยกเลิก
                    </button>
                    <button type="submit" name="edit_user" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openEditModal(id, username, role, name_1, expired_at) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_name_1').value = name_1;
            document.getElementById('edit_expired_at').value = expired_at;
            document.getElementById('editUserModal').classList.remove('hidden');
        }
    </script>