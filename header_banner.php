<!-- header_banner.php -->
<?php
// ดึงแบนเนอร์ที่ active ทั้งหมด
$banner_sql = "SELECT * FROM banners WHERE status = 'active' ORDER BY created_at DESC";
$banner_result = $conn->query($banner_sql);
?>

<?php if($banner_result->num_rows > 0): ?>
<!-- Add Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css" />

<div class="banner-container relative w-full h-64 mb-8 overflow-hidden rounded-lg shadow-lg">
    <div class="swiper banner-swiper">
        <div class="swiper-wrapper">
            <?php while($banner = $banner_result->fetch_assoc()): ?>
            <div class="swiper-slide">
                <img src="<?php echo htmlspecialchars($banner['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                     class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-r from-black/50 to-transparent flex items-center">
                    <div class="text-white p-8">
                        <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($banner['title']); ?></h1>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <!-- Add navigation buttons -->
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <!-- Add pagination -->
        <div class="swiper-pagination"></div>
    </div>
</div>

<!-- Add Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Swiper('.banner-swiper', {
        // Optional parameters
        loop: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        // Navigation arrows
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        // Pagination
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
    });
});
</script>
<?php endif; ?> 