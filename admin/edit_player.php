<?php
$active_page = 'players';
$page_title = 'แก้ไขข้อมูลนักกีฬา';
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

$player_id = $_GET['id'] ?? 0;
if ($player_id == 0) {
    header("location: manage_players.php");
    exit();
}

// --- Fetch all teams for the dropdown (PDO) ---
$teams_for_form = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
try {
    $stmt_teams = $conn->query("SELECT id, team_name FROM teams ORDER BY team_name");
    $teams_for_form = $stmt_teams->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมด
} catch (PDOException $e) {
    error_log("Database error fetching teams for form: " . $e->getMessage());
    // คุณอาจจะแสดงข้อความ error หรือจัดการตามความเหมาะสม
}


// --- Handle form submission for UPDATE (PDO) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $player_id_post = $_POST['id'];
    $current_image_url_post = $_POST['current_image_url'];
    $new_image_url = $current_image_url_post; // กำหนดค่าเริ่มต้นเป็น URL รูปภาพเดิม

    // --- จัดการการอัปโหลดไฟล์รูปภาพใหม่ ---
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // **ข้อควรระวังเรื่องการจัดการไฟล์บน Render (Stateless Service):**
        // การบันทึกและลบไฟล์โดยตรงบน Server ของ Render (assets/img/players/)
        // จะไม่ยั่งยืนใน Production เนื่องจากไฟล์จะหายไปเมื่อ Container ถูก Restart/Rebuild
        // ควรพิจารณาใช้ Cloud Storage (เช่น AWS S3, Cloudinary) สำหรับไฟล์ที่ผู้ใช้อัปโหลด
        // แต่สำหรับตอนนี้ โค้ดจะถูกปรับให้ทำงานตามโครงสร้างเดิมโดยใช้ Path สัมพัทธ์

        // ลบรูปภาพเก่า (ถ้ามีและไฟล์อยู่จริง)
        if (!empty($current_image_url_post) && file_exists("../" . $current_image_url_post)) {
            unlink("../" . $current_image_url_post);
        }

        // อัปโหลดรูปภาพใหม่
        $target_dir = "../assets/img/players/";
        // ตรวจสอบและสร้าง directory ถ้ายังไม่มี
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $new_image_url = "assets/img/players/" . $image_name; // อัปเดต URL ใหม่
        } else {
            // จัดการข้อผิดพลาดในการอัปโหลดไฟล์
            error_log("Error uploading new image for player ID: " . $player_id_post);
            // คุณอาจจะแจ้งผู้ใช้ว่าอัปโหลดไฟล์ไม่สำเร็จ
        }
    }

    $sql_update = "UPDATE players SET team_id = :team_id, ign = :ign, full_name = :full_name, role = :role, image_url = :image_url, positions = :positions, stat_game_sense = :stat_game_sense, stat_hero_pool = :stat_hero_pool, stat_reflex = :stat_reflex, stat_gameplay = :stat_gameplay, strengths = :strengths, weaknesses = :weaknesses WHERE id = :id";
    
    try {
        $stmt_update = $conn->prepare($sql_update);
        // ผูกค่า Parameters
        $stmt_update->bindParam(':team_id', $_POST['team_id'], PDO::PARAM_INT);
        $stmt_update->bindParam(':ign', $_POST['ign'], PDO::PARAM_STR);
        $stmt_update->bindParam(':full_name', $_POST['full_name'], PDO::PARAM_STR);
        $stmt_update->bindParam(':role', $_POST['role'], PDO::PARAM_STR);
        $stmt_update->bindParam(':image_url', $new_image_url, PDO::PARAM_STR);
        $stmt_update->bindParam(':positions', $_POST['positions'], PDO::PARAM_STR);
        $stmt_update->bindParam(':stat_game_sense', $_POST['stat_game_sense'], PDO::PARAM_INT);
        $stmt_update->bindParam(':stat_hero_pool', $_POST['stat_hero_pool'], PDO::PARAM_INT);
        $stmt_update->bindParam(':stat_reflex', $_POST['stat_reflex'], PDO::PARAM_INT);
        $stmt_update->bindParam(':stat_gameplay', $_POST['stat_gameplay'], PDO::PARAM_INT);
        $stmt_update->bindParam(':strengths', $_POST['strengths'], PDO::PARAM_STR);
        $stmt_update->bindParam(':weaknesses', $_POST['weaknesses'], PDO::PARAM_STR);
        $stmt_update->bindParam(':id', $player_id_post, PDO::PARAM_INT);

        $stmt_update->execute();
        
        header("Location: manage_players.php"); // Redirect เมื่ออัปเดตสำเร็จ
        exit();

    } catch (PDOException $e) {
        error_log("Database error updating player: " . $e->getMessage());
        echo "มีข้อผิดพลาดในการบันทึกข้อมูลนักกีฬา: " . $e->getMessage();
        // คุณสามารถจัดการข้อผิดพลาดตามความเหมาะสม
    }
}

// --- Fetch current player data for the form (PDO) ---
$player = null; // กำหนดค่าเริ่มต้น
$sql_select_player = "SELECT * FROM players WHERE id = :id";
try {
    $stmt_select_player = $conn->prepare($sql_select_player);
    $stmt_select_player->bindParam(':id', $player_id, PDO::PARAM_INT);
    $stmt_select_player->execute();
    $player = $stmt_select_player->fetch(PDO::FETCH_ASSOC); // ดึงข้อมูลแถวเดียว

    if (!$player) {
        header("location: manage_players.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error fetching player details: " . $e->getMessage());
    die("ไม่สามารถดึงข้อมูลนักกีฬาได้: " . $e->getMessage());
}
?>
<style>
    textarea { height: 100px; }
    .current-image { max-width: 150px; display: block; margin: 10px 0; border-radius: 5px; }
    hr { border: none; border-top: 1px solid #eee; margin: 20px 0;}
    .back-link { display: inline-block; margin-top: 15px; text-decoration: none; color: #007bff; }
    /* ... (CSS styles remains unchanged from your previous provided code) ... */
    .grid-2-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
</style>

<h1>แก้ไขข้อมูลนักกีฬา: <?php echo htmlspecialchars($player['ign'] ?? 'ไม่พบชื่อ'); ?></h1>
<div class="form-container">
    <form method="post" enctype="multipart/form-data" action="edit_player.php?id=<?php echo htmlspecialchars($player_id); ?>">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($player['id'] ?? ''); ?>">
        <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($player['image_url'] ?? ''); ?>">
        
        <label>สังกัดทีม</label>
        <select name="team_id" required>
            <option value="">-- กรุณาเลือกทีม --</option>
            <?php
            // วนลูปแสดงตัวเลือกทีม
            foreach ($teams_for_form as $team) {
                $selected = (isset($player['team_id']) && $player['team_id'] == $team['id']) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($team['id']) . '" ' . $selected . '>' . htmlspecialchars($team['team_name']) . '</option>';
            }
            ?>
        </select>
        
        <div class="grid-2-col">
            <div>
                <label>ชื่อในเกม (IGN)</label><input type="text" name="ign" value="<?php echo htmlspecialchars($player['ign'] ?? ''); ?>">
                <label>ชื่อ-นามสกุล</label><input type="text" name="full_name" value="<?php echo htmlspecialchars($player['full_name'] ?? ''); ?>">
                <label>บทบาท (Role)</label><input type="text" name="role" value="<?php echo htmlspecialchars($player['role'] ?? ''); ?>">
                <label>ตำแหน่ง (Positions 1-5)</label><input type="text" name="positions" value="<?php echo htmlspecialchars($player['positions'] ?? ''); ?>">
            </div>
            <div>
                <label>รูปภาพปัจจุบัน</label>
                <img src="../<?php echo !empty($player['image_url']) ? htmlspecialchars($player['image_url']) : 'assets/img/default_player.png'; ?>" class="current-image" alt="Current Player Image">
                <label>เปลี่ยนรูปภาพ</label><input type="file" name="image" accept="image/*">
            </div>
        </div>
        
        <hr><h3>ค่าสถานะ (0-100)</h3>
        <div class="grid-2-col">
            <div>
                <label>อ่านเกม</label><input type="number" name="stat_game_sense" value="<?php echo htmlspecialchars($player['stat_game_sense'] ?? ''); ?>" min="0" max="100">
                <label>ฮีโร่พูล</label><input type="number" name="stat_hero_pool" value="<?php echo htmlspecialchars($player['stat_hero_pool'] ?? ''); ?>" min="0" max="100">
            </div>
            <div>
                <label>รีเฟล็กซ์</label><input type="number" name="stat_reflex" value="<?php echo htmlspecialchars($player['stat_reflex'] ?? ''); ?>" min="0" max="100">
                <label>เกมเพลย์</label><input type="number" name="stat_gameplay" value="<?php echo htmlspecialchars($player['stat_gameplay'] ?? ''); ?>" min="0" max="100">
            </div>
        </div>

        <hr><h3>จุดเด่น / จุดด้อย</h3>
        <div class="grid-2-col">
            <div><label>จุดเด่น</label><textarea name="strengths"><?php echo htmlspecialchars($player['strengths'] ?? ''); ?></textarea></div>
            <div><label>จุดด้อย</label><textarea name="weaknesses"><?php echo htmlspecialchars($player['weaknesses'] ?? ''); ?></textarea></div>
        </div>

        <button type="submit" style="margin-top:20px; background-color:#007bff;">บันทึกการเปลี่ยนแปลง</button>
    </form>
    <a href="manage_players.php" class="back-link">&laquo; กลับไปหน้าจัดการนักกีฬา</a>
</div>

<?php require_once 'includes/admin_footer.php'; ?>