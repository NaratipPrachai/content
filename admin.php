<?php
// หน้าจัดการระบบสำหรับแอดมิน
session_start();
include 'db.php';

// ตรวจสอบการล็อกอินและสิทธิ์แอดมิน
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';
$message = '';

// จัดการการเพิ่ม/แก้ไข/ลบข้อมูล
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // จัดการผู้ใช้
    if(isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        // ตรวจสอบว่ามีชื่อผู้ใช้นี้อยู่แล้วหรือไม่
        $check_sql = "SELECT 1 FROM users WHERE username = '$username'";
        $check_result = $conn->query($check_sql);
        
        if($check_result->num_rows > 0) {
            $message = "มีชื่อผู้ใช้นี้ในระบบแล้ว";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password_hash, role) VALUES ('$username', '$password_hash', '$role')";
            
            if($conn->query($sql)) {
                $message = "เพิ่มผู้ใช้สำเร็จ";
            } else {
                $message = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        }
    }
    // จัดการวิชา
    else if(isset($_POST['add_subject'])) {
        $subject_name = $_POST['subject_name'];
        
        $sql = "INSERT INTO subjects (name) VALUES ('$subject_name')";
        
        if($conn->query($sql)) {
            $message = "เพิ่มวิชาสำเร็จ";
        } else {
            $message = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
    // จัดการสื่อ
    else if(isset($_POST['add_media'])) {
        $title = $_POST['title'];
        $file_id = $_POST['google_drive_file_id'];
        $file_type = $_POST['file_type'];
        $subject_id = $_POST['subject_id'];
        
        $sql = "INSERT INTO media_files (title, google_drive_file_id, file_type, subject_id, created_by) 
                VALUES ('$title', '$file_id', '$file_type', $subject_id, {$_SESSION['user_id']})";
        
        if($conn->query($sql)) {
            $message = "เพิ่มสื่อสำเร็จ";
        } else {
            $message = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
    // จัดการสิทธิ์
    else if(isset($_POST['assign_subject'])) {
        $user_id = $_POST['user_id'];
        $subject_id = $_POST['subject_id'];
        
        // ตรวจสอบว่ามีการกำหนดสิทธิ์นี้แล้วหรือไม่
        $check_sql = "SELECT 1 FROM user_subjects WHERE user_id = $user_id AND subject_id = $subject_id";
        $check_result = $conn->query($check_sql);
        
        if($check_result->num_rows > 0) {
            $message = "ผู้ใช้มีสิทธิ์ในวิชานี้อยู่แล้ว";
        } else {
            $sql = "INSERT INTO user_subjects (user_id, subject_id) VALUES ($user_id, $subject_id)";
            
            if($conn->query($sql)) {
                $message = "กำหนดสิทธิ์สำเร็จ";
            } else {
                $message = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        }
    }
}

// ลบข้อมูล
if(isset($_GET['delete'])) {
    $type = $_GET['delete'];
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if($id > 0) {
        switch($type) {
            case 'user':
                $sql = "DELETE FROM users WHERE id = $id AND id != {$_SESSION['user_id']}";
                break;
                
            case 'subject':
                // ลบข้อมูลที่เกี่ยวข้องก่อน
                $conn->query("DELETE FROM user_subjects WHERE subject_id = $id");
                $conn->query("DELETE FROM media_files WHERE subject_id = $id");
                $sql = "DELETE FROM subjects WHERE id = $id";
                break;
                
            case 'media':
                $sql = "DELETE FROM media_files WHERE id = $id";
                break;
                
            case 'permission':
                $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
                $subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
                if($user_id > 0 && $subject_id > 0) {
                    $sql = "DELETE FROM user_subjects WHERE user_id = $user_id AND subject_id = $subject_id";
                }
                break;
        }
        
        if(isset($sql) && $conn->query($sql)) {
            $message = "ลบข้อมูลสำเร็จ";
        } else {
            $message = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// ดึงข้อมูลผู้ใช้
$users_sql = "SELECT * FROM users ORDER BY username";
$users_result = $conn->query($users_sql);

// ดึงข้อมูลวิชา
$subjects_sql = "SELECT * FROM subjects ORDER BY name";
$subjects_result = $conn->query($subjects_sql);

// ดึงข้อมูลสื่อ
$media_sql = "SELECT m.*, s.name as subject_name, u.username as created_by_name
              FROM media_files m
              JOIN subjects s ON m.subject_id = s.id
              LEFT JOIN users u ON m.created_by = u.id
              ORDER BY m.title";
$media_result = $conn->query($media_sql);

// ดึงข้อมูลสิทธิ์
$permissions_sql = "SELECT us.*, u.username, s.name as subject_name
                   FROM user_subjects us
                   JOIN users u ON us.user_id = u.id
                   JOIN subjects s ON us.subject_id = s.id
                   ORDER BY u.username, s.name";
$permissions_result = $conn->query($permissions_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>จัดการระบบ - ระบบสื่อการเรียนรู้</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">จัดการระบบ</h1>
            <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">กลับหน้าหลัก</a>
        </div>
        
        <?php if($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white p-4 rounded shadow mb-6">
            <div class="flex border-b">
                <button class="px-4 py-2 border-b-2 <?php echo $action == 'dashboard' ? 'border-blue-500 text-blue-500' : 'border-transparent hover:text-blue-500'; ?>" onclick="window.location.href='?action=dashboard'">ภาพรวม</button>
                <button class="px-4 py-2 border-b-2 <?php echo $action == 'users' ? 'border-blue-500 text-blue-500' : 'border-transparent hover:text-blue-500'; ?>" onclick="window.location.href='?action=users'">จัดการผู้ใช้</button>