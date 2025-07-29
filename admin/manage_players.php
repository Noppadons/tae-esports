<?php
$active_page = 'players';
$page_title = 'จัดการนักกีฬา';
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

// --- Fetch all teams for the dropdown (PDO) ---
$teams_for_form = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
try {
    $stmt_teams = $conn->query("SELECT id, team_name FROM teams ORDER BY team_name");
    $teams_for_form = $stmt_teams->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมด
} catch (PDOException $e) {
    error_log("Database error fetching teams for form: " . $e->getMessage());
    // คุณอาจจะแสดงข้อความ error หรือจัดการตามความเหมาะสม
}

// --- Handle Add Player Form (PDO) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_player'])) {
    $team_id = $_POST['team_id'] ?? null;
    $ign = $_POST['ign'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $role = $_POST['role'] ?? '';
    $positions = $_POST['positions'] ?? '';
    $stat_game_sense = $_POST['stat_game_sense'] ?? 0;
    $stat_hero_pool = $_POST['stat_hero_pool'] ?? 0;
    $stat_reflex = $_POST['stat_reflex'] ?? 0;
    $stat_gameplay = $_POST['stat_gameplay'] ?? 0;
    $strengths = $_POST['strengths'] ?? '';
    $weaknesses = $_POST['weaknesses'] ?? '';
    $image_url = null; // กำหนดค่าเริ่มต้นเป็น null

    // --- จัดการการอัปโหลดไฟล์รูปภาพ ---
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // **ข้อควรระวังเรื่องการจัดการไฟล์บน Render (Stateless Service):**
        // การบันทึกไฟล์โดยตรงบน Server ของ Render (assets/img/players/)
        // จะไม่ยั่งยืนใน Production เนื่องจากไฟล์จะหายไปเมื่อ Container ถูก Restart/Rebuild
        // ควรพิจารณาใช้ Cloud Storage (เช่น AWS S3, Cloudinary) สำหรับไฟล์ที่ผู้ใช้อัปโหลด
        // แต่สำหรับตอนนี้ โค้ดจะถูกปรับให้ทำงานตามโครงสร้างเดิมโดยใช้ Path สัมพัทธ์

        $target_dir = "../assets/img/players/";
        // ตรวจสอบและสร้าง directory ถ้ายังไม่มี
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                error_log("Failed to create directory: " . $target_dir);
            }
        }
        
        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = "assets/img/players/" . $image_name;
        } else {
            error_log("Failed to move uploaded file for player: " . $target_file);
        }
    }

    $sql_insert = "INSERT INTO players (team_id, ign, full_name, role, image_url, positions, stat_game_sense, stat_hero_pool, stat_reflex, stat_gameplay, strengths, weaknesses) VALUES (:team_id, :ign, :full_name, :role, :image_url, :positions, :stat_game_sense, :stat_hero_pool, :stat_reflex, :stat_gameplay, :strengths, :weaknesses)";
    
    try {
        $stmt_insert = $conn->prepare($sql_insert);
        // ผูกค่า Parameters
        $stmt_insert->bindParam(':team_id', $team_id, PDO::PARAM_INT);
        $stmt_insert->bindParam(':ign', $ign, PDO::PARAM_STR);
        $stmt_insert->bindParam(':full_name', $full_name, PDO::PARAM_STR);
        $stmt_insert->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt_insert->bindParam(':image_url', $image_url, PDO::PARAM_STR);
        $stmt_insert->bindParam(':positions', $positions, PDO::PARAM_STR);
        $stmt_insert->bindParam(':stat_game_sense', $stat_game_sense, PDO::PARAM_INT);
        $stmt_insert->bindParam(':stat_hero_pool', $stat_hero_pool, PDO::PARAM_INT);
        $stmt_insert->bindParam(':stat_reflex', $stat_reflex, PDO::PARAM_INT);
        $stmt_insert->bindParam(':stat_gameplay', $stat_gameplay, PDO::PARAM_INT);
        $stmt_insert->bindParam(':strengths', $strengths, PDO::PARAM_STR);
        $stmt_insert->bindParam(':weaknesses', $weaknesses, PDO::PARAM_STR);
        
        $stmt_insert->execute();
        
        header("Location: manage_players.php"); // Redirect เพื่อ Refresh หน้า
        exit();

    } catch (PDOException $e) {
        error_log("Database error adding new player: " . $e->getMessage());
        echo "มีข้อผิดพลาดในการเพิ่มนักกีฬาใหม่: " . $e->getMessage();
    }
}

// --- ดึงรายการนักกีฬาทั้งหมดมาแสดง (PDO) ---
$players = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$players_sql = "
    SELECT p.id, p.ign, p.full_name, p.role, p.image_url, t.team_name 
    FROM players p
    LEFT JOIN teams t ON p.team_id = t.id
    ORDER BY p.ign ASC
";
try {
    $stmt_players = $conn->query($players_sql); // ใช้ query() สำหรับ SELECT ทั้งหมดที่ไม่มี Parameters
    $players = $stmt_players->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array
} catch (PDOException $e) {
    error_log("Database error fetching players: " . $e->getMessage());
    // คุณอาจจะแสดงข้อความ error หรือแสดง array ว่างเปล่า
}

?>
<style>
    /* ... (CSS styles remains unchanged, if any. Styles from previous example for forms/tables are assumed to be present) ... */
    textarea { height: 100px; }
    .current-image { max-width: 150px; display: block; margin-bottom: 10px; border-radius: 4px; } /* Changed margin-bottom */
    hr { border: none; border-top: 1px solid #eee; margin: 20px 0;}
    .back-link { display: inline-block; margin-top: 15px; text-decoration: none; color: #007bff; }
    /* Form & Table styles (assume from admin_header or global CSS) */
    .form-container { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); margin-bottom: 30px; }
    .form-container h2 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    label { display: block; margin-bottom: 8px; font-weight: bold; }
    input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 8px; margin: 6px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    input[type="file"] { margin-bottom: 15px; }
    button[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 10px; }
    button[type="submit"]:hover { background-color: #0056b3; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .grid-2-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; } /* Re-added from edit_player context */
</style>

<h1>จัดการนักกีฬา</h1>
<div class="form-container">
    <h2>เพิ่มนักกีฬาใหม่</h2>
    <form action="manage_players.php" method="post" enctype="multipart/form-data">
        <label>สังกัดทีม</label>
        <select name="team_id" required>
            <option value="">-- กรุณาเลือกทีม --</option>
            <?php foreach ($teams_for_form as $team): ?>
                <option value="<?php echo htmlspecialchars($team['id'] ?? ''); ?>"><?php echo htmlspecialchars($team['team_name'] ?? ''); ?></option>
            <?php endforeach; ?>
        </select>
        
        <div class="grid-2-col">
            <div>
                <label>ชื่อในเกม (IGN)</label><input type="text" name="ign" required>
                <label>ชื่อ-นามสกุล</label><input type="text" name="full_name" required>
                <label>บทบาท (Role)</label><input type="text" name="role">
                <label>ตำแหน่ง (Positions 1-5)</label><input type="text" name="positions">
            </div>
            <div>
                <label>รูปภาพนักกีฬา</label><input type="file" name="image" accept="image/*">
            </div>
        </div>
        
        <hr><h3>ค่าสถานะ (0-100)</h3>
        <div class="grid-2-col">
            <div>
                <label>อ่านเกม</label><input type="number" name="stat_game_sense" min="0" max="100" value="0">
                <label>ฮีโร่พูล</label><input type="number" name="stat_hero_pool" min="0" max="100" value="0">
            </div>
            <div>
                <label>รีเฟล็กซ์</label><input type="number" name="stat_reflex" min="0" max="100" value="0">
                <label>เกมเพลย์</label><input type="number" name="stat_gameplay" min="0" max="100" value="0">
            </div>
        </div>

        <hr><h3>จุดเด่น / จุดด้อย</h3>
        <div class="grid-2-col">
            <div><label>จุดเด่น</label><textarea name="strengths"></textarea></div>
            <div><label>จุดด้อย</label><textarea name="weaknesses"></textarea></div>
        </div>

        <button type="submit" name="add_player" style="margin-top:20px;">เพิ่มนักกีฬา</button>
    </form>
</div>

<h2>รายการนักกีฬาทั้งหมด</h2>
<table>
    <thead>
        <tr>
            <th>รูป</th>
            <th>IGN</th>
            <th>ชื่อเต็ม</th>
            <th>ทีม</th>
            <th>บทบาท</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($players)): // ตรวจสอบว่า $players ไม่ว่างเปล่า ?>
            <?php foreach($players as $row): // ใช้ foreach loop ?>
            <tr>
                <td>
                    <img src="../<?php echo !empty($row['image_url']) ? htmlspecialchars($row['image_url']) : 'assets/img/default_player.png'; ?>" alt="<?php echo htmlspecialchars($row['ign'] ?? ''); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                </td>
                <td><?php echo htmlspecialchars($row['ign'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['full_name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['team_name'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['role'] ?? '-'); ?></td>
                <td>
                    <a href="edit_player.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>">แก้ไข</a> |
                    <a href="delete_player.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>" onclick="return confirm('ยืนยันการลบนักกีฬา: <?php echo htmlspecialchars($row['ign'] ?? ''); ?>?');">ลบ</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center;">ยังไม่มีข้อมูลนักกีฬาในระบบ</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once 'includes/admin_footer.php'; ?>