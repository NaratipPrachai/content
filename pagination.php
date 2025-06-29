<?php
// Component สำหรับแสดง pagination
if(!isset($total_pages) || $total_pages <= 1) {
    return; // ไม่แสดง pagination ถ้ามีหน้าเดียว
}

// สร้าง URL parameters สำหรับ pagination
$url_params = [];
if(!empty($search)) $url_params['search'] = $search;
if($dept_id > 0) $url_params['dept'] = $dept_id;
if($subject_id > 0) $url_params['subject'] = $subject_id;

// ฟังก์ชันสำหรับสร้าง URL
function buildPaginationUrl($page, $params) {
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

// คำนวณหน้าที่จะแสดงใน pagination
$start_page = max(1, $page - 2);
$end_page = min($total_pages, $page + 2);

// ปรับให้แสดงอย่างน้อย 5 หน้า (ถ้ามี)
if($end_page - $start_page < 4) {
    if($start_page == 1) {
        $end_page = min($total_pages, $start_page + 4);
    } else {
        $start_page = max(1, $end_page - 4);
    }
}
?>

<div class="pagination-container rounded-lg shadow-lg p-6 mt-6">
    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- ข้อมูลสถิติ -->
        <div class="pagination-info text-sm text-gray-600">
            <span class="font-medium">
                แสดง <?php echo number_format($offset + 1); ?>-<?php echo number_format(min($offset + $per_page, $total_records)); ?> 
                จากทั้งหมด <span class="text-indigo-600 font-semibold"><?php echo number_format($total_records); ?></span> รายการ
            </span>
        </div>
        
        <!-- ปุ่ม pagination -->
        <div class="pagination-numbers flex items-center space-x-1">
            <!-- ปุ่มไปหน้าแรก -->
            <?php if($page > 1): ?>
            <a href="<?php echo buildPaginationUrl(1, $url_params); ?>" 
               class="pagination-btn px-3 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-700 transition duration-200"
               title="หน้าแรก">
                <i class="fas fa-angle-double-left"></i>
            </a>
            
            <!-- ปุ่มหน้าก่อนหน้า -->
            <a href="<?php echo buildPaginationUrl($page - 1, $url_params); ?>" 
               class="pagination-btn px-3 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-700 transition duration-200"
               title="หน้าก่อนหน้า">
                <i class="fas fa-angle-left"></i>
            </a>
            <?php else: ?>
            <span class="pagination-disabled px-3 py-2 rounded-lg text-sm font-medium text-gray-300 cursor-not-allowed">
                <i class="fas fa-angle-double-left"></i>
            </span>
            <span class="pagination-disabled px-3 py-2 rounded-lg text-sm font-medium text-gray-300 cursor-not-allowed">
                <i class="fas fa-angle-left"></i>
            </span>
            <?php endif; ?>
            
            <!-- ตัวเลขหน้า -->
            <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if($i == $page): ?>
                <span class="pagination-current px-4 py-2 rounded-lg text-sm font-semibold text-white shadow-lg">
                    <?php echo $i; ?>
                </span>
                <?php else: ?>
                <a href="<?php echo buildPaginationUrl($i, $url_params); ?>" 
                   class="pagination-btn px-4 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-700 transition duration-200">
                    <?php echo $i; ?>
                </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <!-- ปุ่มหน้าถัดไป -->
            <?php if($page < $total_pages): ?>
            <a href="<?php echo buildPaginationUrl($page + 1, $url_params); ?>" 
               class="pagination-btn px-3 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-700 transition duration-200"
               title="หน้าถัดไป">
                <i class="fas fa-angle-right"></i>
            </a>
            
            <!-- ปุ่มไปหน้าสุดท้าย -->
            <a href="<?php echo buildPaginationUrl($total_pages, $url_params); ?>" 
               class="pagination-btn px-3 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-700 transition duration-200"
               title="หน้าสุดท้าย">
                <i class="fas fa-angle-double-right"></i>
            </a>
            <?php else: ?>
            <span class="pagination-disabled px-3 py-2 rounded-lg text-sm font-medium text-gray-300 cursor-not-allowed">
                <i class="fas fa-angle-right"></i>
            </span>
            <span class="pagination-disabled px-3 py-2 rounded-lg text-sm font-medium text-gray-300 cursor-not-allowed">
                <i class="fas fa-angle-double-right"></i>
            </span>
            <?php endif; ?>
        </div>
        
        <!-- Dropdown สำหรับเลือกจำนวนรายการต่อหน้า -->
        <div class="pagination-per-page flex items-center space-x-2">
            <label class="text-sm text-gray-600 font-medium">แสดง:</label>
            <select onchange="changePerPage(this.value)" 
                    class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200">
                <option value="12" <?php echo $per_page == 12 ? 'selected' : ''; ?>>12</option>
                <option value="24" <?php echo $per_page == 24 ? 'selected' : ''; ?>>24</option>
                <option value="48" <?php echo $per_page == 48 ? 'selected' : ''; ?>>48</option>
                <option value="96" <?php echo $per_page == 96 ? 'selected' : ''; ?>>96</option>
            </select>
            <span class="text-sm text-gray-600">รายการ</span>
        </div>
    </div>
</div>

<script>
function changePerPage(perPage) {
    // แสดง loading animation
    showPaginationLoading();
    
    const url = new URL(window.location);
    url.searchParams.set('per_page', perPage);
    url.searchParams.set('page', '1'); // รีเซ็ตไปหน้าแรก
    
    // เพิ่ม delay เล็กน้อยเพื่อให้เห็น loading animation
    setTimeout(() => {
        window.location.href = url.toString();
    }, 300);
}

function showPaginationLoading() {
    // สร้าง loading overlay
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'pagination-loading';
    loadingDiv.innerHTML = `
        <div class="flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
            <span class="text-gray-700 font-medium">กำลังโหลด...</span>
        </div>
    `;
    document.body.appendChild(loadingDiv);
    
    // ลบหลังจาก 3 วินาที (fallback)
    setTimeout(() => {
        if (document.body.contains(loadingDiv)) {
            document.body.removeChild(loadingDiv);
        }
    }, 3000);
}

// เพิ่ม smooth scroll และ loading effect เมื่อเปลี่ยนหน้า
document.addEventListener('DOMContentLoaded', function() {
    const paginationLinks = document.querySelectorAll('.pagination-numbers a, .pagination-btn');
    
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // ป้องกันการคลิกซ้ำ
            if (this.classList.contains('loading')) {
                e.preventDefault();
                return;
            }
            
            // เพิ่ม loading class
            this.classList.add('loading');
            
            // แสดง loading animation
            showPaginationLoading();
            
            // เพิ่ม visual feedback
            this.style.opacity = '0.6';
            this.style.transform = 'scale(0.95)';
            
            // Scroll to top of content area
            const contentArea = document.querySelector('.media-content-area');
            if (contentArea) {
                contentArea.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }
        });
    });
    
    // เพิ่ม animation สำหรับ media cards
    const mediaCards = document.querySelectorAll('.media-card');
    mediaCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('media-card-animated');
    });
    
    // เพิ่ม keyboard navigation สำหรับ pagination
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName.toLowerCase() === 'input' || e.target.tagName.toLowerCase() === 'textarea') {
            return; // ไม่ทำงานถ้าอยู่ในช่อง input
        }
        
        const currentPage = <?php echo $page; ?>;
        const totalPages = <?php echo $total_pages; ?>;
        
        if (e.key === 'ArrowLeft' && currentPage > 1) {
            const prevLink = document.querySelector('a[title="หน้าก่อนหน้า"]');
            if (prevLink) {
                e.preventDefault();
                prevLink.click();
            }
        } else if (e.key === 'ArrowRight' && currentPage < totalPages) {
            const nextLink = document.querySelector('a[title="หน้าถัดไป"]');
            if (nextLink) {
                e.preventDefault();
                nextLink.click();
            }
        }
    });
    
    // เพิ่ม tooltip สำหรับปุ่ม pagination
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            this.style.position = 'relative';
        });
    });
});

// เพิ่ม infinite scroll option (เสริม)
function enableInfiniteScroll() {
    let loading = false;
    
    window.addEventListener('scroll', function() {
        if (loading) return;
        
        const scrollPosition = window.innerHeight + window.scrollY;
        const threshold = document.body.offsetHeight - 1000;
        
        if (scrollPosition >= threshold) {
            const currentPage = <?php echo $page; ?>;
            const totalPages = <?php echo $total_pages; ?>;
            
            if (currentPage < totalPages) {
                loading = true;
                const nextPageUrl = new URL(window.location);
                nextPageUrl.searchParams.set('page', currentPage + 1);
                
                // Load next page content via AJAX (ถ้าต้องการ)
                // fetchNextPage(nextPageUrl.toString());
            }
        }
    });
}

// ฟังก์ชันสำหรับแสดงจำนวนรายการที่เลือกอยู่
function highlightCurrentPerPage() {
    const currentPerPage = <?php echo $per_page; ?>;
    const selectElement = document.querySelector('select[onchange="changePerPage(this.value)"]');
    
    if (selectElement) {
        selectElement.style.background = 'linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%)';
        selectElement.style.borderColor = '#0ea5e9';
    }
}

// เรียกใช้เมื่อโหลดหน้า
highlightCurrentPerPage();
</script>
