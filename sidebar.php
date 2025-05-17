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

// ดึงข้อมูลสาขาวิชาที่ผู้ใช้มีสิทธิ์เข้าถึง (สำหรับ admin ดูได้ทั้งหมด)
if($role == 'admin') {
    // แสดงรายการสาขาวิชาทั้งหมด
    $dept_sql = "SELECT * FROM departments ORDER BY name";
} else {
    // เพิ่มเงื่อนไขให้ดึงสาขาจากสิทธิ์ที่ได้รับโดยตรง (user_departments)
    $dept_sql = "SELECT DISTINCT d.* FROM departments d 
                LEFT JOIN user_departments ud ON d.id = ud.department_id
                LEFT JOIN subjects s ON d.id = s.department_id
                LEFT JOIN user_subjects us ON s.id = us.subject_id
                WHERE (ud.user_id = $user_id) OR (us.user_id = $user_id)
                ORDER BY d.name";
}
$departments = $conn->query($dept_sql);

// ดึงข้อมูลสาขาวิชาที่เลือก
$dept_name = "";
if($dept_id > 0) {
    $dept_info = $conn->query("SELECT name FROM departments WHERE id = $dept_id")->fetch_assoc();
    $dept_name = $dept_info ? $dept_info['name'] : "";
}

// ดึงข้อมูลวิชาที่ผู้ใช้มีสิทธิ์ตามสาขาวิชา
if($dept_id > 0) {
    if($role == 'admin') {
        $subject_sql = "SELECT * FROM subjects WHERE department_id = $dept_id ORDER BY name";
    } else {
        // ดึงทั้งวิชาที่ได้รับสิทธิ์โดยตรง และวิชาที่ได้รับจากสาขา แต่ไม่รวมวิชาที่ถูกยกเว้น
        $subject_sql = "SELECT DISTINCT s.* FROM subjects s
                        LEFT JOIN user_subjects us ON s.id = us.subject_id
                        LEFT JOIN user_departments ud ON s.department_id = ud.department_id
                        WHERE s.department_id = $dept_id 
                        AND (us.user_id = $user_id OR ud.user_id = $user_id)
                        AND s.id NOT IN (
                            SELECT subject_id FROM subject_exclusions WHERE user_id = $user_id
                        )
                        ORDER BY s.name";
    }
} else {
    if($role == 'admin') {
        $subject_sql = "SELECT s.*, d.name as department_name 
                        FROM subjects s
                        JOIN departments d ON s.department_id = d.id
                        ORDER BY d.name, s.name";
    } else {
        // ดึงทั้งวิชาที่ได้รับสิทธิ์โดยตรง และวิชาที่ได้รับจากสาขา แต่ไม่รวมวิชาที่ถูกยกเว้น
        $subject_sql = "SELECT DISTINCT s.*, d.name as department_name 
                        FROM subjects s
                        JOIN departments d ON s.department_id = d.id
                        LEFT JOIN user_subjects us ON s.id = us.subject_id
                        LEFT JOIN user_departments ud ON s.department_id = ud.department_id
                        WHERE (us.user_id = $user_id OR ud.user_id = $user_id)
                        AND s.id NOT IN (
                            SELECT subject_id FROM subject_exclusions WHERE user_id = $user_id
                        )
                        ORDER BY d.name, s.name";
    }
}
$subjects = $conn->query($subject_sql);
?>

<!-- รายการสาขาวิชาและวิชา -->
<div class="w-full md:w-1/4">
    <!-- สาขาวิชา -->
    <div class="sidebar p-6 mb-6">
        <h2 class="text-lg font-bold mb-6 flex items-center text-indigo-700">
            <i class="fas fa-university mr-2"></i> สาขาวิชา
        </h2>
        <ul class="space-y-3">
            <li class="nav-item <?php echo ($dept_id == 0) ? 'active-nav-item' : ''; ?>">
                <a href="?dept=0" class="block hover:text-indigo-600 px-4 py-2 rounded-lg <?php echo ($dept_id == 0) ? 'bg-indigo-50 text-indigo-600' : 'text-gray-700'; ?> transition duration-300">
                    <i class="fas fa-th-list mr-2"></i> ทั้งหมด
                </a>
            </li>
            <?php 
            if($departments->num_rows == 0): 
            ?>
            <li class="px-4 py-3 text-gray-500 italic flex items-center">
                <i class="fas fa-info-circle mr-2"></i> ไม่พบสาขาวิชาที่มีสิทธิ์เข้าถึง
            </li>
            <?php 
            else:
                while($dept = $departments->fetch_assoc()): 
            ?>
            <li class="nav-item <?php echo ($dept_id == $dept['id']) ? 'active-nav-item' : ''; ?>">
                <a href="?dept=<?php echo $dept['id']; ?>" class="block hover:text-indigo-600 px-4 py-2 rounded-lg <?php echo ($dept_id == $dept['id']) ? 'bg-indigo-50 text-indigo-600' : 'text-gray-700'; ?> transition duration-300">
                    <i class="fas fa-university mr-2"></i> <?php echo htmlspecialchars($dept['name']); ?>
                </a>
            </li>
            <?php 
                endwhile; 
            endif;
            ?>
        </ul>
    </div>
    
    <!-- รายวิชา -->
    <?php if($dept_id > 0): ?>
    <div class="sidebar p-6">
        <h2 class="text-lg font-bold mb-6 flex items-center text-indigo-700">
            <i class="fas fa-book mr-2"></i> รายวิชาในสาขา<?php echo htmlspecialchars($dept_name); ?>
        </h2>
        <ul class="space-y-3">
            <li class="nav-item <?php echo ($dept_id > 0 && $subject_id == 0) ? 'active-nav-item' : ''; ?>">
                <a href="?dept=<?php echo $dept_id; ?>" class="block hover:text-indigo-600 px-4 py-2 rounded-lg <?php echo ($dept_id > 0 && $subject_id == 0) ? 'bg-indigo-50 text-indigo-600' : 'text-gray-700'; ?> transition duration-300">
                    <i class="fas fa-th-list mr-2"></i> ทุกวิชาในสาขา
                </a>
            </li>
            <?php 
            if($subjects->num_rows == 0): 
            ?>
            <li class="px-4 py-3 text-gray-500 italic flex items-center">
                <i class="fas fa-info-circle mr-2"></i> ไม่พบรายวิชาในสาขานี้
            </li>
            <?php 
            else:
                while($subject = $subjects->fetch_assoc()): 
            ?>
            <li class="nav-item <?php echo ($subject_id == $subject['id']) ? 'active-nav-item' : ''; ?>">
                <a href="?dept=<?php echo $dept_id; ?>&subject=<?php echo $subject['id']; ?>" class="block hover:text-indigo-600 px-4 py-2 rounded-lg <?php echo ($subject_id == $subject['id']) ? 'bg-indigo-50 text-indigo-600' : 'text-gray-700'; ?> transition duration-300">
                    <i class="fas fa-book mr-2"></i> <?php echo htmlspecialchars($subject['name']); ?>
                </a>
            </li>
            <?php 
                endwhile; 
            endif;
            ?>
        </ul>
    </div>
    <?php endif; ?>
</div> 