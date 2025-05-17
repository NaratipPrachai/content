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
$stmt = $conn->prepare("SELECT * FROM banners WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$banner = $result->fetch_assoc();

if(!$banner) {
    header("Location: manage_banners.php");
    exit;
}

// จัดการการอัพเดท
if(isset($_POST['submit'])) {
    $title = $_POST['title'];
    $status = $_POST['status'];
    
    if(isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['banner_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($filetype), $allowed)) {
            // สร้างชื่อไฟล์ใหม่
            $new_filename = uniqid() . '.' . $filetype;
            $upload_path = 'uploads/banners/' . $new_filename;
            
            if(move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_path)) {
                // ลบไฟล์เก่า
                if(file_exists($banner['image_path'])) {
                    unlink($banner['image_path']);
                }
                
                // อัพเดทฐานข้อมูล
                $sql = "UPDATE banners SET title = ?, image_path = ?, status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $title, $upload_path, $status, $id);
            }
        } else {
            $error_message = "รองรับเฉพาะไฟล์รูปภาพ (JPG, JPEG, PNG, GIF)";
        }
    } else {
        // อัพเดทเฉพาะข้อมูลอื่นๆ
        $sql = "UPDATE banners SET title = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $title, $status, $id);
    }
    
    if(isset($stmt) && $stmt->execute()) {
        $success_message = "อัพเดทแบนเนอร์สำเร็จ";
        // ดึงข้อมูลใหม่
        $stmt = $conn->prepare("SELECT * FROM banners WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $banner = $result->fetch_assoc();
    } else {
        $error_message = "เกิดข้อผิดพลาดในการอัพเดทข้อมูล";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>แก้ไขแบนเนอร์ - ระบบสื่อการเรียนรู้</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <?php include 'styles.php'; ?>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <?php include 'navbar.php'; ?>
        
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <h2 class="text-2xl font-bold mb-6">แก้ไขแบนเนอร์</h2>
            
            <?php if(isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- ฟอร์มแก้ไขแบนเนอร์ -->
            <form method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">ชื่อแบนเนอร์</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($banner['title']); ?>" required class="w-full px-4 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">สถานะ</label>
                        <select name="status" class="w-full px-4 py-2 border rounded">
                            <option value="active" <?php echo $banner['status'] == 'active' ? 'selected' : ''; ?>>แสดง</option>
                            <option value="inactive" <?php echo $banner['status'] == 'inactive' ? 'selected' : ''; ?>>ซ่อน</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-gray-700 mb-2">รูปภาพแบนเนอร์ปัจจุบัน</label>
                    <img src="<?php echo htmlspecialchars($banner['image_path']); ?>" alt="Current Banner" class="w-full max-w-md h-48 object-cover mb-2">
                    <label class="block text-gray-700 mb-2">อัพโหลดรูปภาพใหม่ (ถ้าต้องการเปลี่ยน)</label>
                    <input type="file" name="banner_image" accept="image/*" class="w-full">
                </div>
                <div class="mt-4">
                    <button type="submit" name="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        บันทึกการแก้ไข
                    </button>
                    <a href="manage_banners.php" class="ml-2 bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                        ยกเลิก
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    <?php include 'scripts.php'; ?>
</body>
</html> 