<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php'; // เชื่อมต่อฐานข้อมูล

$username = 'user1'; // 🔧
$new_password = '123456'; // 🔒

$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// ตรวจสอบว่าผู้ใช้มีจริงก่อน
$result = $conn->query("SELECT * FROM users WHERE username = '$username'");
if ($result->num_rows == 0) {
    die("ไม่พบผู้ใช้ '$username'");
}

// อัปเดตรหัสผ่าน
$sql = "UPDATE users SET password_hash = '$new_password_hash' WHERE username = '$username'";
if ($conn->query($sql) === TRUE) {
    echo "เปลี่ยนรหัสผ่านของ '$username' สำเร็จแล้ว";
} else {
    echo "เกิดข้อผิดพลาด: " . $conn->error;
}

$conn->close();
