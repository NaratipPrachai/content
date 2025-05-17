<script>
    // ฟังก์ชันสำหรับขยาย/ย่อ media card
    function toggleMedia(card) {
        card.classList.toggle('media-expanded');
        
        // เพิ่มเอฟเฟกต์การเลื่อนเพื่อดูเนื้อหา
        if (card.classList.contains('media-expanded')) {
            setTimeout(() => {
                card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }
    }
    
    // แสดงการโหลดเมื่อคลิกลิงก์
    document.addEventListener('DOMContentLoaded', function() {
        const links = document.querySelectorAll('a:not([target="_blank"])');
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                // ไม่ทำงานกับปุ่มที่เปิดใน tab ใหม่
                if (this.getAttribute('target') === '_blank') return;
                
                // ไม่ทำงานกับปุ่มที่อยู่ใน media-card header (toggle)
                if (this.closest('.media-header')) return;
                
                const loader = document.createElement('div');
                loader.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                loader.innerHTML = '<div class="loading-animation"></div>';
                document.body.appendChild(loader);
                
                // ลบตัวโหลดหากหน้าไม่โหลดภายใน 5 วินาที
                setTimeout(() => {
                    if (document.body.contains(loader)) {
                        document.body.removeChild(loader);
                    }
                }, 5000);
            });
        });
    });
    
    // ป้องกันการคลิกขวา
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        
        // แสดงข้อความเตือน
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg';
        toast.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> ไม่อนุญาตให้คลิกขวา';
        document.body.appendChild(toast);
        
        // ลบข้อความหลังจาก 2 วินาที
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 500);
        }, 2000);
    });
    
    // ป้องกันการกด F12
    document.addEventListener('keydown', function(e) {
        if(e.keyCode == 123) { // F12
            e.preventDefault();
            return false;
        }
        
        // ป้องกัน Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
        if(e.ctrlKey && (e.shiftKey && (e.keyCode == 73 || e.keyCode == 74) || e.keyCode == 85)) {
            e.preventDefault();
            return false;
        }
    });
    
    // แสดงเอฟเฟกต์เมื่อโหลดหน้าเสร็จ
    window.addEventListener('load', function() {
        document.body.classList.add('fade-in');
        
        // เพิ่มเอฟเฟกต์การปรากฏของ card
        const cards = document.querySelectorAll('.media-card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 * index);
        });
    });
</script> 