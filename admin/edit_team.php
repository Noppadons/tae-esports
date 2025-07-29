<?php
$active_page = 'teams';
$page_title = 'แก้ไขทีม';
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

$team_id = $_GET['id'] ?? 0;
if ($team_id == 0) {
    header("location: manage_teams.php");
    exit();
}

// --- Handle form submission for UPDATE (PDO) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $team_id_post = $_POST['id'];
    $current_logo_url_post = $_POST['current_logo_url'];
    $new_logo_url = $current_logo_url_post; // กำหนดค่าเริ่มต้นเป็น URL โลโก้เดิม

    // --- จัดการการอัปโหลดไฟล์โลโก้ใหม่ ---
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        // **ข้อควรระวังเรื่องการจัดการไฟล์บน Render (Stateless Service):**
        // การบันทึกและลบไฟล์โดยตรงบน Server ของ Render (assets/img/logos/)
        // จะไม่ยั่งยืนใน Production เนื่องจากไฟล์จะหายไปเมื่อ Container ถูก Restart/Rebuild
        // ควรพิจารณาใช้ Cloud Storage (เช่น AWS S3, Cloudinary) สำหรับไฟล์ที่ผู้ใช้อัปโหลด
        // แต่สำหรับตอนนี้ โค้ดจะถูกปรับให้ทำงานตามโครงสร้างเดิมโดยใช้ Path สัมพัทธ์

        // ลบโลโก้เก่า (ถ้ามีและไฟล์อยู่จริง)
        if (!empty($current_logo_url_post) && file_exists("../" . $current_logo_url_post)) {
            unlink("../" . $current_logo_url_post);
        }

        // อัปโหลดโลโก้ใหม่
        $target_dir = "../assets/img/logos/";
        // ตรวจสอบและสร้าง directory ถ้ายังไม่มี
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $image_name = time() . '_' . basename($_FILES["logo"]["name"]);
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $new_logo_url = "assets/img/logos/" . $image_name; // อัปเดต URL ใหม่
        } else {
            // จัดการข้อผิดพลาดในการอัปโหลดไฟล์
            error_log("Error uploading new logo for team ID: " . $team_id_post);
            // คุณอาจจะแจ้งผู้ใช้ว่าอัปโหลดไฟล์ไม่สำเร็จ
        }
    }

    $sql_update = "UPDATE teams SET team_name = :team_name, game_name = :game_name, logo_url = :logo_url, description = :description WHERE id = :id";
    
    try {
        $stmt_update = $conn->prepare($sql_update);
        // ผูกค่า Parameters
        $stmt_update->bindParam(':team_name', $_POST['team_name'], PDO::PARAM_STR);
        $stmt_update->bindParam(':game_name', $_POST['game_name'], PDO::PARAM_STR);
        $stmt_update->bindParam(':logo_url', $new_logo_url, PDO::PARAM_STR);
        $stmt_update->bindParam(':description', $_POST['description'], PDO::PARAM_STR);
        $stmt_update->bindParam(':id', $team_id_post, PDO::PARAM_INT);

        $stmt_update->execute();
        
        header("Location: manage_teams.php"); // Redirect เมื่ออัปเดตสำเร็จ
        exit();

    } catch (PDOException $e) {
        error_log("Database error updating team: " . $e->getMessage());
        echo "มีข้อผิดพลาดในการบันทึกข้อมูลทีม: " . $e->getMessage();
        // คุณสามารถจัดการข้อผิดพลาดตามความเหมาะสม
    }
}

// --- Fetch current team data for the form (PDO) ---
$team = null; // กำหนดค่าเริ่มต้นเป็น null
$sql_select_team = "SELECT * FROM teams WHERE id = :id";
try {
    $stmt_select_team = $conn->prepare($sql_select_team);
    $stmt_select_team->bindParam(':id', $team_id, PDO::PARAM_INT);
    $stmt_select_team->execute();
    $team = $stmt_select_team->fetch(PDO::FETCH_ASSOC); // ดึงข้อมูลแถวเดียว

    if (!$team) {
        header("location: manage_teams.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error fetching team details: " . $e->getMessage());
    die("ไม่สามารถดึงข้อมูลทีมได้: " . $e->getMessage());
}
?>

<h1>แก้ไขทีม: <?php echo htmlspecialchars($team['team_name'] ?? 'ไม่พบชื่อทีม'); ?></h1>
<div class="form-container">
    <form method="post" enctype="multipart/form-data" action="edit_team.php?id=<?php echo htmlspecialchars($team_id); ?>">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($team['id'] ?? ''); ?>">
        <input type="hidden" name="current_logo_url" value="<?php echo htmlspecialchars($team['logo_url'] ?? ''); ?>">
        
        <label>ชื่อทีม</label>
        <input type="text" name="team_name" value="<?php echo htmlspecialchars($team['team_name'] ?? ''); ?>" required>
        
        <label>ชื่อเกม</label>
        <input type="text" name="game_name" value="<?php echo htmlspecialchars($team['game_name'] ?? ''); ?>" required>
        
        <label>คำอธิบายทีม</label>
        <textarea name="description" style="height:100px;"><?php echo htmlspecialchars($team['description'] ?? ''); ?></textarea>
        
        <label>โลโก้ปัจจุบัน</label>
        <img src="../<?php echo !empty($team['logo_url']) ? htmlspecialchars($team['logo_url']) : 'assets/img/default_logo.png'; ?>" style="max-width: 100px; display: block; margin-bottom: 10px;" alt="Current Team Logo">
        
        <label>เปลี่ยนโลโก้</label>
        <input type="file" name="logo" accept="image/*">
        
        <button type="submit" style="margin-top:10px; background-color: #007bff;">บันทึกการเปลี่ยนแปลง</button>
    </form>
    <a href="manage_teams.php" style="display:inline-block; margin-top:15px;">&laquo; กลับไปหน้าจัดการทีม</a>
</div>

<?php require_once 'includes/admin_footer.php'; ?>