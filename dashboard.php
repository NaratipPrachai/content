<?php
// หน้าแสดงสื่อที่ผู้ใช้มีสิทธิ์เข้าถึง โดยแบ่งตามสาขาวิชา
session_start();
include 'db.php';

// แสดงข้อผิดพลาดทั้งหมด
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ตรวจสอบการล็อกอิน
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// ดึงข้อมูลสาขาวิชาที่ผู้ใช้มีสิทธิ์เข้าถึง (สำหรับ admin ดูได้ทั้งหมด)
if($role == 'admin') {
    // แสดงรายการสาขาวิชาทั้งหมด
    $dept_sql = "SELECT * FROM departments ORDER BY name";
} else {
    // แก้ไขการดึงข้อมูลแผนกให้แสดงเฉพาะที่ผู้ใช้มีสิทธิ์
    $dept_sql = "SELECT DISTINCT d.* FROM departments d 
                INNER JOIN user_departments ud ON d.id = ud.department_id
                WHERE ud.user_id = $user_id
                ORDER BY d.name";
}
$departments = $conn->query($dept_sql);

// ดึงข้อมูลสาขาวิชาที่เลือก
$dept_id = isset($_GET['dept']) ? intval($_GET['dept']) : 0;
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
        // แก้ไขการดึงข้อมูลวิชาให้แสดงเฉพาะที่ผู้ใช้มีสิทธิ์
        $subject_sql = "SELECT DISTINCT s.* FROM subjects s
                        INNER JOIN user_subjects us ON s.id = us.subject_id
                        WHERE s.department_id = $dept_id 
                        AND us.user_id = $user_id
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
        // แก้ไขการดึงข้อมูลวิชาทั้งหมดให้แสดงเฉพาะที่ผู้ใช้มีสิทธิ์
        $subject_sql = "SELECT DISTINCT s.*, d.name as department_name 
                        FROM subjects s
                        JOIN departments d ON s.department_id = d.id
                        INNER JOIN user_subjects us ON s.id = us.subject_id
                        WHERE us.user_id = $user_id
                        AND s.id NOT IN (
                            SELECT subject_id FROM subject_exclusions WHERE user_id = $user_id
                        )
                        ORDER BY d.name, s.name";
    }
}
$subjects = $conn->query($subject_sql);

// ดึงข้อมูลสื่อตามสาขาวิชาและวิชาที่เลือก
$subject_id = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
$media_files = [];

// กรณีเลือกสาขาวิชาแต่ไม่ได้เลือกรายวิชา
if($dept_id > 0 && $subject_id == 0) {
    if($role == 'admin') {
        $media_sql = "SELECT m.*, s.name as subject_name 
                      FROM media_files m
                      JOIN subjects s ON m.subject_id = s.id
                      WHERE s.department_id = $dept_id
                      ORDER BY s.name, m.title";
    } else {
        $media_sql = "SELECT m.*, s.name as subject_name 
                      FROM media_files m
                      JOIN subjects s ON m.subject_id = s.id
                      INNER JOIN user_subjects us ON s.id = us.subject_id
                      WHERE s.department_id = $dept_id
                      AND us.user_id = $user_id
                      AND s.id NOT IN (
                        SELECT subject_id FROM subject_exclusions WHERE user_id = $user_id
                      )
                      ORDER BY s.name, m.title";
    }
    
    $media_result = $conn->query($media_sql);
    while($row = $media_result->fetch_assoc()) {
        $media_files[] = $row;
    }
}
// กรณีเลือกรายวิชาเฉพาะ
else if($subject_id > 0) {
    // ตรวจสอบสิทธิ์ก่อนแสดงสื่อ
    $can_access = false;
    
    if($role == 'admin') {
        $can_access = true;
    } else {
        // ตรวจสอบสิทธิ์จากทั้งสาขาวิชาและรายวิชา และตรวจสอบว่าไม่ถูกยกเว้น
        $check_sql = "SELECT 1 FROM subjects s
                    LEFT JOIN user_subjects us ON s.id = us.subject_id
                    LEFT JOIN user_departments ud ON s.department_id = ud.department_id
                    WHERE s.id = $subject_id
                    AND (us.user_id = $user_id OR ud.user_id = $user_id)
                    AND s.id NOT IN (
                        SELECT subject_id FROM subject_exclusions WHERE user_id = $user_id
                    )";
        $check_result = $conn->query($check_sql);
        $can_access = ($check_result->num_rows > 0);
    }
    
    if($can_access) {
        $media_sql = "SELECT m.*, s.name as subject_name 
                    FROM media_files m
                    JOIN subjects s ON m.subject_id = s.id
                    WHERE m.subject_id = $subject_id
                    ORDER BY m.title";
        $media_result = $conn->query($media_sql);
        while($row = $media_result->fetch_assoc()) {
            $media_files[] = $row;
        }
    }
}

// ค้นหาสื่อ
$search = isset($_GET['search']) ? $_GET['search'] : '';
if(!empty($search)) {
    if($role == 'admin') {
        $search_sql = "SELECT m.*, s.name as subject_name, d.name as department_name 
                      FROM media_files m 
                      JOIN subjects s ON m.subject_id = s.id
                      JOIN departments d ON s.department_id = d.id
                      WHERE m.title LIKE '%$search%'
                      ORDER BY d.name, s.name, m.title";
    } else {
        $search_sql = "SELECT m.*, s.name as subject_name, d.name as department_name 
                      FROM media_files m 
                      JOIN subjects s ON m.subject_id = s.id
                      JOIN departments d ON s.department_id = d.id
                      INNER JOIN user_subjects us ON s.id = us.subject_id
                      WHERE us.user_id = $user_id
                      AND m.title LIKE '%$search%'
                      AND s.id NOT IN (
                        SELECT subject_id FROM subject_exclusions WHERE user_id = $user_id
                      )
                      ORDER BY d.name, s.name, m.title";
    }
    $search_result = $conn->query($search_sql);
    $media_files = [];
    while($row = $search_result->fetch_assoc()) {
        $media_files[] = $row;
    }
}

// เพิ่มระบบ pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;

// จำกัดจำนวนรายการต่อหน้าที่อนุญาต
$allowed_per_page = [12, 24, 48, 96];
if(!in_array($per_page, $allowed_per_page)) {
    $per_page = 12;
}

$offset = ($page - 1) * $per_page;

// ดึงข้อมูลสื่อที่ผู้ใช้มีสิทธิ์เข้าถึง
$media_query = "SELECT DISTINCT m.*, s.name as subject_name, d.name as department_name, 
        u.username as created_by_name,
        GROUP_CONCAT(ml.google_drive_file_id SEPARATOR '|') as drive_links
        FROM media_files m
        JOIN subjects s ON m.subject_id = s.id
        JOIN departments d ON s.department_id = d.id
        LEFT JOIN users u ON m.created_by = u.id
        LEFT JOIN media_links ml ON m.id = ml.media_id
        WHERE 1=1 ";

// Query สำหรับนับจำนวนทั้งหมด
$count_query = "SELECT COUNT(DISTINCT m.id) as total
        FROM media_files m
        JOIN subjects s ON m.subject_id = s.id
        JOIN departments d ON s.department_id = d.id
        LEFT JOIN users u ON m.created_by = u.id
        LEFT JOIN media_links ml ON m.id = ml.media_id
        WHERE 1=1 ";

if($role !== 'admin') {
    $permission_condition = "AND s.id IN (
        SELECT subject_id FROM user_subjects WHERE user_id = ?
        UNION
        SELECT s.id FROM subjects s
        JOIN user_departments ud ON s.department_id = ud.department_id
        WHERE ud.user_id = ?
    )
    AND s.id NOT IN (
        SELECT subject_id FROM subject_exclusions WHERE user_id = ?
    ) ";
    
    $media_query .= $permission_condition;
    $count_query .= $permission_condition;
}

// เพิ่มเงื่อนไขการค้นหา
if(!empty($search)) {
    $search_condition = "AND m.title LIKE ? ";
    $media_query .= $search_condition;
    $count_query .= $search_condition;
}

// เพิ่มเงื่อนไขสาขาวิชา
if($dept_id > 0) {
    $dept_condition = "AND s.department_id = ? ";
    $media_query .= $dept_condition;
    $count_query .= $dept_condition;
}

// เพิ่มเงื่อนไขวิชา
if($subject_id > 0) {
    $subject_condition = "AND m.subject_id = ? ";
    $media_query .= $subject_condition;
    $count_query .= $subject_condition;
}

// เพิ่ม GROUP BY และ ORDER BY ลงใน media_query
$media_query .= "GROUP BY m.id ORDER BY m.title LIMIT ? OFFSET ?";

// ดึงจำนวนรายการทั้งหมดก่อน
$count_stmt = $conn->prepare($count_query);

// Bind parameters สำหรับ count query
$count_params = [];
$count_types = "";

if($role !== 'admin') {
    $count_params[] = $user_id;
    $count_params[] = $user_id;
    $count_params[] = $user_id;
    $count_types .= "iii";
}

if(!empty($search)) {
    $search_param = "%$search%";
    $count_params[] = $search_param;
    $count_types .= "s";
}

if($dept_id > 0) {
    $count_params[] = $dept_id;
    $count_types .= "i";
}

if($subject_id > 0) {
    $count_params[] = $subject_id;
    $count_types .= "i";
}

if(!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}

$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// ดึงข้อมูลสื่อพร้อม pagination
$stmt = $conn->prepare($media_query);

// Bind parameters สำหรับ main query
$params = [];
$types = "";

if($role !== 'admin') {
    $params[] = $user_id;
    $params[] = $user_id;
    $params[] = $user_id;
    $types .= "iii";
}

if(!empty($search)) {
    $search_param = "%$search%";
    $params[] = $search_param;
    $types .= "s";
}

if($dept_id > 0) {
    $params[] = $dept_id;
    $types .= "i";
}

if($subject_id > 0) {
    $params[] = $subject_id;
    $types .= "i";
}

// เพิ่ม parameters สำหรับ LIMIT และ OFFSET
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

if(!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$media_files = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <title>หน้าหลัก - ระบบสื่อการเรียนรู้</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <?php include 'styles.php'; ?>
</head>
<body class="bg-gray-100 custom-scrollbar" oncontextmenu="return false;">
    <div class="wave-bg"></div>
    
    <div class="container mx-auto px-4 py-8">
        <?php if(isset($_SESSION['expiry_warning'])): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-md flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <span><?php echo $_SESSION['expiry_warning']; ?></span>
        </div>
        <?php unset($_SESSION['expiry_warning']); ?>
        <?php endif; ?>
        
        <?php include 'navbar.php'; ?>
        <?php include 'header_banner.php'; ?>
        
        <!-- ค้นหา -->
        <div class="mb-8">
            <form method="GET" class="search-container flex">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ค้นหาสื่อการเรียนรู้..." class="flex-1 px-6 py-3 border-0 focus:ring-2 focus:ring-indigo-500 focus:outline-none text-gray-700">
                <button type="submit" class="btn-primary text-white px-6 py-3 flex items-center">
                    <i class="fas fa-search mr-2"></i> ค้นหา
                </button>
            </form>
        </div>
        
        <div class="flex flex-col md:flex-row gap-6">
            <?php include 'sidebar.php'; ?>
            <?php include 'media_content.php'; ?>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    <?php include 'scripts.php'; ?>
</body>
</html>