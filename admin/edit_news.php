<?php
// /admin/edit_news.php
$active_page = 'news';
$page_title = 'แก้ไขข่าว';
require_once 'includes/admin_header.php'; // admin_header.php ควรจะมีการเรียก db_connect.php อยู่แล้ว

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    die("Database connection failed. Please check includes/db_connect.php");
}

// Security check (ตรวจสอบสิทธิ์ Admin)
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}

// ดึง ID ของข่าวจาก URL
$news_id = $_GET['id'] ?? 0;
if ($news_id == 0) {
    header("location: manage_news.php");
    exit();
}

// --- ส่วนจัดการ POST Request (เมื่อมีการส่งฟอร์มเพื่อบันทึกการเปลี่ยนแปลง) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $news_id_post = $_POST['id'];
    $title_post = $_POST['title'];
    $content_post = $_POST['content'];
    $current_image_url_post = $_POST['current_image_url'];
    $new_image_url = $current_image_url_post; // กำหนดค่าเริ่มต้นเป็น URL รูปภาพเดิม

    // --- จัดการการอัปโหลดไฟล์รูปภาพใหม่ ---
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // **ข้อควรระวังเรื่องการจัดการไฟล์บน Render (Stateless Service):**
        // การบันทึกและลบไฟล์โดยตรงบน Server ของ Render (assets/img/news/)
        // จะไม่ยั่งยืนใน Production เนื่องจากไฟล์จะหายไปเมื่อ Container ถูก Restart/Rebuild
        // ควรพิจารณาใช้ Cloud Storage (เช่น AWS S3, Cloudinary) สำหรับไฟล์ที่ผู้ใช้อัปโหลด
        // แต่สำหรับตอนนี้ โค้ดจะถูกปรับให้ทำงานตามโครงสร้างเดิมโดยใช้ Path สัมพัทธ์

        // ลบรูปภาพเก่า (ถ้ามีและไฟล์อยู่จริง)
        if (!empty($current_image_url_post) && file_exists("../" . $current_image_url_post)) {
            unlink("../" . $current_image_url_post);
        }

        // อัปโหลดรูปภาพใหม่
        $target_dir = "../assets/img/news/";
        // ตรวจสอบและสร้าง directory ถ้ายังไม่มี
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $new_image_url = "assets/img/news/" . $image_name; // อัปเดต URL ใหม่
        } else {
            // จัดการข้อผิดพลาดในการอัปโหลดไฟล์
            error_log("Error uploading new image for news ID: " . $news_id_post);
            // คุณอาจจะแจ้งผู้ใช้ว่าอัปโหลดไฟล์ไม่สำเร็จ
        }
    }

    // --- อัปเดตข้อมูลข่าวในฐานข้อมูล ---
    $sql_update = "UPDATE news SET title = :title, content = :content, image_url = :image_url WHERE id = :id";
    try {
        $stmt_update = $conn->prepare($sql_update);
        // ผูกค่า Parameters
        $stmt_update->bindParam(':title', $title_post, PDO::PARAM_STR);
        $stmt_update->bindParam(':content', $content_post, PDO::PARAM_STR);
        $stmt_update->bindParam(':image_url', $new_image_url, PDO::PARAM_STR);
        $stmt_update->bindParam(':id', $news_id_post, PDO::PARAM_INT);

        $stmt_update->execute();
        
        header("Location: manage_news.php"); // Redirect เมื่ออัปเดตสำเร็จ
        exit();

    } catch (PDOException $e) {
        error_log("Database error updating news: " . $e->getMessage());
        echo "มีข้อผิดพลาดในการบันทึกข้อมูลข่าว: " . $e->getMessage();
        // คุณสามารถจัดการข้อผิดพลาดตามความเหมาะสม เช่น แสดงข้อความ error
    }
}

// --- ส่วนดึงข้อมูลข่าวเพื่อแสดงผลในฟอร์ม (GET Request) ---
$news = null; // กำหนดค่าเริ่มต้นเป็น null
$title = '';
$content = '';
$current_image_url = '';

$sql_select = "SELECT * FROM news WHERE id = :id";
try {
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bindParam(':id', $news_id, PDO::PARAM_INT);
    $stmt_select->execute();
    $news = $stmt_select->fetch(PDO::FETCH_ASSOC); // ดึงข้อมูลแถวเดียว

    // ถ้าไม่พบข่าวตาม ID ที่ระบุ
    if (!$news) {
        header("location: manage_news.php");
        exit();
    }
    
    // กำหนดค่าสำหรับ Form
    $title = $news['title'] ?? '';
    $content = $news['content'] ?? '';
    $current_image_url = $news['image_url'] ?? '';

} catch (PDOException $e) {
    error_log("Database error fetching news details: " . $e->getMessage());
    die("ไม่สามารถดึงข้อมูลข่าวได้: " . $e->getMessage());
}

?>
<script src="https://cdn.tiny.cloud/1/wz0eup1bgddbnmjpimc2bfqbp9bc111yb78sfc50e04mjmuq/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: 'textarea#content',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
  });
</script>
<style>
    .current-image { max-width: 200px; display: block; margin-bottom: 10px; border-radius: 4px; }
    .back-link { display: inline-block; margin-top: 15px; text-decoration: none; color: #007bff; }
</style>

<h1>แก้ไขข่าว</h1>
<div class="form-container">
    <form action="edit_news.php?id=<?php echo htmlspecialchars($news_id); ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($news_id); ?>">
        <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($current_image_url); ?>">
        
        <label for="title">หัวข้อข่าว</label>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
        
        <label for="content">เนื้อหาข่าว</label>
        <textarea id="content" name="content"><?php echo htmlspecialchars($content); ?></textarea>

        <label style="margin-top:10px;">รูปภาพปัจจุบัน</label>
        <?php if (!empty($current_image_url)): ?>
            <img src="../<?php echo htmlspecialchars($current_image_url); ?>" alt="Current Image" class="current-image">
        <?php else: ?>
            <p>ไม่มีรูปภาพประกอบ</p>
        <?php endif; ?>
        
        <label for="image">เปลี่ยน/เพิ่ม รูปภาพประกอบ</label>
        <input type="file" id="image" name="image" accept="image/*">

        <button type="submit" style="margin-top:10px; background-color: #007bff;">บันทึกการเปลี่ยนแปลง</button>
    </form>
    <a href="manage_news.php" class="back-link">&laquo; กลับไปหน้าจัดการข่าวสาร</a>
</div>

<?php require_once 'includes/admin_footer.php'; ?>