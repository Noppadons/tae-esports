<?php
$active_page = 'gallery';
$page_title = 'จัดการแกลเลอรี่';
require_once 'includes/admin_header.php'; // admin_header.php ควรจะมีการเรียก db_connect.php อยู่แล้ว

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    die("Database connection failed. Please check includes/db_connect.php");
}

// Security check (ตรวจสอบสิทธิ์ Admin) - ควรมีในทุกหน้า admin
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}

// --- ส่วนจัดการ POST Request (เมื่อมีการอัปโหลดรูปภาพใหม่) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_image'])) {
    $caption = trim($_POST['caption'] ?? ''); // ใช้ trim และ ?? ''

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // **ข้อควรระวังเรื่องการจัดการไฟล์บน Render (Stateless Service):**
        // การบันทึกไฟล์โดยตรงบน Server ของ Render (assets/img/gallery/)
        // จะไม่ยั่งยืนใน Production เนื่องจากไฟล์จะหายไปเมื่อ Container ถูก Restart/Rebuild
        // ควรพิจารณาใช้ Cloud Storage (เช่น AWS S3, Cloudinary) สำหรับไฟล์ที่ผู้ใช้อัปโหลด
        // แต่สำหรับตอนนี้ โค้ดจะถูกปรับให้ทำงานตามโครงสร้างเดิมโดยใช้ Path สัมพัทธ์

        $target_dir = "../assets/img/gallery/";
        // ตรวจสอบและสร้าง directory ถ้ายังไม่มี
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) { // ตั้งค่า permission ที่เหมาะสม
                error_log("Failed to create directory: " . $target_dir);
                // คุณอาจจะแจ้งผู้ใช้ว่าสร้างโฟลเดอร์ไม่สำเร็จ
            }
        }
        
        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = "assets/img/gallery/" . $image_name;
            $sql_insert = "INSERT INTO gallery (image_url, caption) VALUES (:image_url, :caption)";
            try {
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bindParam(':image_url', $image_url, PDO::PARAM_STR);
                $stmt_insert->bindParam(':caption', $caption, PDO::PARAM_STR);
                $stmt_insert->execute();
            } catch (PDOException $e) {
                error_log("Database error inserting gallery image: " . $e->getMessage());
                // คุณอาจจะแจ้งผู้ใช้ว่าบันทึกข้อมูลลง DB ไม่สำเร็จ
            }
        } else {
            error_log("Failed to move uploaded file to: " . $target_file);
            // คุณอาจจะแจ้งผู้ใช้ว่าอัปโหลดไฟล์ไม่สำเร็จ
        }
    }
    header("Location: manage_gallery.php"); // Redirect เพื่อ Refresh หน้า
    exit();
}

// --- ดึงรูปภาพทั้งหมดจากแกลเลอรี่มาแสดง (PDO) ---
$gallery_images = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$sql_select_gallery = "SELECT * FROM gallery ORDER BY uploaded_at DESC";
try {
    $stmt_select_gallery = $conn->query($sql_select_gallery); // ใช้ query() สำหรับ SELECT ทั้งหมดที่ไม่มี Parameters
    $gallery_images = $stmt_select_gallery->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array
} catch (PDOException $e) {
    error_log("Database error fetching gallery images: " . $e->getMessage());
    // คุณอาจจะแสดงข้อความ error หรือแสดง array ว่างเปล่า
}
?>
<style>
    .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
    .gallery-admin-item { position: relative; }
    .gallery-admin-item img { width: 100%; height: 150px; object-fit: cover; border-radius: 4px; display: block; }
    .delete-btn { position: absolute; top: 5px; right: 5px; background-color: rgba(220, 53, 69, 0.9); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-weight: bold; font-size: 1rem; line-height: 30px; text-align: center; }
    .delete-btn:hover { background-color: rgba(200, 33, 49, 1); }
    /* เพิ่ม style สำหรับ form และ container ถ้ายังไม่มีจาก admin_header */
    .form-container { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); margin-bottom: 30px; }
    .form-container h2 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    input[type="file"], input[type="text"] { width: 100%; padding: 8px; margin: 6px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    button[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 10px; }
    button[type="submit"]:hover { background-color: #0056b3; }
</style>

<h1>จัดการแกลเลอรี่</h1>
<div class="form-container">
    <h2>อัปโหลดรูปภาพใหม่</h2>
    <form action="manage_gallery.php" method="post" enctype="multipart/form-data">
        <label for="image_upload">เลือกไฟล์รูปภาพ</label>
        <input type="file" name="image" id="image_upload" accept="image/*" required>
        <label for="caption">คำอธิบายภาพ (ไม่บังคับ)</label>
        <input type="text" name="caption" id="caption" placeholder="คำอธิบายภาพ">
        <button type="submit" name="upload_image">อัปโหลด</button>
    </form>
</div>

<div class="gallery-grid">
<?php if (!empty($gallery_images)): // ตรวจสอบว่ามีรูปภาพในแกลเลอรี่หรือไม่ ?>
    <?php foreach($gallery_images as $row): // ใช้ foreach loop ?>
        <div class="gallery-admin-item">
            <img src="../<?php echo htmlspecialchars($row['image_url'] ?? ''); ?>" alt="<?php echo htmlspecialchars($row['caption'] ?? ''); ?>">
            <a href="delete_gallery_image.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>" onclick="return confirm('ยืนยันการลบรูปภาพนี้?');" class="delete-btn">&times;</a>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align: center; grid-column: 1 / -1;">ยังไม่มีรูปภาพในแกลเลอรี่</p>
<?php endif; ?>
</div>

<?php require_once 'includes/admin_footer.php'; ?>