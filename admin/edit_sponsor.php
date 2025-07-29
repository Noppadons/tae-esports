<?php
// /admin/edit_sponsor.php
$active_page = 'sponsors';
$page_title = 'แก้ไขผู้สนับสนุน';
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

$sponsor_id = $_GET['id'] ?? 0;
if ($sponsor_id == 0) {
    header("location: manage_sponsors.php");
    exit();
}

// --- ส่วนจัดการ POST Request (เมื่อมีการส่งฟอร์มเพื่อบันทึกการเปลี่ยนแปลง) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sponsor_id_post = $_POST['id'];
    $current_logo_url = $_POST['current_logo_url']; // URL ของโลโก้เดิม
    $new_logo_url = $current_logo_url; // กำหนดค่าเริ่มต้นเป็นโลโก้เดิม

    // --- จัดการการอัปโหลดไฟล์โลโก้ใหม่ ---
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        // **ข้อควรระวังเรื่องการจัดการไฟล์บน Render (Stateless Service):**
        // การบันทึกและลบไฟล์โดยตรงบน Server ของ Render (assets/img/sponsors/)
        // จะไม่ยั่งยืนใน Production เนื่องจากไฟล์จะหายไปเมื่อ Container ถูก Restart/Rebuild
        // ควรพิจารณาใช้ Cloud Storage (เช่น AWS S3, Cloudinary) สำหรับไฟล์ที่ผู้ใช้อัปโหลด
        // แต่สำหรับตอนนี้ โค้ดจะถูกปรับให้ทำงานตามโครงสร้างเดิมโดยใช้ Path สัมพัทธ์

        // ลบโลโก้เก่า (ถ้ามีและไฟล์อยู่จริง)
        if (!empty($current_logo_url) && file_exists("../" . $current_logo_url)) {
            unlink("../" . $current_logo_url);
        }

        // อัปโหลดโลโก้ใหม่
        $target_dir = "../assets/img/sponsors/";
        // ตรวจสอบและสร้าง directory ถ้ายังไม่มี
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $image_name = time() . '_' . basename($_FILES["logo"]["name"]);
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $new_logo_url = "assets/img/sponsors/" . $image_name; // อัปเดต URL ใหม่
        } else {
            // จัดการข้อผิดพลาดในการอัปโหลดไฟล์
            error_log("Error uploading new logo for sponsor ID: " . $sponsor_id_post);
            // คุณอาจจะแจ้งผู้ใช้ว่าอัปโหลดไฟล์ไม่สำเร็จ
        }
    }

    // --- อัปเดตข้อมูลผู้สนับสนุนในฐานข้อมูล ---
    $sql_update = "UPDATE sponsors SET name = :name, logo_url = :logo_url, website_url = :website_url, display_order = :display_order WHERE id = :id";
    
    try {
        $stmt_update = $conn->prepare($sql_update);
        // ผูกค่า Parameters
        $stmt_update->bindParam(':name', $_POST['name'], PDO::PARAM_STR);
        $stmt_update->bindParam(':logo_url', $new_logo_url, PDO::PARAM_STR);
        $stmt_update->bindParam(':website_url', $_POST['website_url'], PDO::PARAM_STR);
        $stmt_update->bindParam(':display_order', $_POST['display_order'], PDO::PARAM_INT);
        $stmt_update->bindParam(':id', $sponsor_id_post, PDO::PARAM_INT);

        $stmt_update->execute();
        
        header("Location: manage_sponsors.php"); // Redirect เมื่ออัปเดตสำเร็จ
        exit();

    } catch (PDOException $e) {
        error_log("Database error updating sponsor: " . $e->getMessage());
        echo "มีข้อผิดพลาดในการบันทึกข้อมูลผู้สนับสนุน: " . $e->getMessage();
        // คุณสามารถจัดการข้อผิดพลาดตามความเหมาะสม เช่น แสดงข้อความ error หรือ redirect
    }
}

// --- ส่วนดึงข้อมูลผู้สนับสนุนเพื่อแสดงผลในฟอร์ม (GET Request) ---
$sponsor = null; // กำหนดค่าเริ่มต้นเป็น null
$sql_select = "SELECT * FROM sponsors WHERE id = :id";
try {
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bindParam(':id', $sponsor_id, PDO::PARAM_INT);
    $stmt_select->execute();
    $sponsor = $stmt_select->fetch(PDO::FETCH_ASSOC); // ดึงข้อมูลแถวเดียว

    // ถ้าไม่พบผู้สนับสนุนตาม ID ที่ระบุ
    if (!$sponsor) {
        header("location: manage_sponsors.php");
        exit();
    }

} catch (PDOException $e) {
    error_log("Database error fetching sponsor details: " . $e->getMessage());
    die("ไม่สามารถดึงข้อมูลผู้สนับสนุนได้: " . $e->getMessage());
}

?>
<style>
    .current-logo { max-width: 150px; max-height: 80px; object-fit: contain; display: block; margin: 10px 0; background: #eee; padding: 5px; border-radius: 4px; }
    .back-link { display: inline-block; margin-top: 15px; text-decoration: none; color: #007bff; }
</style>

<h1>แก้ไขผู้สนับสนุน</h1>
<div class="form-container">
    <form method="post" enctype="multipart/form-data" action="edit_sponsor.php?id=<?php echo htmlspecialchars($sponsor_id); ?>">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($sponsor['id'] ?? ''); ?>">
        <input type="hidden" name="current_logo_url" value="<?php echo htmlspecialchars($sponsor['logo_url'] ?? ''); ?>">
        
        <label>ชื่อผู้สนับสนุน</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($sponsor['name'] ?? ''); ?>" required>
        
        <label>ลิงก์เว็บไซต์ (URL)</label>
        <input type="text" name="website_url" value="<?php echo htmlspecialchars($sponsor['website_url'] ?? ''); ?>">
        
        <label>ลำดับการแสดงผล</label>
        <input type="number" name="display_order" value="<?php echo htmlspecialchars($sponsor['display_order'] ?? ''); ?>">
        
        <label>โลโก้ปัจจุบัน</label>
        <?php if (!empty($sponsor['logo_url'])): ?>
            <img src="../<?php echo htmlspecialchars($sponsor['logo_url']); ?>" class="current-logo" alt="Current Logo">
        <?php else: ?>
            <p>ไม่มีโลโก้ปัจจุบัน</p>
        <?php endif; ?>
        
        <label>เปลี่ยนโลโก้</label>
        <input type="file" name="logo" accept="image/*">
        
        <button type="submit" style="margin-top:10px; background-color: #007bff;">บันทึกการเปลี่ยนแปลง</button>
    </form>
    <a href="manage_sponsors.php" class="back-link">&laquo; กลับไปหน้าจัดการผู้สนับสนุน</a>
</div>

<?php require_once 'includes/admin_footer.php'; ?>