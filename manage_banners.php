<?php
session_start();
include 'db.php';

// ตรวจสอบการล็อกอินและสิทธิ์ admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// จัดการการอัพโหลดรูปภาพ
if(isset($_POST['submit'])) {
    $title = $_POST['title'];
    $status = $_POST['status'];
    
    // ตรวจสอบไฟล์รูปภาพ
    if(isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['banner_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($filetype), $allowed)) {
            // สร้างชื่อไฟล์ใหม่
            $new_filename = uniqid() . '.' . $filetype;
            $upload_path = 'uploads/banners/' . $new_filename;
            
            if(move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_path)) {
                // บันทึกลงฐานข้อมูล
                $sql = "INSERT INTO banners (title, image_path, status) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $title, $upload_path, $status);
                
                if($stmt->execute()) {
                    $success_message = "เพิ่มแบนเนอร์สำเร็จ";
                } else {
                    $error_message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
                }
            } else {
                $error_message = "เกิดข้อผิดพลาดในการอัพโหลดไฟล์";
            }
        } else {
            $error_message = "รองรับเฉพาะไฟล์รูปภาพ (JPG, JPEG, PNG, GIF)";
        }
    }
}

// ดึงข้อมูลแบนเนอร์ทั้งหมด
$banners = $conn->query("SELECT * FROM banners ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>จัดการแบนเนอร์ - ระบบสื่อการเรียนรู้</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <?php include 'styles.php'; ?>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <?php include 'admin-sidebar.php'; ?>
        
        <div class="flex-1 p-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-6">จัดการแบนเนอร์</h2>
                
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
                
                <!-- ฟอร์มเพิ่มแบนเนอร์ -->
                <form method="POST" enctype="multipart/form-data" class="mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">ชื่อแบนเนอร์</label>
                            <input type="text" name="title" required class="w-full px-4 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">สถานะ</label>
                            <select name="status" class="w-full px-4 py-2 border rounded">
                                <option value="active">แสดง</option>
                                <option value="inactive">ซ่อน</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-gray-700 mb-2">รูปภาพแบนเนอร์</label>
                        <input type="file" name="banner_image" required accept="image/*" class="w-full">
                    </div>
                    <button type="submit" name="submit" class="mt-4 bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        เพิ่มแบนเนอร์
                    </button>
                </form>
                
                <!-- แสดงรายการแบนเนอร์ -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php while($banner = $banners->fetch_assoc()): ?>
                        <div class="border rounded p-4">
                            <img src="<?php echo htmlspecialchars($banner['image_path']); ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>" class="w-full h-48 object-cover mb-2">
                            <h3 class="font-bold"><?php echo htmlspecialchars($banner['title']); ?></h3>
                            <p class="text-sm text-gray-600">สถานะ: <?php echo $banner['status'] == 'active' ? 'แสดง' : 'ซ่อน'; ?></p>
                            <div class="mt-2">
                                <a href="edit_banner.php?id=<?php echo $banner['id']; ?>" class="text-blue-500 hover:text-blue-700 mr-2">แก้ไข</a>
                                <a href="delete_banner.php?id=<?php echo $banner['id']; ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบแบนเนอร์นี้?')">ลบ</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html> 