<?php
include 'db.php';

// ตรวจสอบว่ามีการส่ง department_id มาหรือไม่
if(!isset($_GET['department_id']) || !is_numeric($_GET['department_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid department ID']);
    exit;
}

$department_id = intval($_GET['department_id']);

// ดึงรายวิชาตามสาขาที่เลือก
$stmt = $conn->prepare("SELECT id, name FROM subjects WHERE department_id = ? ORDER BY name");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while($row = $result->fetch_assoc()) {
    $subjects[] = [
        'id' => $row['id'],
        'name' => $row['name']
    ];
}

// ส่งข้อมูลกลับเป็น JSON
header('Content-Type: application/json');
echo json_encode($subjects); 