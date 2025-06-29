<?php
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
        $stmt = $conn->prepare("SELECT COUNT(*) as video_count FROM media_files WHERE file_type = 'video' AND subject_id = ?");
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $video_count = $result->fetch_assoc()['video_count'];
        $ep_number = $video_count + 1;
        $title = $title . " EP." . $ep_number;
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
    $google_drive_ep_names = [];
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
        if(isset($_POST['google_drive_file_ids']) && is_array($_POST['google_drive_file_ids'])) {
            foreach($_POST['google_drive_file_ids'] as $idx => $file_id) {
                $file_id = trim($file_id);
                $file_id = extract_drive_file_id($file_id); // ดึงเฉพาะ file_id
                $ep_name = isset($_POST['google_drive_ep_names'][$idx]) ? trim($_POST['google_drive_ep_names'][$idx]) : '';
                if(!empty($file_id)) {
                    $google_drive_file_ids[] = htmlspecialchars($file_id);
                    $google_drive_ep_names[] = htmlspecialchars($ep_name);
                }
            }
            if(!empty($google_drive_file_ids)) {
                $google_drive_file_id = $google_drive_file_ids[0];
            }
        }
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
    
    if(empty($error)) {
        $conn->begin_transaction();
        
        try {
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
                
                // เพิ่ม Google Drive IDs ที่เหลือ
                if(!empty($google_drive_file_ids) && count($google_drive_file_ids) > 0) {
                    $stmt = $conn->prepare("INSERT INTO media_links (media_id, google_drive_file_id, ep_name) VALUES (?, ?, ?)");
                    for($i = 0; $i < count($google_drive_file_ids); $i++) {
                        $stmt->bind_param("iss", $media_id, $google_drive_file_ids[$i], $google_drive_ep_names[$i]);
                        $stmt->execute();
                    }
                }
                
                $conn->commit();
                $message = "เพิ่มสื่อ '$title' สำเร็จ";
                
                // รีเซ็ตฟอร์ม
                $_POST = array();
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

// ดึงรายวิชาทั้งหมดสำหรับ dropdown
$subjects = $conn->query("SELECT * FROM subjects ORDER BY name");

// เพิ่มฟังก์ชันดึง file_id จากลิงก์ Google Drive
function extract_drive_file_id($url) {
    if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }
    if (preg_match('/id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }
    return $url;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <title>เพิ่มสื่อใหม่ - ระบบสื่อการเรียนรู้</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .hover-scale:hover {
            transform: scale(1.05);
            transition: transform 0.2s;
        }
        
        .active-button:active {
            transform: scale(0.95);
            transition: transform 0.1s;
        }
        
        .card-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: box-shadow 0.3s;
        }
        
        .card-shadow:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
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
                    <h2 class="text-2xl font-bold text-gray-800">เพิ่มสื่อใหม่</h2>
                    <a href="admin-media.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg shadow-md w-full md:w-auto transition duration-300 ease-in-out hover-scale active-button flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-2"></i> กลับไปหน้าจัดการสื่อ
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
                
                <div class="bg-white rounded-lg shadow-md p-6 card-shadow">
                    <form method="POST" class="space-y-6" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="title">ชื่อสื่อ</label>
                                <input type="text" name="title" id="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                       class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" required>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="department_id">สาขาวิชา</label>
                                <select name="department_id" id="department_id" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" required onchange="updateSubjects(this.value)">
                                    <option value="">-- เลือกสาขาวิชา --</option>
                                    <?php 
                                    $departments = $conn->query("SELECT * FROM departments ORDER BY name");
                                    while($department = $departments->fetch_assoc()): 
                                    ?>
                                    <option value="<?php echo $department['id']; ?>" <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $department['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($department['name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="subject_id">รายวิชา</label>
                            <select name="subject_id" id="subject_id" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" required>
                                <option value="">-- เลือกรายวิชา --</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="file_type">ประเภทสื่อ</label>
                            <select name="file_type" id="file_type" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" required onchange="toggleFileInput(this.value)">
                                <option value="video" <?php echo (isset($_POST['file_type']) && $_POST['file_type'] == 'video') ? 'selected' : ''; ?>>วิดีโอ</option>
                                <option value="pdf" <?php echo (isset($_POST['file_type']) && $_POST['file_type'] == 'pdf') ? 'selected' : ''; ?>>PDF</option>
                                <option value="word" <?php echo (isset($_POST['file_type']) && $_POST['file_type'] == 'word') ? 'selected' : ''; ?>>Word</option>
                                <option value="image" <?php echo (isset($_POST['file_type']) && $_POST['file_type'] == 'image') ? 'selected' : ''; ?>>รูปภาพ</option>
                                <option value="other" <?php echo (isset($_POST['file_type']) && $_POST['file_type'] == 'other') ? 'selected' : ''; ?>>อื่นๆ</option>
                            </select>
                        </div>
                        
                        <div id="google_drive_input">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Google Drive File ID</label>
                            <div id="drive_links_container">
                                <div class="drive-link-item mb-2 flex gap-2">
                                    <input type="text" name="google_drive_file_ids[]" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" placeholder="Google Drive File ID">
                                    <input type="text" name="google_drive_ep_names[]" class="w-24 px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" placeholder="EP.1" value="EP.1">
                                    <button type="button" class="ml-2 px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-300 remove-link" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" id="add_drive_link" class="mt-2 px-3 py-1 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-300 text-sm">
                                <i class="fas fa-plus mr-1"></i> เพิ่มลิงก์
                            </button>
                            <p class="text-gray-500 text-xs mt-1">ดูได้จาก URL ของไฟล์ (https://drive.google.com/file/d/FILE_ID/view)</p>
                        </div>
                        
                        <div id="image_upload_input" style="display: none;">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="image_file">อัพโหลดรูปภาพ</label>
                            <input type="file" name="image_file" id="image_file" accept="image/*" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out">
                            <p class="text-gray-500 text-xs mt-1">รองรับไฟล์ jpg, jpeg, png, gif ขนาดไม่เกิน 5MB</p>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">เอกสารอ้างอิง</label>
                            <div class="space-y-4">
                                <!-- ส่วนอัพโหลดไฟล์ -->
                                <div>
                                    <label class="block text-gray-600 text-sm mb-2">อัพโหลดไฟล์</label>
                                    <input type="file" name="reference_files[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" 
                                           class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out">
                                    <p class="text-gray-500 text-xs mt-1">รองรับไฟล์ PDF, Word, Excel, PowerPoint</p>
                                </div>
                                
                                <!-- ส่วนลิงค์ -->
                                <div>
                                    <label class="block text-gray-600 text-sm mb-2">ลิงค์อ้างอิง</label>
                                    <div id="reference_links_container">
                                        <div class="reference-link-item mb-2">
                                            <div class="flex">
                                                <input type="url" name="reference_links[]" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" placeholder="https://...">
                                                <button type="button" class="ml-2 px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-300 remove-link" style="display: none;">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" id="add_reference_link" class="mt-2 px-3 py-1 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-300 text-sm">
                                        <i class="fas fa-plus mr-1"></i> เพิ่มลิงค์
                                    </button>
                                </div>
                                
                                <!-- ส่วนรูปตัวอย่าง -->
                                <div>
                                    <label class="block text-gray-600 text-sm mb-2">รูปแสดงตัวอย่าง</label>
                                    <input type="file" name="example_images[]" multiple accept="image/*" 
                                           class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out">
                                    <p class="text-gray-500 text-xs mt-1">รองรับไฟล์ jpg, jpeg, png, gif</p>
                                </div>

                                <!-- ส่วนปกคลิป -->
                                <div>
                                    <label class="block text-gray-600 text-sm mb-2">ปกคลิป</label>
                                    <input type="file" name="thumbnail_file" accept="image/*" 
                                           class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out">
                                    <p class="text-gray-500 text-xs mt-1">รองรับไฟล์ jpg, jpeg, png, gif, webp ขนาดไม่เกิน 5MB</p>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="illustrations_file">ภาพประกอบ</label>
                            <input type="file" name="illustrations_file" id="illustrations_file" accept="image/*" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out">
                            <p class="text-gray-500 text-xs mt-1">รองรับไฟล์ jpg, jpeg, png, gif ขนาดไม่เกิน 5MB</p>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <a href="admin-media.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg shadow-sm transition duration-300 ease-in-out hover-scale active-button">
                                ยกเลิก
                            </a>
                            <button type="submit" name="add_media" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg shadow-sm transition duration-300 ease-in-out hover-scale active-button">
                                <i class="fas fa-save mr-1"></i> เพิ่มสื่อ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function updateSubjects(departmentId) {
            const subjectSelect = document.getElementById('subject_id');
            
            // Clear current options
            subjectSelect.innerHTML = '<option value="">-- เลือกรายวิชา --</option>';
            
            if(departmentId) {
                // Fetch subjects for selected department
                fetch(`get_subjects.php?department_id=${departmentId}`)
                    .then(response => response.json())
                    .then(subjects => {
                        subjects.forEach(subject => {
                            const option = new Option(subject.name, subject.id);
                            subjectSelect.add(option);
                        });
                    });
            }
        }

        function toggleFileInput(fileType) {
            const googleDriveInput = document.getElementById('google_drive_input');
            const imageUploadInput = document.getElementById('image_upload_input');
            
            if(fileType === 'image') {
                googleDriveInput.style.display = 'none';
                imageUploadInput.style.display = 'block';
            } else {
                googleDriveInput.style.display = 'block';
                imageUploadInput.style.display = 'none';
            }
        }
        
        // เพิ่มฟังก์ชันสำหรับจัดการลิงค์ Google Drive
        document.getElementById('add_drive_link').addEventListener('click', function() {
            const container = document.getElementById('drive_links_container');
            const count = container.getElementsByClassName('drive-link-item').length + 1;
            const newLink = document.createElement('div');
            newLink.className = 'drive-link-item mb-2 flex gap-2';
            newLink.innerHTML = `
                <input type="text" name="google_drive_file_ids[]" class="w-full px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" placeholder="Google Drive File ID">
                <input type="text" name="google_drive_ep_names[]" class="w-24 px-3 py-2 border rounded-lg shadow-sm input-focus transition duration-300 ease-in-out" placeholder="EP.${count}" value="EP.${count}">
                <button type="button" class="ml-2 px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-300 remove-link">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(newLink);
            
            // แสดงปุ่มลบของลิงค์แรกถ้ามีมากกว่า 1 ลิงค์
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
                
                // ซ่อนปุ่มลบของลิงค์แรกถ้าเหลือแค่ 1 ลิงค์
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
        
        // เรียกใช้ toggleFileInput เมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            const fileType = document.getElementById('file_type').value;
            toggleFileInput(fileType);
            
            // ถ้ามีการเลือกสาขาไว้แล้ว ให้โหลดรายวิชาของสาขานั้น
            const departmentId = document.getElementById('department_id').value;
            if(departmentId) {
                updateSubjects(departmentId);
            }
        });
    </script>
</body>
</html>