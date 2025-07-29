<?php
$active_page = 'teams';
$page_title = 'จัดการทีม';
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

// --- Handle Add Team Form (PDO) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_team'])) {
    $team_name = $_POST['team_name'] ?? '';
    $game_name = $_POST['game_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $logo_url = null; // กำหนดค่าเริ่มต้นเป็น null

    // --- จัดการการอัปโหลดไฟล์โลโก้ ---
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        // **ข้อควรระวังเรื่องการจัดการไฟล์บน Render (Stateless Service):**
        // การบันทึกไฟล์โดยตรงบน Server ของ Render (assets/img/logos/)
        // จะไม่ยั่งยืนใน Production เนื่องจากไฟล์จะหายไปเมื่อ Container ถูก Restart/rebuild
        // ควรพิจารณาใช้ Cloud Storage (เช่น AWS S3, Cloudinary) สำหรับไฟล์ที่ผู้ใช้อัปโหลด
        // แต่สำหรับตอนนี้ โค้ดจะถูกปรับให้ทำงานตามโครงสร้างเดิมโดยใช้ Path สัมพัทธ์

        $target_dir = "../assets/img/logos/";
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
            $logo_url = "assets/img/logos/" . $image_name;
        } else {
            error_log("Failed to move uploaded file for team: " . $target_file);
            // คุณอาจจะแจ้งผู้ใช้ว่าอัปโหลดไฟล์ไม่สำเร็จ
        }
    }

    $sql_insert = "INSERT INTO teams (team_name, game_name, logo_url, description) VALUES (:team_name, :game_name, :logo_url, :description)";
    
    try {
        $stmt_insert = $conn->prepare($sql_insert);
        // ผูกค่า Parameters
        $stmt_insert->bindParam(':team_name', $team_name, PDO::PARAM_STR);
        $stmt_insert->bindParam(':game_name', $game_name, PDO::PARAM_STR);
        $stmt_insert->bindParam(':logo_url', $logo_url, PDO::PARAM_STR);
        $stmt_insert->bindParam(':description', $description, PDO::PARAM_STR);
        
        $stmt_insert->execute();
        
        header("Location: manage_teams.php"); // Redirect เพื่อ Refresh หน้า
        exit();

    } catch (PDOException $e) {
        error_log("Database error adding new team: " . $e->getMessage());
        // คุณอาจจะแจ้งผู้ใช้ว่ามีข้อผิดพลาดในการเพิ่มข้อมูล
        echo "มีข้อผิดพลาดในการเพิ่มทีมใหม่: " . $e->getMessage();
    }
}

// --- ดึงรายชื่อทีมทั้งหมดมาแสดง (PDO) ---
$teams = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$teams_sql = "SELECT * FROM teams ORDER BY game_name ASC"; // เพิ่ม ASC เพื่อให้แน่ใจว่าเรียงลำดับเสมอ
try {
    $stmt_teams = $conn->query($teams_sql); // ใช้ query() สำหรับ SELECT ทั้งหมดที่ไม่มี Parameters
    $teams = $stmt_teams->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array
} catch (PDOException $e) {
    error_log("Database error fetching teams: " . $e->getMessage());
    // คุณอาจจะแสดงข้อความ error หรือแสดง array ว่างเปล่า
}

?>

<h1>จัดการทีม</h1>
<div class="form-container">
    <h2>สร้างทีมใหม่</h2>
    <form action="manage_teams.php" method="post" enctype="multipart/form-data">
        <label>ชื่อทีม</label><input type="text" name="team_name" required placeholder="เช่น TAE Esport DOTA2">
        <label>ชื่อเกม</label><input type="text" name="game_name" required placeholder="เช่น DOTA2">
        <label>คำอธิบายทีม (ไม่บังคับ)</label><textarea name="description" style="height:80px;"></textarea>
        <label>โลโก้ทีม</label><input type="file" name="logo" accept="image/*">
        <button type="submit" name="add_team" style="margin-top:10px;">สร้างทีม</button>
    </form>
</div>

<h2>ทีมทั้งหมดในระบบ</h2>
<table>
    <thead>
        <tr><th>โลโก้</th><th>ชื่อทีม</th><th>เกม</th><th>จัดการ</th></tr>
    </thead>
    <tbody>
    <?php if (!empty($teams)): // ตรวจสอบว่า $teams ไม่ว่างเปล่า ?>
        <?php foreach($teams as $row): // ใช้ foreach loop ?>
        <tr>
            <td>
                <img src="../<?php echo !empty($row['logo_url']) ? htmlspecialchars($row['logo_url']) : 'assets/img/default_logo.png'; ?>" 
                     alt="<?php echo htmlspecialchars($row['team_name'] ?? ''); ?>" 
                     style="max-width:50px; max-height: 50px; object-fit: contain;">
            </td>
            <td><?php echo htmlspecialchars($row['team_name'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($row['game_name'] ?? ''); ?></td>
            <td><a href="edit_team.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>">แก้ไข</a></td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="4" style="text-align:center;">ยังไม่มีทีมในระบบ</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?php require_once 'includes/admin_footer.php'; ?>