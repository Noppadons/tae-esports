<?php require_once 'includes/header.php'; ?>

<?php
// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว (มาจาก includes/header.php -> db_connect.php)
if (!isset($conn) || !$conn instanceof PDO) {
    // จัดการข้อผิดพลาดถ้าการเชื่อมต่อ DB ล้มเหลว
    // ในหน้า Public อาจจะไม่ die() แต่แสดงข้อความ error แทน
    echo "<p style='text-align:center; color:red;'>ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง</p>";
    // สามารถ exit; ได้ ถ้าไม่ต้องการให้แสดงเนื้อหาหน้าเว็บต่อ
    // exit;
}
?>

<style>
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
}
.gallery-item {
    overflow: hidden;
    border-radius: 8px;
    cursor: pointer;
}
.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}
.gallery-item:hover img {
    transform: scale(1.05);
}
/* Lightbox styles */
.lightbox {
    display: none; position: fixed; z-index: 1001;
    top: 0; left: 0; width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.8);
    justify-content: center; align-items: center;
}
.lightbox img {
    max-width: 90%; max-height: 80%;
}
.lightbox .close {
    position: absolute; top: 20px; right: 30px;
    font-size: 40px; color: white; cursor: pointer;
}
</style>

<div class="container">
    <h2 class="section-title">แกลเลอรี่</h2>
    <div class="gallery-grid">
    <?php
    $gallery_images = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
    $sql_select_gallery = "SELECT image_url, caption FROM gallery ORDER BY uploaded_at DESC";
    
    try {
        $stmt_gallery = $conn->query($sql_select_gallery); // ใช้ query() สำหรับ SELECT ทั้งหมดที่ไม่มี Parameters
        $gallery_images = $stmt_gallery->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array

        if (!empty($gallery_images)) { // ตรวจสอบว่ามีรูปภาพในแกลเลอรี่หรือไม่
            foreach($gallery_images as $row) { // ใช้ foreach loop
    ?>
                <div class="gallery-item" onclick="openLightbox('<?php echo htmlspecialchars($row['image_url'] ?? ''); ?>')">
                    <img src="../<?php echo htmlspecialchars($row['image_url'] ?? ''); ?>" alt="<?php echo htmlspecialchars($row['caption'] ?? ''); ?>">
                </div>
    <?php
            }
        } else {
            echo "<p style='text-align:center; grid-column: 1 / -1;'>ยังไม่มีรูปภาพในแกลเลอรี่</p>";
        }
    } catch (PDOException $e) {
        error_log("Database error fetching gallery images on public page: " . $e->getMessage());
        echo "<p style='text-align:center; grid-column: 1 / -1; color:red;'>ไม่สามารถโหลดรูปภาพได้ในขณะนี้</p>";
    }
    ?>
    </div>
</div>

<div id="myLightbox" class="lightbox" onclick="closeLightbox()">
    <span class="close">&times;</span>
    <img id="lightboxImage" src="" alt="Lightbox Image">
</div>

<script>
function openLightbox(imageUrl) {
    document.getElementById('myLightbox').style.display = 'flex';
    document.getElementById('lightboxImage').src = imageUrl;
}
function closeLightbox() {
    document.getElementById('myLightbox').style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>