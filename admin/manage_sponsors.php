<?php
$active_page = 'sponsors';
$page_title = 'จัดการผู้สนับสนุน';
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

// --- Handle Add Sponsor Form (PDO) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_sponsor'])) {
    $name = $_POST['name'] ?? '';
    $website_url = $_POST['website_url'] ?? '';
    $display_order = $_POST['display_order'] ?? 0;
    $logo_url = ''; // กำหนดค่าเริ่มต้นเป็น string ว่าง

    // --- จัดการการอัปโหลดไฟล์โลโก้ ---
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        // **ข้อควรระวังเรื่องการจัดการไฟล์บน Render (Stateless Service):**
        // การบันทึกไฟล์โดยตรงบน Server ของ Render (assets/img/sponsors/)
        // จะไม่ยั่งยืนใน Production เนื่องจากไฟล์จะหายไปเมื่อ Container ถูก Restart/Rebuild
        // ควรพิจารณาใช้ Cloud Storage (เช่น AWS S3, Cloudinary) สำหรับไฟล์ที่ผู้ใช้อัปโหลด
        // แต่สำหรับตอนนี้ โค้ดจะถูกปรับให้ทำงานตามโครงสร้างเดิมโดยใช้ Path สัมพัทธ์

        $target_dir = "../assets/img/sponsors/";
        // ตรวจสอบและสร้าง directory ถ้ายังไม่มี
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) { // ตั้งค่า permission ที่เหมาะสม
                error_log("Failed to create directory: " . $target_dir);
                // คุณอาจจะแจ้งผู้ใช้ว่าสร้างโฟลเดอร์ไม่สำเร็จ
            }
        }
        
        $image_name = time() . '_' . basename($_FILES["logo"]["name"]);
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $logo_url = "assets/img/sponsors/" . $image_name;
        } else {
            error_log("Failed to move uploaded file for sponsor: " . $target_file);
            // คุณอาจจะแจ้งผู้ใช้ว่าอัปโหลดไฟล์ไม่สำเร็จ
        }
    }

    $sql_insert = "INSERT INTO sponsors (name, logo_url, website_url, display_order) VALUES (:name, :logo_url, :website_url, :display_order)";
    
    try {
        $stmt_insert = $conn->prepare($sql_insert);
        // ผูกค่า Parameters
        $stmt_insert->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt_insert->bindParam(':logo_url', $logo_url, PDO::PARAM_STR);
        $stmt_insert->bindParam(':website_url', $website_url, PDO::PARAM_STR);
        $stmt_insert->bindParam(':display_order', $display_order, PDO::PARAM_INT);
        
        $stmt_insert->execute();
        
        header("Location: manage_sponsors.php"); // Redirect เพื่อ Refresh หน้า
        exit();

    } catch (PDOException $e) {
        error_log("Database error adding new sponsor: " . $e->getMessage());
        // คุณอาจจะแจ้งผู้ใช้ว่ามีข้อผิดพลาดในการเพิ่มข้อมูล
        echo "มีข้อผิดพลาดในการเพิ่มผู้สนับสนุนใหม่: " . $e->getMessage();
    }
}

// --- ดึงรายชื่อผู้สนับสนุนทั้งหมดมาแสดง (PDO) ---
$sponsors = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$sponsors_sql = "SELECT * FROM sponsors ORDER BY display_order ASC, name ASC";
try {
    $stmt_sponsors = $conn->query($sponsors_sql); // ใช้ query() สำหรับ SELECT ทั้งหมดที่ไม่มี Parameters
    $sponsors = $stmt_sponsors->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array
} catch (PDOException $e) {
    error_log("Database error fetching sponsors: " . $e->getMessage());
    // คุณอาจจะแสดงข้อความ error หรือแสดง array ว่างเปล่า
}

?>
<style>
    td img { max-width: 100px; max-height: 40px; object-fit: contain; }
    /* เพิ่ม style สำหรับ form, container และ table ถ้ายังไม่มีจาก admin_header */
    .form-container { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); margin-bottom: 30px; }
    .form-container h2 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    label { display: block; margin-bottom: 8px; font-weight: bold; }
    input[type="text"], input[type="number"], input[type="file"] { width: 100%; padding: 8px; margin: 6px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    button[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 10px; }
    button[type="submit"]:hover { background-color: #0056b3; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>

<h1>จัดการผู้สนับสนุน</h1>
<div class="form-container">
    <h2>เพิ่มผู้สนับสนุนใหม่</h2>
    <form action="manage_sponsors.php" method="post" enctype="multipart/form-data">
        <label>ชื่อผู้สนับสนุน</label><input type="text" name="name" required>
        <label>ลิงก์เว็บไซต์ (URL)</label><input type="text" name="website_url" placeholder="https://www.example.com">
        <label>ลำดับการแสดงผล (เลขน้อยขึ้นก่อน)</label><input type="number" name="display_order" value="0">
        <label>โลโก้</label><input type="file" name="logo" accept="image/*" required>
        <button type="submit" name="add_sponsor" style="margin-top:10px;">เพิ่มผู้สนับสนุน</button>
    </form>
</div>

<h2>รายชื่อผู้สนับสนุนทั้งหมด</h2>
<table>
    <thead>
        <tr><th>โลโก้</th><th>ชื่อ</th><th>ลำดับ</th><th>จัดการ</th></tr>
    </thead>
    <tbody>
    <?php if (!empty($sponsors)): // ตรวจสอบว่า $sponsors ไม่ว่างเปล่า ?>
        <?php foreach($sponsors as $row): // ใช้ foreach loop ?>
            <tr>
                <td><img src="../<?php echo !empty($row['logo_url']) ? htmlspecialchars($row['logo_url']) : 'assets/img/default_logo.png'; ?>" alt="<?php echo htmlspecialchars($row['name'] ?? ''); ?>"></td>
                <td><a href="<?php echo htmlspecialchars($row['website_url'] ?? ''); ?>" target="_blank"><?php echo htmlspecialchars($row['name'] ?? ''); ?></a></td>
                <td><?php echo htmlspecialchars($row['display_order'] ?? ''); ?></td>
                <td>
                    <a href="edit_sponsor.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>">แก้ไข</a> |
                    <a href="delete_sponsor.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>" onclick="return confirm('ยืนยันการลบผู้สนับสนุน: <?php echo htmlspecialchars($row['name'] ?? ''); ?>?');">ลบ</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="4" style="text-align:center;">ยังไม่มีผู้สนับสนุนในระบบ</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?php require_once 'includes/admin_footer.php'; ?>