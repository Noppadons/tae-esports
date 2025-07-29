<?php
session_start();
require_once '../includes/db_connect.php';

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    die("Database connection failed. Please check ../includes/db_connect.php");
}

// Security check (ตรวจสอบสิทธิ์ Admin)
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}

// ตรวจสอบ ID ที่ส่งมาใน URL
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
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขผู้สนับสนุน</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f8f9fa; }
        .sidebar { height: 100%; width: 250px; position: fixed; z-index: 1; top: 0; left: 0; background-color: #343a40; padding-top: 20px; overflow-y: auto; }
        .sidebar a { padding: 10px 15px; text-decoration: none; font-size: 1.1rem; color: #adb5bd; display: block; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #495057; }
        .main-content { margin-left: 260px; padding: 20px; }
        .header { background-color: #fff; padding: 10px 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .logout-btn { color: #dc3545; text-decoration: none; font-weight: bold; }
        .form-container { max-width: 800px; background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input[type=text], input[type=number], input[type=file] { width: 100%; padding: 8px; margin: 6px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .current-logo { max-width: 150px; max-height: 80px; object-fit: contain; display: block; margin: 10px 0; background: #eee; padding: 5px; border-radius: 4px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3 style="color: white; text-align: center;">TAE Esport Admin</h3>
    <a href="dashboard.php">Dashboard</a>
    <a href="manage_teams.php">จัดการทีม</a>
    <a href="manage_players.php">จัดการนักกีฬา</a>
    <a href="manage_matches.php">จัดการตารางแข่ง</a>
    <a href="manage_news.php">จัดการข่าวสาร</a>
    <a href="manage_gallery.php">จัดการแกลเลอรี่</a>
    <a href="manage_sponsors.php" class="active">จัดการผู้สนับสนุน</a>
</div>

<div class="main-content">
    <div class="header">
        <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION["admin_username"]); ?></strong></span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <h1>แก้ไขผู้สนับสนุน</h1>
    <div class="form-container">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($sponsor['id'] ?? ''); ?>">
            <input type="hidden" name="current_logo_url" value="<?php echo htmlspecialchars($sponsor['logo_url'] ?? ''); ?>">
            <label>ชื่อผู้สนับสนุน</label><input type="text" name="name" value="<?php echo htmlspecialchars($sponsor['name'] ?? ''); ?>" required>
            <label>ลิงก์เว็บไซต์ (URL)</label><input type="text" name="website_url" value="<?php echo htmlspecialchars($sponsor['website_url'] ?? ''); ?>">
            <label>ลำดับการแสดงผล</label><input type="number" name="display_order" value="<?php echo htmlspecialchars($sponsor['display_order'] ?? ''); ?>">
            <label>โลโก้ปัจจุบัน</label>
            <?php if (!empty($sponsor['logo_url'])): ?>
                <img src="../<?php echo htmlspecialchars($sponsor['logo_url']); ?>" class="current-logo">
            <?php else: ?>
                <p>ไม่มีโลโก้ปัจจุบัน</p>
            <?php endif; ?>
            <label>เปลี่ยนโลโก้</label><input type="file" name="logo" accept="image/*">
            <button type="submit" style="margin-top:10px;">บันทึกการเปลี่ยนแปลง</button>
        </form>
    </div>
</div>

</body>
</html>