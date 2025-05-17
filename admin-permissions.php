        <?php
        // หน้าจัดการสิทธิ์การเข้าถึงสื่อตามสาขาวิชา
        session_start();
        include 'db.php';

        // ตรวจสอบผู้ใช้ต้องเป็น admin เท่านั้น
        if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: index.php");
            exit;
        }

        $message = '';
        $error = '';

        // สร้างตาราง user_departments ถ้ายังไม่มี
        $check_table = $conn->query("SHOW TABLES LIKE 'user_departments'");
        if($check_table->num_rows == 0) {
            $create_table_sql = "CREATE TABLE user_departments (
                user_id INT,
                department_id INT,
                PRIMARY KEY (user_id, department_id),
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (department_id) REFERENCES departments(id)
            )";
            $conn->query($create_table_sql);
        }

        // สร้างตาราง subject_exclusions ถ้ายังไม่มี (เก็บข้อมูลวิชาที่ยกเว้นไม่ให้เข้าถึง)
        $check_exclusion_table = $conn->query("SHOW TABLES LIKE 'subject_exclusions'");
        if($check_exclusion_table->num_rows == 0) {
            $create_exclusion_table = "CREATE TABLE subject_exclusions (
                user_id INT,
                subject_id INT,
                PRIMARY KEY (user_id, subject_id),
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (subject_id) REFERENCES subjects(id)
            )";
            $conn->query($create_exclusion_table);
        }

        // เพิ่มสิทธิ์ใหม่ตามสาขาวิชา
        if(isset($_POST['add_permission'])) {
            $user_id = $_POST['user_id'];
            $department_id = $_POST['department_id'];
            
            // ตรวจสอบว่ามีการให้สิทธิ์แล้วหรือไม่
            $check = $conn->query("SELECT 1 FROM user_departments WHERE user_id = $user_id AND department_id = $department_id");
            
            if($check->num_rows > 0) {
                $error = "ผู้ใช้นี้มีสิทธิ์ในสาขาวิชานี้อยู่แล้ว";
            } else {
                $sql = "INSERT INTO user_departments (user_id, department_id) VALUES ($user_id, $department_id)";
                
                if($conn->query($sql)) {
                    // ให้สิทธิ์ผู้ใช้เข้าถึงทุกวิชาในสาขาวิชา
                    $subjects_sql = "SELECT id FROM subjects WHERE department_id = $department_id";
                    $subjects_result = $conn->query($subjects_sql);
                    
                    while($subject = $subjects_result->fetch_assoc()) {
                        $subject_id = $subject['id'];
                        // ตรวจสอบว่ามีสิทธิ์ในวิชานี้แล้วหรือไม่
                        $check_subject = $conn->query("SELECT 1 FROM user_subjects WHERE user_id = $user_id AND subject_id = $subject_id");
                        if($check_subject->num_rows == 0) {
                            $conn->query("INSERT INTO user_subjects (user_id, subject_id) VALUES ($user_id, $subject_id)");
                        }
                    }
                    
                    $message = "เพิ่มสิทธิ์สำเร็จ";
                } else {
                    $error = "เกิดข้อผิดพลาด: " . $conn->error;
                }
            }
        }

        // ลบสิทธิ์
        if(isset($_GET['delete_user_id']) && isset($_GET['delete_department_id'])) {
            $delete_user_id = $_GET['delete_user_id'];
            $delete_department_id = $_GET['delete_department_id'];
            
            // ลบสิทธิ์การเข้าถึงวิชาทั้งหมดในสาขาวิชา
            $subjects_sql = "SELECT id FROM subjects WHERE department_id = $delete_department_id";
            $subjects_result = $conn->query($subjects_sql);
            
            while($subject = $subjects_result->fetch_assoc()) {
                $subject_id = $subject['id'];
                $conn->query("DELETE FROM user_subjects WHERE user_id = $delete_user_id AND subject_id = $subject_id");
            }
            
            // ลบข้อมูลในตาราง subject_exclusions
            $conn->query("DELETE FROM subject_exclusions WHERE user_id = $delete_user_id AND subject_id IN 
                        (SELECT id FROM subjects WHERE department_id = $delete_department_id)");
            
            // ลบสิทธิ์การเข้าถึงสาขาวิชา
            $sql = "DELETE FROM user_departments WHERE user_id = $delete_user_id AND department_id = $delete_department_id";
            
            if($conn->query($sql)) {
                $message = "ลบสิทธิ์เรียบร้อยแล้ว";
            } else {
                $error = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        }

        // จัดการยกเว้นวิชา
        if(isset($_GET['manage_exclusion']) && isset($_GET['user_id']) && isset($_GET['department_id'])) {
            $manage_user_id = $_GET['user_id'];
            $manage_department_id = $_GET['department_id'];
            
            header("Location: manage-subject-exclusions.php?user_id=$manage_user_id&department_id=$manage_department_id");
            exit;
        }

        // ดึงรายชื่อผู้ใช้ที่เป็นผู้ใช้ปกติ (ไม่ใช่ admin)
        $users = $conn->query("SELECT * FROM users WHERE role = 'user' ORDER BY username");

        // ดึงสาขาวิชาทั้งหมด
        $departments = $conn->query("SELECT * FROM departments ORDER BY name");

        // ฟิลเตอร์
        $filter_user = isset($_GET['user']) ? intval($_GET['user']) : 0;
        $filter_department = isset($_GET['department']) ? intval($_GET['department']) : 0;

        // ดึงข้อมูลสิทธิ์
        $permissions_sql = "SELECT ud.*, u.username, d.name as department_name,
                        (SELECT COUNT(*) FROM subjects s WHERE s.department_id = ud.department_id) as total_subjects,
                        (SELECT COUNT(*) FROM subject_exclusions se JOIN subjects s ON se.subject_id = s.id 
                            WHERE se.user_id = ud.user_id AND s.department_id = ud.department_id) as excluded_subjects
                        FROM user_departments ud
                        JOIN users u ON ud.user_id = u.id
                        JOIN departments d ON ud.department_id = d.id
                        WHERE 1=1";

        if($filter_user > 0) {
            $permissions_sql .= " AND ud.user_id = $filter_user";
        }

        if($filter_department > 0) {
            $permissions_sql .= " AND ud.department_id = $filter_department";
        }

        $permissions_sql .= " ORDER BY u.username, d.name";
        $permissions = $conn->query($permissions_sql);
        ?>

        <!DOCTYPE html>
        <html>
        <head>
            <title>จัดการสิทธิ์การเข้าถึงสื่อ - ระบบสื่อการเรียนรู้</title>
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
                            <h2 class="text-2xl font-bold">จัดการสิทธิ์การเข้าถึงสื่อตามสาขาวิชา</h2>
                            <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded" onclick="document.getElementById('addPermissionModal').classList.remove('hidden')">
                                <i class="fas fa-plus mr-2"></i> เพิ่มสิทธิ์
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
                                <div class="w-full md:w-1/2">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">กรองตามผู้ใช้</label>
                                    <select name="user" class="w-full px-3 py-2 border rounded">
                                        <option value="0">-- ทั้งหมด --</option>
                                        <?php 
                                        $users_filter = $conn->query("SELECT * FROM users WHERE role = 'user' ORDER BY username");
                                        while($user = $users_filter->fetch_assoc()): 
                                        ?>
                                        <option value="<?php echo $user['id']; ?>" <?php echo ($filter_user == $user['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="w-full md:w-1/2">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">กรองตามสาขาวิชา</label>
                                    <select name="department" class="w-full px-3 py-2 border rounded">
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
                                
                                <div class="w-full md:w-auto self-end">
                                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                                        <i class="fas fa-filter mr-2"></i> กรอง
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- ตารางสิทธิ์ -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลำดับ</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้ใช้</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สาขาวิชาที่เข้าถึงได้</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะการเข้าถึง</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php 
                                    $i = 1;
                                    while($permission = $permissions->fetch_assoc()): 
                                        $accessible_subjects = $permission['total_subjects'] - $permission['excluded_subjects'];
                                    ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $i++; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($permission['username']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs">
                                                <i class="fas fa-university mr-1"></i> <?php echo htmlspecialchars($permission['department_name']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 <?php echo ($permission['excluded_subjects'] > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?> rounded-full text-xs">
                                                <i class="fas fa-book mr-1"></i> 
                                                <?php echo $accessible_subjects; ?> / <?php echo $permission['total_subjects']; ?> รายวิชา
                                            </span>
                                            
                                            <?php if($permission['excluded_subjects'] > 0): ?>
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs ml-1">
                                                <i class="fas fa-ban mr-1"></i> ยกเว้น <?php echo $permission['excluded_subjects']; ?> วิชา
                                            </span>
                                            <?php endif; ?>
                                            
                                            <a href="?manage_exclusion=1&user_id=<?php echo $permission['user_id']; ?>&department_id=<?php echo $permission['department_id']; ?>" 
                                            class="ml-2 text-blue-500 hover:text-blue-700">
                                                <i class="fas fa-edit"></i> จัดการยกเว้นวิชา
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="?delete_user_id=<?php echo $permission['user_id']; ?>&delete_department_id=<?php echo $permission['department_id']; ?>" 
                                            class="text-red-500 hover:text-red-700" 
                                            onclick="return confirm('ยืนยันการลบสิทธิ์นี้? การลบสิทธิ์สาขาวิชานี้จะทำให้ผู้ใช้หมดสิทธิ์เข้าถึงรายวิชาทั้งหมดในสาขานี้ด้วย')">
                                                <i class="fas fa-trash-alt"></i> ลบ
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    
                                    <?php if($permissions->num_rows == 0): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">ไม่พบข้อมูลการกำหนดสิทธิ์</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal เพิ่มสิทธิ์ -->
            <div id="addPermissionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold">เพิ่มสิทธิ์การเข้าถึงสาขาวิชา</h3>
                        <button onclick="document.getElementById('addPermissionModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">ผู้ใช้</label>
                            <select name="user_id" class="w-full px-3 py-2 border rounded" required>
                                <?php 
                                // รีเซ็ต users result
                                $users = $conn->query("SELECT * FROM users WHERE role = 'user' ORDER BY username");
                                while($user = $users->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">สาขาวิชาที่ให้เข้าถึง</label>
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
                            <p class="text-xs text-gray-500 mt-1">
                                * ผู้ใช้จะได้รับสิทธิ์ในการเข้าถึงทุกรายวิชาที่อยู่ในสาขาวิชาที่เลือก<br>
                                * คุณสามารถยกเว้นบางวิชาไม่ให้เข้าถึงได้ในภายหลัง
                            </p>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="button" onclick="document.getElementById('addPermissionModal').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded mr-2">
                                ยกเลิก
                            </button>
                            <button type="submit" name="add_permission" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                                เพิ่มสิทธิ์
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </body>
        </html>