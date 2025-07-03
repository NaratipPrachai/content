<?php
// ไฟล์ API สำหรับดึงรายวิชาตามแผนก
// ชื่อไฟล์: get-subjects-by-department.php

// เปิดการแสดงข้อผิดพลาดสำหรับ debug (ปิดในโปรดักชัน)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// ตั้งค่า header JSON ก่อนทำอะไรอื่น
header('Content-Type: application/json; charset=utf-8');

try {
    include 'db.php';

    // ตรวจสอบผู้ใช้ต้องเป็น admin เท่านั้น
    if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['error' => 'Unauthorized access']);
        exit;
    }

    // ตรวจสอบการส่งค่า department_id
    if(!isset($_GET['department_id']) || !isset($_GET['user_id'])) {
        echo json_encode(['error' => 'Missing required parameters (department_id or user_id)']);
        exit;
    }

    $department_id = intval($_GET['department_id']);
    $user_id = intval($_GET['user_id']);

    if($department_id <= 0 || $user_id <= 0) {
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }

    // ดึงรายวิชาในแผนกที่ผู้ใช้ยังไม่มีสิทธิ์เข้าถึง
    $subjects_sql = "SELECT s.* FROM subjects s 
                   WHERE s.department_id = $department_id 
                   AND s.id NOT IN (
                       SELECT subject_id FROM user_subjects WHERE user_id = $user_id
                   )
                   ORDER BY s.code, s.name";

    $subjects_result = $conn->query($subjects_sql);

    if(!$subjects_result) {
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
        exit;
    }

    $subjects = [];
    while($subject = $subjects_result->fetch_assoc()) {
        $subjects[] = [
            'id' => $subject['id'],
            'code' => $subject['code'],
            'name' => $subject['name']
        ];
    }

    // ส่งกลับรายการวิชา
    echo json_encode($subjects);

} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>