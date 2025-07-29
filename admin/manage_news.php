<?php
$active_page = 'news';
$page_title = 'จัดการข่าวสาร';
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

// --- Handle Add News Form (PDO) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_news'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image_url = null; // กำหนดค่าเริ่มต้นเป็น null

    // --- จัดการการอัปโหลดไฟล์รูปภาพ ---
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // **ข้อควรระวังเรื่องการจัดการไฟล์บน Render (Stateless Service):**
        // การบันทึกไฟล์โดยตรงบน Server ของ Render (assets/img/news/)
        // จะไม่ยั่งยืนใน Production เนื่องจากไฟล์จะหายไปเมื่อ Container ถูก Restart/Rebuild
        // ควรพิจารณาใช้ Cloud Storage (เช่น AWS S3, Cloudinary) สำหรับไฟล์ที่ผู้ใช้อัปโหลด
        // แต่สำหรับตอนนี้ โค้ดจะถูกปรับให้ทำงานตามโครงสร้างเดิมโดยใช้ Path สัมพัทธ์

        $target_dir = "../assets/img/news/";
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
            $image_url = "assets/img/news/" . $image_name;
        } else {
            error_log("Failed to move uploaded file for news: " . $target_file);
            // คุณอาจจะแจ้งผู้ใช้ว่าอัปโหลดไฟล์ไม่สำเร็จ
        }
    }

    $sql_insert = "INSERT INTO news (title, content, image_url) VALUES (:title, :content, :image_url)";
    
    try {
        $stmt_insert = $conn->prepare($sql_insert);
        // ผูกค่า Parameters
        $stmt_insert->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt_insert->bindParam(':content', $content, PDO::PARAM_STR);
        $stmt_insert->bindParam(':image_url', $image_url, PDO::PARAM_STR);
        
        $stmt_insert->execute();
        
        header("Location: manage_news.php"); // Redirect เพื่อ Refresh หน้า
        exit();

    } catch (PDOException $e) {
        error_log("Database error adding new news: " . $e->getMessage());
        // คุณอาจจะแจ้งผู้ใช้ว่ามีข้อผิดพลาดในการเพิ่มข้อมูล
        echo "มีข้อผิดพลาดในการเพิ่มข่าวใหม่: " . $e->getMessage();
    }
}

// --- ดึงรายการข่าวทั้งหมดมาแสดง (PDO) ---
$news_items = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$news_sql = "SELECT id, title, content, created_at FROM news ORDER BY created_at DESC";
try {
    $stmt_news = $conn->query($news_sql); // ใช้ query() สำหรับ SELECT ทั้งหมดที่ไม่มี Parameters
    $news_items = $stmt_news->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array
} catch (PDOException $e) {
    error_log("Database error fetching news items: " . $e->getMessage());
    // คุณอาจจะแสดงข้อความ error หรือแสดง array ว่างเปล่า
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
    /* ... (CSS styles remains unchanged, if any. Styles from previous example for forms/tables are assumed to be present) ... */
    .form-container { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); margin-bottom: 30px; }
    .form-container h2 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    label { display: block; margin-bottom: 8px; font-weight: bold; }
    input[type="text"], textarea { width: 100%; padding: 8px; margin: 6px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    input[type="file"] { margin-bottom: 15px; } /* Added margin for file input */
    button[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 10px; }
    button[type="submit"]:hover { background-color: #0056b3; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>

<h1>จัดการข่าวสาร</h1>
<div class="form-container">
    <h2>สร้างข่าวใหม่</h2>
    <form action="manage_news.php" method="post" enctype="multipart/form-data">
        <label for="title">หัวข้อข่าว</label>
        <input type="text" id="title" name="title" required>
        
        <label for="content">เนื้อหาข่าว</label>
        <textarea id="content" name="content"></textarea>

        <label for="image" style="margin-top:10px;">รูปภาพประกอบ (ไม่บังคับ)</label>
        <input type="file" id="image" name="image" accept="image/*">
        
        <button type="submit" name="add_news">เผยแพร่ข่าว</button>
    </form>
</div>

<h2>รายการข่าวทั้งหมด</h2>
<table>
    <thead>
        <tr>
            <th>หัวข้อ</th>
            <th>เนื้อหาย่อ</th>
            <th>วันที่สร้าง</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($news_items)): // ตรวจสอบว่า $news_items ไม่ว่างเปล่า ?>
            <?php foreach($news_items as $row): // ใช้ foreach loop ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars(strip_tags(substr($row['content'] ?? '', 0, 150))) . (strlen($row['content'] ?? '') > 150 ? '...' : ''); ?></td>
                <td><?php echo htmlspecialchars(date('d M Y', strtotime($row['created_at'] ?? ''))); ?></td>
                <td>
                    <a href="edit_news.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>">แก้ไข</a> |
                    <a href="delete_news.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>" onclick="return confirm('ยืนยันการลบข่าวนี้?');">ลบ</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" style="text-align: center;">ยังไม่มีข่าวในระบบ</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once 'includes/admin_footer.php'; ?>