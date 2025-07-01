<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// หน้าจัดการสื่อ
session_start();
include 'db.php';

// ตรวจสอบผู้ใช้ต้องเป็น admin เท่านั้น
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$message = '';
$error = '';

// เพิ่มสื่อใหม่
if(isset($_POST['add_media'])) {
    $title = htmlspecialchars(trim($_POST['title']));
    $file_type = htmlspecialchars(trim($_POST['file_type']));
    $subject_id = intval($_POST['subject_id']);
    $document_references = htmlspecialchars(trim($_POST['document_references']));
    $illustrations = '';
    $thumbnail = '';
    $google_drive_file_id = '';
    
    // เพิ่ม EP ให้กับวิดีโอ
    if($file_type === 'video') {
        // นับจำนวนวิดีโอที่มีอยู่แล้วในวิชานี้
        $stmt = $conn->prepare("SELECT COUNT(*) as video_count FROM media_files WHERE file_type = 'video' AND subject_id = ?");
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $video_count = $result->fetch_assoc()['video_count'];
        
        // เพิ่ม EP ต่อท้ายชื่อ
        $ep_number = $video_count + 1;
        $title = $title . " EP." . $ep_number;
    }
    
    // จัดการการอัพโหลดปกคลิป (thumbnail)
    if(isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/thumbnails/';
        if(!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['thumbnail_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        
        if(in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['thumbnail_file']['tmp_name'], $upload_path)) {
                $thumbnail = $upload_path;
            } else {
                $error = "เกิดข้อผิดพลาดในการอัพโหลดปกคลิป";
            }
        } else {
            $error = "นามสกุลไฟล์ปกคลิปไม่ถูกต้อง (รองรับเฉพาะ jpg, jpeg, png, gif, webp)";
        }
    }
    
    // จัดการการอัพโหลดไฟล์เอกสารอ้างอิง
    $reference_files = [];
    if(isset($_FILES['reference_files']) && is_array($_FILES['reference_files']['name'])) {
        $upload_dir = 'uploads/references/';
        if(!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        foreach($_FILES['reference_files']['tmp_name'] as $key => $tmp_name) {
            if($_FILES['reference_files']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['reference_files']['name'][$key];
                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx');
                
                if(in_array($file_extension, $allowed_extensions)) {
                    $new_filename = uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if(move_uploaded_file($tmp_name, $upload_path)) {
                        $reference_files[] = $upload_path;
                    }
                }
            }
        }
    }
    
    // จัดการการอัพโหลดรูปตัวอย่าง
    $example_images = [];
    if(isset($_FILES['example_images']) && is_array($_FILES['example_images']['name'])) {
        $upload_dir = 'uploads/examples/';
        if(!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        foreach($_FILES['example_images']['tmp_name'] as $key => $tmp_name) {
            if($_FILES['example_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['example_images']['name'][$key];
                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
                
                if(in_array($file_extension, $allowed_extensions)) {
                    $new_filename = uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if(move_uploaded_file($tmp_name, $upload_path)) {
                        $example_images[] = $upload_path;
                    }
                }
            }
        }
    }
    
    // จัดการการอัพโหลดรูปภาพ
    $google_drive_file_ids = [];
    if($file_type === 'image' && isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/images/';
        $file_extension = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if(in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_path)) {
                $google_drive_file_id = $upload_path;
            } else {
                $error = "เกิดข้อผิดพลาดในการอัพโหลดรูปภาพ";
            }
        } else {
            $error = "นามสกุลไฟล์ไม่ถูกต้อง (รองรับเฉพาะ jpg, jpeg, png, gif)";
        }
    } else {
        // รับ Google Drive IDs จากฟอร์ม
        if(isset($_POST['google_drive_file_ids']) && is_array($_POST['google_drive_file_ids'])) {
            foreach($_POST['google_drive_file_ids'] as $file_id) {
                $file_id = trim($file_id);
                if(!empty($file_id)) {
                    $google_drive_file_ids[] = htmlspecialchars($file_id);
                }
            }
            // ใช้ Google Drive ID แรกเป็นค่าเริ่มต้น
            if(!empty($google_drive_file_ids)) {
                $google_drive_file_id = $google_drive_file_ids[0];
            }
        }
    }
    
    if(isset($_FILES['illustrations_file']) && $_FILES['illustrations_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/illustrations/';
        $file_extension = strtolower(pathinfo($_FILES['illustrations_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        if(in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            if(move_uploaded_file($_FILES['illustrations_file']['tmp_name'], $upload_path)) {
                $illustrations = $upload_path;
            } else {
                $error = "เกิดข้อผิดพลาดในการอัพโหลดภาพประกอบ";
            }
        } else {
            $error = "นามสกุลไฟล์ภาพประกอบไม่ถูกต้อง (รองรับเฉพาะ jpg, jpeg, png, gif)";
        }
    }
    
    // ตรวจสอบว่าวิชามีอยู่จริงหรือไม่
    $check = $conn->prepare("SELECT 1 FROM subjects WHERE id = ?");
    $check->bind_param("i", $subject_id);
    $check->execute();
    $result = $check->get_result();
    
    if($result->num_rows == 0) {
        $error = "ไม่พบวิชาที่เลือก";
    } else if(empty($error)) {
        // เริ่ม transaction
        $conn->begin_transaction();
        
        try {
            // เพิ่มข้อมูลสื่อหลัก
            $stmt = $conn->prepare("INSERT INTO media_files (title, document_references, illustrations, thumbnail, file_type, subject_id, created_by, google_drive_file_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssiis", $title, $document_references, $illustrations, $thumbnail, $file_type, $subject_id, $_SESSION['user_id'], $google_drive_file_id);
            
            if($stmt->execute()) {
                $media_id = $conn->insert_id;
                
                // เพิ่มไฟล์เอกสารอ้างอิง
                if(!empty($reference_files)) {
                    $stmt = $conn->prepare("INSERT INTO media_references (media_id, file_path, type) VALUES (?, ?, 'file')");
                    foreach($reference_files as $file_path) {
                        $stmt->bind_param("is", $media_id, $file_path);
                        $stmt->execute();
                    }
                }
                
                // เพิ่มลิงค์เอกสารอ้างอิง
                if(isset($_POST['reference_links']) && is_array($_POST['reference_links'])) {
                    $stmt = $conn->prepare("INSERT INTO media_references (media_id, file_path, type) VALUES (?, ?, 'link')");
                    foreach($_POST['reference_links'] as $link) {
                        if(!empty(trim($link))) {
                            $stmt->bind_param("is", $media_id, $link);
                            $stmt->execute();
                        }
                    }
                }
                
                // เพิ่มรูปตัวอย่าง
                if(!empty($example_images)) {
                    $stmt = $conn->prepare("INSERT INTO media_examples (media_id, image_path) VALUES (?, ?)");
                    foreach($example_images as $image_path) {
                        $stmt->bind_param("is", $media_id, $image_path);
                        $stmt->execute();
                    }
                }
                
                // เพิ่ม Google Drive IDs ที่เหลือ (ถ้ามี)
                if(!empty($google_drive_file_ids) && count($google_drive_file_ids) > 1) {
                    $stmt = $conn->prepare("INSERT INTO media_links (media_id, google_drive_file_id) VALUES (?, ?)");
                    for($i = 1; $i < count($google_drive_file_ids); $i++) {
                        $stmt->bind_param("is", $media_id, $google_drive_file_ids[$i]);
                        $stmt->execute();
                    }
                }
                
                $conn->commit();
                $message = "เพิ่มสื่อ '$title' สำเร็จ";
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

// ลบสื่อ
if(isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    $stmt = $conn->prepare("DELETE FROM media_files WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if($stmt->execute()) {
        $message = "ลบสื่อเรียบร้อยแล้ว";
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// แก้ไขสื่อ
if(isset($_POST['edit_media'])) {
    $edit_id = intval($_POST['edit_id']);
    $edit_title = htmlspecialchars(trim($_POST['edit_title']));
    $edit_file_type = htmlspecialchars(trim($_POST['edit_file_type']));
    $edit_subject_id = intval($_POST['edit_subject_id']);
    $edit_document_references = htmlspecialchars(trim($_POST['edit_document_references']));
    $edit_illustrations = '';
    $edit_thumbnail = '';
    
    // จัดการการอัพโหลดรูปภาพ
    $edit_file_id = '';
    if($edit_file_type === 'image' && isset($_FILES['edit_image_file']) && $_FILES['edit_image_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/images/';
        $file_extension = strtolower(pathinfo($_FILES['edit_image_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if(in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['edit_image_file']['tmp_name'], $upload_path)) {
                // ลบรูปเก่าถ้ามี
                $old_file = $conn->query("SELECT google_drive_file_id FROM media_files WHERE id = $edit_id")->fetch_assoc();
                if($old_file && strpos($old_file['google_drive_file_id'], 'uploads/images/') === 0) {
                    @unlink($old_file['google_drive_file_id']);
                }
                $edit_file_id = $upload_path;
            } else {
                $error = "เกิดข้อผิดพลาดในการอัพโหลดรูปภาพ";
            }
        } else {
            $error = "นามสกุลไฟล์ไม่ถูกต้อง (รองรับเฉพาะ jpg, jpeg, png, gif)";
        }
    } else {
        $edit_file_id = htmlspecialchars(trim($_POST['edit_file_id']));
    }
    
    if(isset($_FILES['edit_illustrations_file']) && $_FILES['edit_illustrations_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/illustrations/';
        $file_extension = strtolower(pathinfo($_FILES['edit_illustrations_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        if(in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            if(move_uploaded_file($_FILES['edit_illustrations_file']['tmp_name'], $upload_path)) {
                // ลบไฟล์เก่าถ้ามี
                $old_file = $conn->query("SELECT illustrations FROM media_files WHERE id = $edit_id")->fetch_assoc();
                if($old_file && strpos($old_file['illustrations'], 'uploads/illustrations/') === 0) {
                    @unlink($old_file['illustrations']);
                }
                $edit_illustrations = $upload_path;
            } else {
                $error = "เกิดข้อผิดพลาดในการอัพโหลดภาพประกอบ";
            }
        } else {
            $error = "นามสกุลไฟล์ภาพประกอบไม่ถูกต้อง (รองรับเฉพาะ jpg, jpeg, png, gif)";
        }
    } else {
        $edit_illustrations = htmlspecialchars(trim($_POST['edit_illustrations']));
    }
    
    // จัดการการอัพโหลดปกคลิป
    if(isset($_FILES['edit_thumbnail_file']) && $_FILES['edit_thumbnail_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/thumbnails/';
        if(!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['edit_thumbnail_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        
        if(in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['edit_thumbnail_file']['tmp_name'], $upload_path)) {
                // ลบไฟล์เก่าถ้ามี
                $old_file = $conn->query("SELECT thumbnail FROM media_files WHERE id = $edit_id")->fetch_assoc();
                if($old_file && !empty($old_file['thumbnail']) && strpos($old_file['thumbnail'], 'uploads/thumbnails/') === 0) {
                    @unlink($old_file['thumbnail']);
                }
                $edit_thumbnail = $upload_path;
            } else {
                $error = "เกิดข้อผิดพลาดในการอัพโหลดปกคลิป";
            }
        } else {
            $error = "นามสกุลไฟล์ปกคลิปไม่ถูกต้อง (รองรับเฉพาะ jpg, jpeg, png, gif, webp)";
        }
    } else {
        $edit_thumbnail = htmlspecialchars(trim($_POST['edit_thumbnail']));
    }
    
    if(empty($error)) {
        $stmt = $conn->prepare("UPDATE media_files 
                SET title = ?, 
                    google_drive_file_id = ?, 
                    file_type = ?, 
                    subject_id = ?,
                    document_references = ?,
                    illustrations = ?,
                    thumbnail = ?
                WHERE id = ?");
        $stmt->bind_param("sssisssi", $edit_title, $edit_file_id, $edit_file_type, $edit_subject_id, $edit_document_references, $edit_illustrations, $edit_thumbnail, $edit_id);
        
        if($stmt->execute()) {
            $message = "แก้ไขสื่อ '$edit_title' เรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// ดึงรายวิชาทั้งหมดสำหรับ dropdown
$subjects = $conn->query("SELECT * FROM subjects ORDER BY name");

// ดึงรายการสื่อ
$filter_subject = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
$search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : '';

// เพิ่มตัวแปรสำหรับกรองตามสาขา
$filter_department = isset($_GET['department']) ? intval($_GET['department']) : 0;

// สร้าง prepared statement สำหรับการดึงข้อมูลสื่อ
$where_conditions = [];
$params = [];
$param_types = "";

// เพิ่มเงื่อนไขการกรอง
if($filter_subject > 0) {
    $where_conditions[] = "m.subject_id = ?";
    $params[] = $filter_subject;
    $param_types .= "i";
} else if($filter_department > 0) {
    $where_conditions[] = "s.department_id = ?";
    $params[] = $filter_department;
    $param_types .= "i";
}

if(!empty($search)) {
    $where_conditions[] = "m.title LIKE ?";
    $params[] = "%$search%";
    $param_types .= "s";
}

// สร้าง SQL query
$sql = "SELECT m.*, s.name as subject_name, u.username as created_by_name,
        GROUP_CONCAT(ml.google_drive_file_id SEPARATOR '|') as drive_links
        FROM media_files m
        JOIN subjects s ON m.subject_id = s.id
        LEFT JOIN users u ON m.created_by = u.id
        LEFT JOIN media_links ml ON m.id = ml.media_id";

if(!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " GROUP BY m.id ORDER BY m.title";

$stmt = $conn->prepare($sql);
if(!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$media_files = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <title>จัดการสื่อ - ระบบสื่อการเรียนรู้</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        @media (max-width: 640px) {
            .responsive-table {
                display: block;
                width: 100%;
                overflow-x: auto;
            }
        }
        
        /* เพิ่มเอฟเฟกต์เมื่อชี้ที่ปุ่ม */
        .hover-scale:hover {
            transform: scale(1.05);
            transition: transform 0.2s;
        }
        
        /* เพิ่มเอฟเฟกต์เมื่อคลิกที่ปุ่ม */
        .active-button:active {
            transform: scale(0.95);
            transition: transform 0.1s;
        }
        
        /* เพิ่มเงาให้การ์ด */
        .card-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: box-shadow 0.3s;
        }
        
        .card-shadow:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        /* เพิ่มเส้นขอบให้กับฟอร์มอินพุต */
        .input-focus:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
            outline: none;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex flex-col md:flex-row min-h-screen">
        <!-- Sidebar -->
        <?php include 'admin-sidebar.php'; ?>

        <div class="flex-1 flex flex-col">
            <!-- Navbar -->
            <?php include 'admin-navbar.php'; ?>

            <!-- Main Content -->
            <div class="p-4 md:p-6 flex-grow">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800">จัดการสื่อ</h2>
                    <a href="add-media.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg shadow-md w-full md:w-auto transition duration-300 ease-in-out hover-scale active-button flex items-center justify-center">
                        <i class="fas fa-plus mr-2"></i> เพิ่มสื่อใหม่
                    </a>
                </div>
                
                <?php if($message): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-md animate__animated animate__fadeIn">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <p><?php echo $message; ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-md animate__animated animate__fadeIn">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <p><?php echo $error; ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- ตัวกรองและค้นหา -->
                <div class="bg-white rounded-lg shadow-md p-4 mb-6 card-shadow">
                    <form method="GET" class="flex flex-col md:flex-row md:items-end gap-4">
                        <div class="w-full md:w-1/3">
                            <label class="block text-gray-700 text-sm font-bold mb-2">กรองตามสาขา</label>
                            <select name="department" id="filter_department" class="w-full px-3 py-2 border rounded-lg input-focus transition duration-300 ease-in-out" onchange="updateFilterSubject(this.value)">
                                <option value="0">-- ทั้งหมด --</option>
                                <?php 
                                $departments_result = $conn->query("SELECT * FROM departments ORDER BY name");
                                while($department = $departments_result->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $department['id']; ?>" <?php echo ($filter_department == $department['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($department['name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="w-full md:w-1/3">
                            <label class="block text-gray-700 text-sm font-bold mb-2">กรองตามวิชา</label>
                            <select name="subject" id="filter_subject" class="w-full px-3 py-2 border rounded-lg input-focus transition duration-300 ease-in-out">
                                <option value="0">-- ทั้งหมด --</option>
                                <?php 
                                if($filter_department > 0) {
                                    $subjects_filter = $conn->prepare("SELECT * FROM subjects WHERE department_id = ? ORDER BY name");
                                    $subjects_filter->bind_param("i", $filter_department);
                                    $subjects_filter->execute();
                                    $subjects_result = $subjects_filter->get_result();
                                    while($subject = $subjects_result->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $subject['id']; ?>" <?php echo ($filter_subject == $subject['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                </option>
                                <?php 
                                    endwhile;
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="w-full md:w-2/3">
                            <label class="block text-gray-700 text-sm font-bold mb-2">ค้นหาสื่อ</label>
                            <div class="flex">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ค้นหาจากชื่อสื่อ..." 
                                       class="w-full px-3 py-2 border rounded-l-lg input-focus transition duration-300 ease-in-out">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-r-lg transition duration-300 ease-in-out hover-scale active-button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- ตารางสื่อ -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden card-shadow">
                    <div class="responsive-table">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อสื่อ</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ปกคลิป</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วิชา</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Google Drive ID</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                $i = 1;
                                while($media = $media_files->fetch_assoc()): 
                                ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm"><?php echo $i++; ?></td>
                                    <td class="px-3 md:px-6 py-4 text-sm font-medium text-gray-900">
                                        <?php 
                                        if($media['file_type'] === 'video') {
                                            // แยกชื่อและ EP
                                            $title_parts = explode(" EP.", $media['title']);
                                            echo htmlspecialchars($title_parts[0]);
                                            if(isset($title_parts[1])) {
                                                echo ' <span class="text-blue-600 font-semibold">EP.' . $title_parts[1] . '</span>';
                                            }
                                        } else {
                                            echo htmlspecialchars($media['title']);
                                        }
                                        ?>
                                    </td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                                        <?php if(!empty($media['thumbnail'])): ?>
                                            <img src="<?php echo htmlspecialchars($media['thumbnail']); ?>" alt="ปกคลิป" class="w-16 h-12 object-cover rounded">
                                        <?php else: ?>
                                            <div class="w-16 h-12 bg-gray-200 rounded flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                                        <?php 
                                        switch($media['file_type']) {
                                            case 'video': echo '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs flex items-center whitespace-nowrap"><i class="fas fa-video mr-1"></i> วิดีโอ</span>'; break;
                                            case 'pdf': echo '<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs flex items-center whitespace-nowrap"><i class="fas fa-file-pdf mr-1"></i> PDF</span>'; break;
                                            case 'word': echo '<span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs flex items-center whitespace-nowrap"><i class="fas fa-file-word mr-1"></i> Word</span>'; break;
                                            case 'image': echo '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs flex items-center whitespace-nowrap"><i class="fas fa-image mr-1"></i> รูปภาพ</span>'; break;
                                            default: echo '<span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs flex items-center whitespace-nowrap"><i class="fas fa-file mr-1"></i> อื่นๆ</span>'; break;
                                        }
                                        ?>
                                    </td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($media['subject_name']); ?></td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500 truncate max-w-xs hidden md:table-cell">
                                        <?php 
                                        if(!empty($media['drive_links'])) {
                                            $links = explode('|', $media['drive_links']);
                                            foreach($links as $index => $link) {
                                                echo '<div class="mb-1">';
                                                echo '<a href="https://drive.google.com/file/d/' . htmlspecialchars($link) . '/view" target="_blank" class="text-blue-500 hover:text-blue-700">';
                                                echo 'ลิงก์ ' . ($index + 1);
                                                echo '</a>';
                                                echo '</div>';
                                            }
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center space-x-2">
                                            <a href="view_media.php?id=<?php echo $media['id']; ?>" class="text-blue-500 hover:text-blue-700 transition duration-300 bg-blue-50 hover:bg-blue-100 p-1 rounded-full" target="_blank" title="ดูสื่อ">
                                                <i class="fas fa-eye"></i><span class="sr-only">ดู</span>
                                            </a>
                                            
                                            <button class="text-indigo-500 hover:text-indigo-700 transition duration-300 bg-indigo-50 hover:bg-indigo-100 p-1 rounded-full" 
                                                    onclick="openEditModal(
                                                        <?php echo $media['id']; ?>, 
                                                        '<?php echo htmlspecialchars(addslashes($media['title'])); ?>', 
                                                        '<?php echo htmlspecialchars(addslashes($media['google_drive_file_id'])); ?>', 
                                                        '<?php echo $media['file_type']; ?>', 
                                                        <?php echo $media['subject_id']; ?>, 
                                                        '<?php echo htmlspecialchars(addslashes($media['document_references'])); ?>', 
                                                        '<?php echo htmlspecialchars(addslashes($media['illustrations'])); ?>',
                                                        '<?php echo htmlspecialchars(addslashes($media['thumbnail'] ?? '')); ?>'
                                                    )" title="แก้ไข">
                                                <i class="fas fa-edit"></i><span class="sr-only">แก้ไข</span>
                                            </button>
                                            
                                            <a href="?delete_id=<?php echo $media['id']; ?>" 
                                               class="text-red-500 hover:text-red-700 transition duration-300 bg-red-50 hover:bg-red-100 p-1 rounded-full" 
                                               onclick="return confirm('ยืนยันการลบสื่อ <?php echo htmlspecialchars(addslashes($media['title'])); ?> ?')" title="ลบ">
                                                <i class="fas fa-trash-alt"></i><span class="sr-only">ลบ</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                
                                <?php if($media_files->num_rows == 0): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-search text-gray-300 text-5xl mb-3"></i>
                                            <p>ไม่พบสื่อที่ตรงกับเงื่อนไข</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">แก้ไขสื่อ</h3>
                <form method="POST" class="space-y-4" enctype="multipart/form-data">
                    <input type="hidden" name="edit_id" id="edit_id">
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_title">ชื่อสื่อ</label>
                        <input type="text" name="edit_title" id="edit_title" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_file_type">ประเภทสื่อ</label>
                        <select name="edit_file_type" id="edit_file_type" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" required onchange="toggleEditFileInput(this.value)">
                            <option value="video">วิดีโอ</option>
                            <option value="pdf">PDF</option>
                            <option value="word">Word</option>
                            <option value="image">รูปภาพ</option>
                            <option value="other">อื่นๆ</option>
                        </select>
                    </div>
                    
                    <div id="edit_google_drive_input">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_file_id">Google Drive File ID</label>
                        <input type="text" name="edit_file_id" id="edit_file_id" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" required>
                    </div>
                    
                    <div id="edit_image_upload_input" style="display: none;">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_image_file">อัพโหลดรูปภาพ</label>
                        <input type="file" name="edit_image_file" id="edit_image_file" accept="image/*" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_document_references">เอกสารอ้างอิง</label>
                        <textarea name="edit_document_references" id="edit_document_references" rows="3" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" placeholder="ระบุเอกสารอ้างอิง (ถ้ามี)"></textarea>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_illustrations_file">ภาพประกอบ</label>
                        <input type="file" name="edit_illustrations_file" id="edit_illustrations_file" accept="image/*" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out">
                        <input type="hidden" name="edit_illustrations" id="edit_illustrations">
                        <p class="text-gray-500 text-xs mt-1">รองรับไฟล์ jpg, jpeg, png, gif ขนาดไม่เกิน 5MB</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_thumbnail_file">ปกคลิป</label>
                        <input type="file" name="edit_thumbnail_file" id="edit_thumbnail_file" accept="image/*" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out">
                        <input type="hidden" name="edit_thumbnail" id="edit_thumbnail">
                        <p class="text-gray-500 text-xs mt-1">รองรับไฟล์ jpg, jpeg, png, gif, webp ขนาดไม่เกิน 5MB</p>
                        <div id="current_thumbnail" class="mt-2"></div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_subject_id">วิชา</label>
                        <select name="edit_subject_id" id="edit_subject_id" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" required>
                            <option value="">-- เลือกรายวิชา --</option>
                            <?php 
                            $edit_subjects = $conn->query("SELECT * FROM subjects ORDER BY name");
                            while($subject = $edit_subjects->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $subject['id']; ?>">
                                <?php echo htmlspecialchars($subject['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition duration-300">
                            ยกเลิก
                        </button>
                        <button type="submit" name="edit_media" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-300">
                            บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openEditModal(id, title, fileId, fileType, subjectId, documentReferences, illustrations, thumbnail) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_file_id').value = fileId;
            document.getElementById('edit_file_type').value = fileType;
            document.getElementById('edit_subject_id').value = subjectId;
            document.getElementById('edit_document_references').value = documentReferences;
            document.getElementById('edit_illustrations').value = illustrations || '';
            document.getElementById('edit_thumbnail').value = thumbnail || '';
            
            // แสดงปกคลิปปัจจุบัน
            const currentThumbnailDiv = document.getElementById('current_thumbnail');
            if(thumbnail) {
                currentThumbnailDiv.innerHTML = `
                    <p class="text-sm text-gray-600 mb-1">ปกคลิปปัจจุบัน:</p>
                    <img src="${thumbnail}" alt="ปกคลิปปัจจุบัน" class="w-24 h-16 object-cover rounded border">
                `;
            } else {
                currentThumbnailDiv.innerHTML = '<p class="text-sm text-gray-500">ไม่มีปกคลิป</p>';
            }
            
            document.getElementById('editModal').classList.remove('hidden');
        }

        // ฟังก์ชันสำหรับแสดง/ซ่อนฟิลด์อัพโหลดไฟล์ในโหมดแก้ไข
        function toggleEditFileInput(fileType) {
            const googleDriveInput = document.getElementById('edit_google_drive_input');
            const imageUploadInput = document.getElementById('edit_image_upload_input');
            
            if(fileType === 'image') {
                googleDriveInput.style.display = 'none';
                imageUploadInput.style.display = 'block';
            } else {
                googleDriveInput.style.display = 'block';
                imageUploadInput.style.display = 'none';
            }
        }

        // เพิ่มฟังก์ชันสำหรับจัดการลิงก์ Google Drive
        document.getElementById('add_drive_link').addEventListener('click', function() {
            const container = document.getElementById('drive_links_container');
            const newLink = document.createElement('div');
            newLink.className = 'drive-link-item mb-2';
            newLink.innerHTML = `
                <div class="flex">
                    <input type="text" name="google_drive_file_ids[]" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" placeholder="เช่น 1a2b3c4d5e6f7g8h9i">
                    <button type="button" class="ml-2 px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-300 remove-link">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            container.appendChild(newLink);
            
            // แสดงปุ่มลบของลิงก์แรกถ้ามีมากกว่า 1 ลิงก์
            const removeButtons = container.getElementsByClassName('remove-link');
            if (removeButtons.length > 1) {
                removeButtons[0].style.display = 'block';
            }
        });

        // เพิ่ม event listener สำหรับปุ่มลบ
        document.getElementById('drive_links_container').addEventListener('click', function(e) {
            if (e.target.closest('.remove-link')) {
                const linkItem = e.target.closest('.drive-link-item');
                linkItem.remove();
                
                // ซ่อนปุ่มลบของลิงก์แรกถ้าเหลือแค่ 1 ลิงก์
                const removeButtons = this.getElementsByClassName('remove-link');
                if (removeButtons.length === 1) {
                    removeButtons[0].style.display = 'none';
                }
            }
        });

        // เพิ่มฟังก์ชันสำหรับจัดการลิงค์อ้างอิง
        document.getElementById('add_reference_link').addEventListener('click', function() {
            const container = document.getElementById('reference_links_container');
            const newLink = document.createElement('div');
            newLink.className = 'reference-link-item mb-2';
            newLink.innerHTML = `
                <div class="flex">
                    <input type="url" name="reference_links[]" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" placeholder="https://...">
                    <button type="button" class="ml-2 px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-300 remove-link">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            container.appendChild(newLink);
            
            // แสดงปุ่มลบของลิงค์แรกถ้ามีมากกว่า 1 ลิงค์
            const removeButtons = container.getElementsByClassName('remove-link');
            if (removeButtons.length > 1) {
                removeButtons[0].style.display = 'block';
            }
        });

        // เพิ่ม event listener สำหรับปุ่มลบลิงค์อ้างอิง
        document.getElementById('reference_links_container').addEventListener('click', function(e) {
            if (e.target.closest('.remove-link')) {
                const linkItem = e.target.closest('.reference-link-item');
                linkItem.remove();
                
                // ซ่อนปุ่มลบของลิงค์แรกถ้าเหลือแค่ 1 ลิงค์
                const removeButtons = this.getElementsByClassName('remove-link');
                if (removeButtons.length === 1) {
                    removeButtons[0].style.display = 'none';
                }
            }
        });

        // เพิ่มฟังก์ชันสำหรับจัดการรายวิชาตามสาขาที่เลือก
        function updateSubjects(departmentId) {
            const subjectSelect = document.getElementById('subject_id');
            const editSubjectSelect = document.getElementById('edit_subject_id');
            const filterSubjectSelect = document.getElementById('filter_subject');
            
            // Clear current options
            subjectSelect.innerHTML = '<option value="">-- เลือกรายวิชา --</option>';
            if(editSubjectSelect) {
                editSubjectSelect.innerHTML = '<option value="">-- เลือกรายวิชา --</option>';
            }
            if(filterSubjectSelect) {
                filterSubjectSelect.innerHTML = '<option value="0">-- ทั้งหมด --</option>';
            }
            
            if(departmentId) {
                // Fetch subjects for selected department
                fetch(`get_subjects.php?department_id=${departmentId}`)
                    .then(response => response.json())
                    .then(subjects => {
                        subjects.forEach(subject => {
                            const option = new Option(subject.name, subject.id);
                            subjectSelect.add(option);
                            if(editSubjectSelect) {
                                const editOption = new Option(subject.name, subject.id);
                                editSubjectSelect.add(editOption);
                            }
                            if(filterSubjectSelect) {
                                const filterOption = new Option(subject.name, subject.id);
                                filterSubjectSelect.add(filterOption);
                            }
                        });
                    });
            }
        }

        // เพิ่มฟังก์ชันสำหรับอัพเดท filter subject เมื่อเลือก department
        function updateFilterSubject(departmentId) {
            const filterSubjectSelect = document.getElementById('filter_subject');
            if(filterSubjectSelect) {
                filterSubjectSelect.innerHTML = '<option value="0">-- ทั้งหมด --</option>';
                if(departmentId) {
                    fetch(`get_subjects.php?department_id=${departmentId}`)
                        .then(response => response.json())
                        .then(subjects => {
                            subjects.forEach(subject => {
                                const option = new Option(subject.name, subject.id);
                                filterSubjectSelect.add(option);
                            });
                        });
                }
            }
        }
    </script>
</body>
</html>