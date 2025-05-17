<?php
// ไฟล์ API สำหรับดึงรายวิชาตามแผนก
// ชื่อไฟล์: get-subjects-by-department.php

session_start();
include 'db.php';

header('Content-Type: application/json'); // ย้ายขึ้นต้นเสมอเพื่อป้องกัน header ซ้ำ

// ตรวจสอบผู้ใช้ต้องเป็น admin เท่านั้น
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ตรวจสอบการส่งค่า department_id
if(!isset($_GET['department_id']) || !isset($_GET['user_id'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$department_id = intval($_GET['department_id']);
$user_id = intval($_GET['user_id']);

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

// ถ้าไม่มีรายวิชาเลย ให้ส่งกลับเป็น array ว่าง (หรือจะส่ง error ก็ได้)
echo json_encode($subjects);