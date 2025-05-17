<?php
session_start();
include 'db.php';

// ตรวจสอบการล็อกอินและสิทธิ์ admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id == 0) {
    header("Location: manage_banners.php");
    exit;
}

// ดึงข้อมูลแบนเนอร์
$stmt = $conn->prepare("SELECT image_path FROM banners WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$banner = $result->fetch_assoc();

if($banner) {
    // ลบไฟล์รูปภาพ
    if(file_exists($banner['image_path'])) {
        unlink($banner['image_path']);
    }
    
    // ลบข้อมูลจากฐานข้อมูล
    $stmt = $conn->prepare("DELETE FROM banners WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: manage_banners.php");
exit; 