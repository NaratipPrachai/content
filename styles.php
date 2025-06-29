<style>
    @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap');
    
    body {
        font-family: 'Prompt', sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
    }
    
    /* ป้องกันการคลิกขวา */
    .media-view {
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }
    
    /* สไตล์สำหรับ tab */
    .tab-active {
        border-bottom: 3px solid #4F46E5;
        color: #4F46E5;
    }
    
    /* แสดง/ซ่อน content เมื่อคลิกที่ card */
    .media-content {
        display: none;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.5s ease-out;
    }
    
    .media-expanded .media-content {
        display: block;
        max-height: 800px;
        transition: max-height 0.5s ease-in;
    }
    
    /* สไตล์สำหรับปุ่มขยาย/ย่อ */
    .expand-btn {
        transition: transform 0.3s ease;
    }
    
    .media-expanded .expand-btn {
        transform: rotate(180deg);
    }
    
    /* สไตล์สำหรับข้อมูลตรวจสอบ */
    .debug-info {
        margin-top: 10px;
        padding: 10px;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: monospace;
        font-size: 12px;
    }
    
    .card-hover {
        transition: all 0.3s ease;
    }
    
    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .nav-item {
        position: relative;
        transition: all 0.3s ease;
    }
    
    .nav-item:hover {
        transform: translateX(5px);
    }
    
    .nav-item::before {
        content: '';
        position: absolute;
        width: 0;
        height: 100%;
        left: -10px;
        top: 0;
        background-color: #4F46E5;
        transition: width 0.3s ease;
        border-radius: 0 3px 3px 0;
    }
    
    .nav-item:hover::before {
        width: 3px;
    }
    
    .active-nav-item::before {
        width: 3px;
    }
    
    .wave-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%234F46E5' fill-opacity='0.05' d='M0,128L48,144C96,160,192,192,288,186.7C384,181,480,139,576,138.7C672,139,768,181,864,186.7C960,192,1056,160,1152,138.7C1248,117,1344,107,1392,101.3L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
        background-size: cover;
        background-position: center;
        z-index: -1;
        opacity: 0.6;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
        transition: all 0.3s ease;
    }
    
    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    
    .media-card {
        position: relative;
        overflow: hidden;
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    
    .media-card:hover {
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    }
    
    .media-card::after {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        width: 0;
        height: 3px;
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        transition: width 0.3s ease;
    }
    
    .media-card:hover::after {
        width: 100%;
        left: 0;
        right: auto;
    }
    
    .media-header {
        position: relative;
        overflow: hidden;
    }
    
    .search-container {
        position: relative;
        border-radius: 50px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .search-container input {
        border-radius: 50px 0 0 50px;
        padding-left: 1.5rem;
    }
    
    .search-container button {
        border-radius: 0 50px 50px 0;
    }
    
    .navbar {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    }
    
    .sidebar {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    }
    
    .media-content-area {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    }
    
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #4F46E5;
        border-radius: 10px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #7C3AED;
    }
    
    .no-media-container {
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 12px;
        border: 2px dashed #d1d5db;
    }
    
    .loading-animation {
        width: 40px;
        height: 40px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #4F46E5;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .glow {
        animation: glow 2s ease-in-out infinite alternate;
    }
    
    @keyframes glow {
        from {
            box-shadow: 0 0 10px -10px #4F46E5;
        }
        to {
            box-shadow: 0 0 20px 5px #4F46E5;
        }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }
    
    .media-card {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    }
    
    /* Pagination Styles */
    .pagination-container {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        backdrop-filter: blur(10px);
    }
    
    .pagination-container .pagination-btn {
        transition: all 0.2s ease-in-out;
        border: 1px solid transparent;
    }
    
    .pagination-container .pagination-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        border-color: #e0e7ff;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }
    
    .pagination-container .pagination-current {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        transform: translateY(-1px);
    }
    
    .pagination-container .pagination-disabled {
        opacity: 0.4;
        cursor: not-allowed;
        pointer-events: none;
    }
    
    /* Loading Animation for Pagination */
    .pagination-loading {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(255, 255, 255, 0.95);
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        z-index: 9999;
        backdrop-filter: blur(10px);
    }
    
    @media (max-width: 768px) {
        .pagination-container {
            padding: 1rem;
        }
        
        .pagination-container .flex {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }
        
        .pagination-container .pagination-numbers {
            justify-content: center;
        }
        
        .pagination-container .pagination-info {
            text-align: center;
            order: -1;
        }
        
        .pagination-container .pagination-per-page {
            justify-content: center;
        }
    }
    
    /* Animation for media cards appearing */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .media-card-animated {
        animation: slideInUp 0.5s ease-out forwards;
    }
    
    /* Stagger animation delay for multiple cards */
    .media-card:nth-child(1) { animation-delay: 0.1s; }
    .media-card:nth-child(2) { animation-delay: 0.2s; }
    .media-card:nth-child(3) { animation-delay: 0.3s; }
    .media-card:nth-child(4) { animation-delay: 0.4s; }
    .media-card:nth-child(5) { animation-delay: 0.5s; }
    .media-card:nth-child(6) { animation-delay: 0.6s; }
</style> 