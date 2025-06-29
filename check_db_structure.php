<?php
include 'db.php';

// แสดงข้อผิดพลาดทั้งหมด
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>ตรวจสอบโครงสร้างตาราง media_files</h2>";

$result = $conn->query("DESCRIBE media_files");
if($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "ไม่สามารถดึงข้อมูลโครงสร้างตารางได้: " . $conn->error;
}

echo "<h3>ตรวจสอบข้อมูลสื่อที่มี thumbnail</h3>";
$thumb_check = $conn->query("SELECT id, title, thumbnail FROM media_files WHERE thumbnail IS NOT NULL AND thumbnail != '' LIMIT 5");
if($thumb_check && $thumb_check->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Title</th><th>Thumbnail</th></tr>";
    while($row = $thumb_check->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['title'] . "</td>";
        echo "<td>" . $row['thumbnail'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "ยังไม่มีสื่อใดที่มี thumbnail";
}

$conn->close();
?>
