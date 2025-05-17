<?php
// หน้าแรก - เป็นหน้าล็อกอิน
session_start();
include 'db.php';

// ถ้าล็อกอินแล้วให้ไปหน้า dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

// ตรวจสอบการล็อกอิน
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // ป้องกัน SQL Injection ด้วย prepared statement
    $stmt = $conn->prepare("SELECT id, password_hash, role, name_1, expired_at FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // ตรวจสอบวันหมดอายุ
        $today = date('Y-m-d');
        $expired = !empty($row['expired_at']) && $row['expired_at'] < $today;
        
        if($expired) {
            $error = "บัญชีผู้ใช้นี้หมดอายุแล้ว โปรดติดต่อผู้ดูแลระบบ";
        }
        else if(password_verify($password, $row['password_hash'])) {
            // ตรวจสอบว่ากำลังจะหมดอายุหรือไม่ (ภายใน 7 วัน)
            $expired_date = new DateTime($row['expired_at']);
            $today_date = new DateTime();
            $days_until_expired = $today_date->diff($expired_date)->days;
            
            if(!empty($row['expired_at']) && $days_until_expired <= 7) {
                $_SESSION['expiry_warning'] = "บัญชีของคุณจะหมดอายุในอีก {$days_until_expired} วัน โปรดติดต่อผู้ดูแลระบบ";
            }
            
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['username'] = $username;
            $_SESSION['name_1'] = $row['name_1']; // เก็บชื่อผู้ใช้ไว้ใน session
            
            // ถ้าเป็นการส่ง AJAX จะต้องตรวจสอบ X-Requested-With header
            if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
                exit;
            } else {
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "ไม่พบชื่อผู้ใช้นี้";
    }
    
    $stmt->close();
    
    // ถ้าเป็นการส่ง AJAX และมีข้อผิดพลาด
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <title>เข้าสู่ระบบ - ระบบสื่อการเรียนรู้ สื่อ Content</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="icon" type="image/png" href="logo/logo1.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #7c3aed, #6366f1);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }
        
        #particles-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        
        .kc-logo {
            font-family: 'Arial Black', 'Prompt', sans-serif;
            font-weight: 900;
            letter-spacing: -2px;
        }
        
        .login-title {
            color: #fff;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .login-card {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateY(0);
            transition: transform 0.5s, box-shadow 0.5s;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #a5b4fc;
            transition: color 0.3s;
        }
        
        .input-field {
            padding-left: 40px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .input-field:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            outline: none;
        }
        
        .input-field:focus + i {
            color: #6366f1;
        }
        
        .btn-login {
            background: linear-gradient(90deg, #4f46e5, #6366f1);
            color: white;
            border-radius: 8px;
            padding: 12px;
            transition: all 0.3s;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.6s;
        }
        
        .btn-login:hover {
            background: linear-gradient(90deg, #4338ca, #4f46e5);
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(67, 56, 202, 0.4);
        }
        
        .btn-login:hover:before {
            left: 100%;
        }
        
        .login-icon {
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: #4f46e5;
            font-size: 28px;
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.15);
            position: relative;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        .login-icon:after {
            content: '';
            position: absolute;
            width: 90%;
            height: 10px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.05);
            bottom: -15px;
            filter: blur(3px);
            animation: shadow 3s ease-in-out infinite;
        }
        
        @keyframes shadow {
            0%, 100% {
                transform: scale(1);
                opacity: 0.3;
            }
            50% {
                transform: scale(0.8);
                opacity: 0.1;
            }
        }
        
        .year-text {
            font-size: 14px;
            color: #c7d2fe;
            font-weight: 400;
        }
        
        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.3" d="M0,64L48,80C96,96,192,128,288,128C384,128,480,96,576,85.3C672,75,768,85,864,96C960,107,1056,117,1152,133.3C1248,149,1344,171,1392,181.3L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: 100% 100px;
            background-repeat: no-repeat;
            z-index: -1;
        }
        
        .wave-2 {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 150px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.15" d="M0,160L48,170.7C96,181,192,203,288,186.7C384,171,480,117,576,112C672,107,768,149,864,165.3C960,181,1056,171,1152,154.7C1248,139,1344,117,1392,106.7L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: 100% 150px;
            background-repeat: no-repeat;
            z-index: -1;
            animation: wave-move 20s linear infinite;
        }
        
        @keyframes wave-move {
            0% {
                background-position-x: 0%;
            }
            100% {
                background-position-x: 100%;
            }
        }
        
        /* ลูกเล่นเพิ่มเติม */
        .info-bubble {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: absolute;
            max-width: 250px;
            font-size: 14px;
            color: #4338ca;
            display: none;
            animation: fadeIn 0.3s ease;
            z-index: 10;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-label {
            transition: all 0.3s;
        }
        
        .form-input:focus + .form-label {
            color: #4f46e5;
            font-weight: 500;
        }
        
        /* ไฮไลท์เมื่อกรอกถูกต้อง */
        .input-field.valid {
            border-color: #10b981;
        }
        
        .input-field.valid + i {
            color: #10b981;
        }
        
        /* เอฟเฟกต์การกระเพื่อมของโลโก้ */
        .logo-container {
            position: relative;
            animation: pulseGlow 3s infinite alternate;
        }
        
        @keyframes pulseGlow {
            0% {
                filter: drop-shadow(0 0 5px rgba(79, 70, 229, 0.1));
            }
            100% {
                filter: drop-shadow(0 0 15px rgba(79, 70, 229, 0.5));
            }
        }
        
        /* ลูกเล่นไฮไลต์ */
        .highlight {
            position: relative;
            display: inline-block;
        }
        
        .highlight:after {
            content: '';
            position: absolute;
            width: 100%;
            height: 8px;
            background: rgba(99, 102, 241, 0.3);
            bottom: 2px;
            left: 0;
            z-index: -1;
            transform: skew(-12deg);
        }
        
        /* ลูกเล่นแสดงเวอร์ชั่น */
        .version-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            color: white;
            cursor: help;
        }
        
        /* Animation classes */
        .animate__shakeX {
            animation: shakeX 0.75s;
        }
        
        @keyframes shakeX {
            from, to { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(10px); }
        }
        
        .animate__fadeIn {
            animation: fadeIn 0.5s;
        }
    </style>
</head>
<body class="flex flex-col items-center justify-center p-4 relative">
    <div id="particles-canvas"></div>
    <div class="wave"></div>
    <div class="wave-2"></div>
    
    <div class="version-badge" id="version-info">v.2.5.1</div>
    <div class="info-bubble" id="version-bubble" style="display:none; right: 10px; top: 40px;">
        <b>KC Media Platform</b><br>
        เวอร์ชั่น 2.5.1 อัพเดท 15/05/2025<br>
        <span class="text-green-600">- ปรับปรุงความเร็วการโหลด</span><br>
        <span class="text-green-600">- เพิ่มคอนเทนต์ใหม่</span><br>
        <span class="text-green-600">- แก้ไขบั๊ก UI</span>
    </div>
    
    <div class="w-full max-w-xl text-center mb-8 mt-6 z-10 logo-container" data-tilt>
        <div class="flex items-center justify-center">
            <h1 class="login-title text-6xl md:text-7xl mb-2 relative">
                สื่อ Content
            </h1>
        </div>
        
        <h2 class="text-lg text-white mt-4 opacity-90 font-light">
            <span class="typing-text">ยินดีต้อนรับเข้าสู่ระบบสื่อการเรียนรู้ล้ำสมัย</span>
            <span class="animate-ping inline-block h-2 w-2 rounded-full bg-white ml-1" style="animation-duration: 1.5s;"></span>
        </h2>
    </div>
    
    <div class="w-full max-w-md z-10">
        <div class="bg-white login-card p-8 md:p-10">
            <div class="text-center mb-8">
                <div class="login-icon text-xl mb-5">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3 class="text-2xl font-semibold text-indigo-900">เข้าสู่ระบบ</h3>
                <p class="text-gray-500 text-sm mt-2">เข้าสู่ระบบเพื่อเข้าถึงคอนเทนต์</p>
            </div>
            
            <?php if($error): ?>
                <div id="error-message" class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md flex items-center text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span id="error-text"><?php echo $error; ?></span>
                </div>
            <?php else: ?>
                <div id="error-message" class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md flex items-center text-sm hidden">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span id="error-text"></span>
                </div>
            <?php endif; ?>
            
            <form id="login-form" method="POST" action="index.php" class="space-y-6">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2 transition-all" for="username">
                        <i class="far fa-user text-indigo-400 mr-1"></i> ชื่อผู้ใช้
                    </label>
                    <div class="input-with-icon group">
                        <input 
                            type="text" 
                            name="username" 
                            id="username"
                            class="input-field w-full py-3 px-4 transition-all focus:ring-2" 
                            required 
                            placeholder="กรอกชื่อผู้ใช้ของคุณ"
                            autocomplete="username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        >
                        <i class="fas fa-user group-hover:text-indigo-500 transition-all"></i>
                    </div>
                    <div class="text-xs text-gray-500 mt-1 ml-1 hidden" id="username-hint">
                        <i class="fas fa-info-circle mr-1"></i> ใช้ชื่อผู้ใช้ที่ได้รับจากผู้ดูแลระบบ
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2 transition-all" for="password">
                        <i class="far fa-lock text-indigo-400 mr-1"></i> รหัสผ่าน
                    </label>
                    <div class="input-with-icon relative group">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="input-field w-full py-3 px-4 pr-10 transition-all focus:ring-2" 
                            required 
                            placeholder="กรอกรหัสผ่านของคุณ"
                            autocomplete="current-password"
                        >
                        <i class="fas fa-lock group-hover:text-indigo-500 transition-all"></i>
                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer text-gray-500 hover:text-indigo-600 transition-all" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" id="login-button" class="btn-login w-full flex items-center justify-center mt-8 group">
                    <span id="login-text">
                        <i class="fas fa-sign-in-alt mr-2 transition-transform group-hover:translate-x-1"></i> เข้าสู่ระบบ
                    </span>
                    <span id="loading-spinner" class="hidden">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="ml-2">กำลังเข้าสู่ระบบ...</span>
                    </span>
                </button>
            </form>
            
            <div class="text-center mt-8 text-sm text-gray-500">
                <p>หากมีปัญหาในการเข้าสู่ระบบ <button id="help-button" class="text-indigo-600 hover:underline font-medium focus:outline-none">ติดต่อผู้ดูแลระบบ</button></p>
                <div class="info-bubble text-left" id="help-bubble" style="display:none; left: 50%; transform: translateX(-50%); top: 100%;">
                    <p><b>ติดต่อผู้ดูแลระบบ:</b></p>
                    <p><i class="fas fa-envelope mr-1"></i> admin@kcmedia.co.th</p>
                    <p><i class="fas fa-phone mr-1"></i> 02-123-4567</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-8 mb-6">
            <div class="kc-logo text-white text-3xl mb-2 flex items-center justify-center">
                <span>KC</span> <span class="year-text align-text-top ml-1">MEDIA 2025</span>
            </div>
            <p class="text-white text-opacity-70 text-xs">© 2025 KC Media. All rights reserved.</p>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // สร้างพาร์ติเคิลด้วย Three.js
            let scene, camera, renderer, particles;
            
            function initParticles() {
                scene = new THREE.Scene();
                camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
                renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
                renderer.setSize(window.innerWidth, window.innerHeight);
                renderer.setPixelRatio(window.devicePixelRatio);
                document.getElementById('particles-canvas').appendChild(renderer.domElement);
                
                // สร้างพาร์ติเคิล
                const particlesGeometry = new THREE.BufferGeometry();
                const particlesCount = 800;
                
                const posArray = new Float32Array(particlesCount * 3);
                
                for(let i = 0; i < particlesCount * 3; i++) {
                    posArray[i] = (Math.random() - 0.5) * 10;
                }
                
                particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
                
                const particlesMaterial = new THREE.PointsMaterial({
                    size: 0.02,
                    color: 0xffffff,
                    transparent: true,
                    opacity: 0.5
                });
                
                particles = new THREE.Points(particlesGeometry, particlesMaterial);
                scene.add(particles);
                
                camera.position.z = 3;
                
                // แอนิเมชัน
                function animate() {
                    requestAnimationFrame(animate);
                    particles.rotation.x += 0.0003;
                    particles.rotation.y += 0.0005;
                    renderer.render(scene, camera);
                }
                
                animate();
                
                // รีไซส์
                window.addEventListener('resize', () => {
                    camera.aspect = window.innerWidth / window.innerHeight;
                    camera.updateProjectionMatrix();
                    renderer.setSize(window.innerWidth, window.innerHeight);
                });
            }
            
            // เริ่มทำงานเมื่อโหลดหน้าเว็บ
            try {
                initParticles();
            } catch (e) {
                console.log('Particles effect could not be initialized', e);
                // ใช้แบ็คอัพ CSS แทนหากมีข้อผิดพลาด
                document.body.classList.add('particles-fallback');
            }
            
            // Typing Animation
            const typingElement = document.querySelector('.typing-text');
            const originalText = typingElement.textContent;
            typingElement.textContent = '';
            
            let i = 0;
            function typeWriter() {
                if (i < originalText.length) {
                    typingElement.textContent += originalText.charAt(i);
                    i++;
                    setTimeout(typeWriter, 50);
                }
            }
            
            setTimeout(typeWriter, 800);
            
            // สคริปต์สำหรับแสดง/ซ่อนรหัสผ่าน
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                // เปลี่ยนประเภทของช่องรหัสผ่าน
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                // เปลี่ยนไอคอน
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
            
            // แสดงคำใบ้ username เมื่อ focus
            document.getElementById('username').addEventListener('focus', function() {
                document.getElementById('username-hint').classList.remove('hidden');
            });
            
            document.getElementById('username').addEventListener('blur', function() {
                document.getElementById('username-hint').classList.add('hidden');
            });
            
            // แสดงข้อมูลเวอร์ชั่น
            document.getElementById('version-info').addEventListener('mouseenter', function() {
                document.getElementById('version-bubble').style.display = 'block';
            });
            
            document.getElementById('version-info').addEventListener('mouseleave', function() {
                document.getElementById('version-bubble').style.display = 'none';
            });
            
            // แสดงข้อมูลช่วยเหลือ
            document.getElementById('help-button').addEventListener('click', function(e) {
                e.preventDefault();
                const helpBubble = document.getElementById('help-bubble');
                if (helpBubble.style.display === 'none' || helpBubble.style.display === '') {
                    helpBubble.style.display = 'block';
                } else {
                    helpBubble.style.display = 'none';
                }
            });
            
            // ส่งฟอร์มด้วย AJAX แทนการส่งแบบปกติ
            document.getElementById('login-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // แสดงสถานะกำลังโหลด
                document.getElementById('login-text').classList.add('hidden');
                document.getElementById('loading-spinner').classList.remove('hidden');
                
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;
                
                // ส่งข้อมูลไปยังเซิร์ฟเวอร์ด้วย Fetch API
                fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest' // บอกเซิร์ฟเวอร์ว่านี่คือ AJAX request
                    },
                    body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
                })
                .then(response => {
                    return response.json(); // แปลงการตอบกลับเป็น JSON
                })
                .then(data => {
                    if (data.success) {
                        // ถ้าล็อกอินสำเร็จ ให้ redirect ไปยัง URL ที่กำหนด
                        window.location.href = data.redirect;
                    } else {
                        // ถ้าล็อกอินไม่สำเร็จ ให้แสดงข้อความผิดพลาด
                        document.getElementById('error-text').textContent = data.error;
                        document.getElementById('error-message').classList.remove('hidden');
                        
                        // เขย่าฟอร์ม
                        const form = document.querySelector('.login-card');
                        form.classList.add('animate__shakeX');
                        setTimeout(() => {
                            form.classList.remove('animate__shakeX');
                        }, 1000);
                        
                        // ซ่อนสถานะกำลังโหลด
                        document.getElementById('login-text').classList.remove('hidden');
                        document.getElementById('loading-spinner').classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('error-text').textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์';
                    document.getElementById('error-message').classList.remove('hidden');
                    document.getElementById('login-text').classList.remove('hidden');
                    document.getElementById('loading-spinner').classList.add('hidden');
                });
            });
            
            // เพิ่มเอฟเฟกต์เมื่อ hover ที่ปุ่ม
            const loginButton = document.getElementById('login-button');
            loginButton.addEventListener('mouseenter', function() {
                gsap.to(this, {
                    scale: 1.03,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
            
            loginButton.addEventListener('mouseleave', function() {
                gsap.to(this, {
                    scale: 1,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
            
            // Validation ง่ายๆ
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            
            usernameInput.addEventListener('input', function() {
                if (this.value.length >= 3) {
                    this.classList.add('valid');
                } else {
                    this.classList.remove('valid');
                }
            });
            
            passwordInput.addEventListener('input', function() {
                if (this.value.length >= 6) {
                    this.classList.add('valid');
                } else {
                    this.classList.remove('valid');
                }
            });
            
            // เพิ่มลูกเล่น Tilt effect - สร้างลูกเล่นเอียงการ์ดเมื่อเลื่อนเมาส์
            function addTiltEffect() {
                const card = document.querySelector('.login-card');
                
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    
                    const moveX = (x - centerX) / 20;
                    const moveY = (y - centerY) / 20;
                    
                    card.style.transform = `perspective(1000px) rotateX(${-moveY}deg) rotateY(${moveX}deg) translateZ(10px)`;
                });
                
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateZ(0)';
                    card.style.transition = 'transform 0.5s ease';
                });
                
                card.addEventListener('mouseenter', () => {
                    card.style.transition = 'transform 0.1s ease';
                });
            }
            
            // เรียกใช้ฟังก์ชันทั้ล์ท
            addTiltEffect();
            
            // เพิ่มลูกเล่น Tooltips ให้กับองค์ประกอบต่างๆ
            function addTooltip(element, message) {
                element.setAttribute('data-tooltip', message);
                
                element.addEventListener('mouseenter', function(e) {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'info-bubble';
                    tooltip.innerHTML = message;
                    tooltip.style.position = 'absolute';
                    document.body.appendChild(tooltip);
                    
                    const rect = element.getBoundingClientRect();
                    tooltip.style.left = rect.left + 'px';
                    tooltip.style.top = (rect.bottom + 10) + 'px';
                    tooltip.style.display = 'block';
                    tooltip.style.zIndex = '1000';
                    
                    this.tooltip = tooltip;
                });
                
                element.addEventListener('mouseleave', function() {
                    if (this.tooltip) {
                        document.body.removeChild(this.tooltip);
                        this.tooltip = null;
                    }
                });
            }
            
            // เพิ่ม tooltips ให้กับปุ่มต่างๆ
            addTooltip(document.getElementById('togglePassword'), 'คลิกเพื่อแสดง/ซ่อนรหัสผ่าน');
            
            // เพิ่มลูกเล่นแสดงความแข็งแรงของรหัสผ่าน
            function addPasswordStrengthMeter() {
                const passwordInput = document.getElementById('password');
                const meterContainer = document.createElement('div');
                meterContainer.className = 'flex space-x-1 mt-1';
                
                // สร้าง 4 บาร์
                for (let i = 0; i < 4; i++) {
                    const bar = document.createElement('div');
                    bar.className = 'h-1 w-full bg-gray-200 rounded-full';
                    meterContainer.appendChild(bar);
                }
                
                // แทรกหลังช่องรหัสผ่าน
                passwordInput.parentNode.parentNode.appendChild(meterContainer);
                
                const bars = meterContainer.querySelectorAll('div');
                
                passwordInput.addEventListener('input', function() {
                    const val = this.value;
                    let strength = 0;
                    
                    // มีความยาวมากกว่า 6 ตัว
                    if (val.length > 6) strength++;
                    // มีตัวเลข
                    if (val.match(/\d+/)) strength++;
                    // มีตัวอักษรพิมพ์ใหญ่
                    if (val.match(/[A-Z]+/)) strength++;
                    // มีอักขระพิเศษ
                    if (val.match(/[^a-zA-Z0-9]+/)) strength++;
                    
                    // สีตามความแข็งแรง
                    const colors = ['#EF4444', '#F59E0B', '#10B981', '#6366F1'];
                    
                    // ปรับสีบาร์
                    bars.forEach((bar, index) => {
                        if (index < strength) {
                            bar.style.backgroundColor = colors[strength - 1];
                        } else {
                            bar.style.backgroundColor = '#E5E7EB';
                        }
                    });
                });
            }
            
            // เรียกใช้ความแข็งแรงของรหัสผ่าน
            addPasswordStrengthMeter();
            
            // เพิ่มลูกเล่นแสดงวันเวลาปัจจุบัน
            function displayDateTime() {
                const dateElement = document.createElement('div');
                dateElement.className = 'text-white text-opacity-70 text-xs fixed bottom-2 left-2';
                
                function updateTime() {
                    const now = new Date();
                    const formattedDate = now.toLocaleDateString('th-TH', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                    dateElement.textContent = formattedDate;
                }
                
                updateTime();
                setInterval(updateTime, 1000);
                
                document.body.appendChild(dateElement);
            }
            
            // แสดงวันเวลา
            displayDateTime();
            
            // เพิ่มลูกเล่นการแจ้งเตือนระบบ
            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                
                // กำหนดสีตามประเภท
                let bgColor, iconClass;
                switch(type) {
                    case 'success':
                        bgColor = 'bg-green-500';
                        iconClass = 'fas fa-check-circle';
                        break;
                    case 'error':
                        bgColor = 'bg-red-500';
                        iconClass = 'fas fa-exclamation-circle';
                        break;
                    case 'warning':
                        bgColor = 'bg-yellow-500';
                        iconClass = 'fas fa-exclamation-triangle';
                        break;
                    default:
                        bgColor = 'bg-blue-500';
                        iconClass = 'fas fa-info-circle';
                }
                
                notification.className = `${bgColor} text-white px-4 py-3 rounded-lg shadow-lg fixed top-4 right-4 flex items-center z-50 transform transition-all duration-500 translate-x-full`;
                notification.innerHTML = `
                    <i class="${iconClass} mr-2"></i>
                    ${message}
                    <button class="ml-4 text-white focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                document.body.appendChild(notification);
                
                // แสดงการแจ้งเตือน
                setTimeout(() => {
                    notification.classList.remove('translate-x-full');
                }, 100);
                
                // ซ่อนหลังจาก 5 วินาที
                setTimeout(() => {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 500);
                }, 5000);
                
                // ปุ่มปิด
                const closeButton = notification.querySelector('button');
                closeButton.addEventListener('click', () => {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 500);
                });
            }
            
            // แสดงการแจ้งเตือนตอนโหลดเพจ
            setTimeout(() => {
                showNotification('ยินดีต้อนรับเข้าสู่ระบบสื่อการเรียนรู้', 'info');
            }, 1000);
            
            // ดักจับการเข้าถึงคีย์บอร์ด
            document.addEventListener('keydown', function(e) {
                // ถ้ากด Enter ที่ช่อง username ให้เลื่อนไปยังช่อง password
                if (e.key === 'Enter' && document.activeElement === usernameInput) {
                    e.preventDefault();
                    passwordInput.focus();
                }
            });
            
            // เพิ่มลูกเล่นแสดงคำแนะนำสำหรับผู้ใช้ใหม่
            const showTips = localStorage.getItem('kc_media_tips_shown') !== 'true';
            
            if (showTips) {
                setTimeout(() => {
                    const tipCard = document.createElement('div');
                    tipCard.className = 'fixed bottom-4 right-4 bg-white rounded-lg shadow-xl p-4 max-w-xs z-50 animate__fadeInUp';
                    tipCard.innerHTML = `
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-bold text-indigo-800"><i class="fas fa-lightbulb text-yellow-500 mr-2"></i>เคล็ดลับ!</h3>
                            <button id="close-tip" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="text-sm text-gray-600">สำหรับผู้ใช้ใหม่: คุณสามารถเข้าสู่ระบบทดลองด้วย username: <strong>demo</strong> และ password: <strong>demo</strong></p>
                        <div class="mt-3 text-right">
                            <button id="dont-show-again" class="text-xs text-indigo-600 hover:underline">ไม่ต้องแสดงอีก</button>
                        </div>
                    `;
                    
                    document.body.appendChild(tipCard);
                    
                    document.getElementById('close-tip').addEventListener('click', () => {
                        tipCard.classList.add('animate__fadeOutDown');
                        setTimeout(() => {
                            document.body.removeChild(tipCard);
                        }, 500);
                    });
                    
                    document.getElementById('dont-show-again').addEventListener('click', () => {
                        localStorage.setItem('kc_media_tips_shown', 'true');
                        tipCard.classList.add('animate__fadeOutDown');
                        setTimeout(() => {
                            document.body.removeChild(tipCard);
                        }, 500);
                    });
                }, 3000);
            }
            
            // เพิ่มเอฟเฟกต์ลูกคลื่นพื้นหลัง
            function createWaveEffect() {
                const canvas = document.createElement('canvas');
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
                canvas.style.position = 'fixed';
                canvas.style.bottom = '0';
                canvas.style.left = '0';
                canvas.style.pointerEvents = 'none';
                canvas.style.zIndex = '-2';
                document.body.appendChild(canvas);
                
                const ctx = canvas.getContext('2d');
                
                function drawWave(time) {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    
                    const gradient = ctx.createLinearGradient(0, canvas.height, 0, canvas.height * 0.7);
                    gradient.addColorStop(0, 'rgba(255, 255, 255, 0.1)');
                    gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');
                    
                    ctx.fillStyle = gradient;
                    
                    ctx.beginPath();
                    ctx.moveTo(0, canvas.height);
                    
                    for (let i = 0; i < canvas.width; i++) {
                        const x = i;
                        const y = Math.sin((i * 0.005) + (time * 0.0015)) * 15 + 
                                 Math.sin((i * 0.002) + (time * 0.001)) * 10 + 
                                 canvas.height * 0.8;
                        
                        ctx.lineTo(x, y);
                    }
                    
                    ctx.lineTo(canvas.width, canvas.height);
                    ctx.closePath();
                    ctx.fill();
                    
                    requestAnimationFrame(drawWave);
                }
                
                requestAnimationFrame(drawWave);
                
                window.addEventListener('resize', () => {
                    canvas.width = window.innerWidth;
                    canvas.height = window.innerHeight;
                });
            }
            
            // สร้างเอฟเฟกต์ลูกคลื่น
            createWaveEffect();
        });
    </script>
</body>
</html>