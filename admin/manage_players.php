<?php
$active_page = 'players';
$page_title = 'จัดการนักกีฬา';
require_once 'includes/admin_header.php';

// --- Fetch all teams for the dropdown ---
$teams_result_for_form = $conn->query("SELECT id, team_name FROM teams ORDER BY team_name");

// --- Handle Add Player Form ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_player'])) {
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/img/players/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = "assets/img/players/" . $image_name;
        }
    }

    $sql = "INSERT INTO players (team_id, ign, full_name, role, image_url, positions, stat_game_sense, stat_hero_pool, stat_reflex, stat_gameplay, strengths, weaknesses) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isssssiiisss",
            $_POST['team_id'], $_POST['ign'], $_POST['full_name'], $_POST['role'], $image_url,
            $_POST['positions'], $_POST['stat_game_sense'], $_POST['stat_hero_pool'],
            $_POST['stat_reflex'], $_POST['stat_gameplay'], $_POST['strengths'], $_POST['weaknesses']
        );
        $stmt->execute();
    }
    header("Location: manage_players.php");
    exit();
}

// Fetch all players with their team name using a JOIN
$players_result = $conn->query("
    SELECT p.*, t.team_name 
    FROM players p
    LEFT JOIN teams t ON p.team_id = t.id
    ORDER BY t.team_name, p.ign
");
?>

<style>
    textarea { height: 80px; }
    td img { max-width: 50px; border-radius: 5px; }
    hr { border: none; border-top: 1px solid #eee; margin: 20px 0;}
</style>

<h1>จัดการข้อมูลนักกีฬา</h1>
<div class="form-container">
    <h2>เพิ่มนักกีฬาใหม่</h2>
    <form action="manage_players.php" method="post" enctype="multipart/form-data">
        <div class="grid-2-col">
            <div>
                <label>สังกัดทีม</label>
                <select name="team_id" required>
                    <option value="">-- กรุณาเลือกทีม --</option>
                    <?php
                    if ($teams_result_for_form->num_rows > 0) {
                        while($team = $teams_result_for_form->fetch_assoc()) {
                            echo '<option value="' . $team['id'] . '">' . htmlspecialchars($team['team_name']) . '</option>';
                        }
                    }
                    ?>
                </select>
                <label>ชื่อในเกม (IGN)</label><input type="text" name="ign" required>
                <label>ชื่อ-นามสกุล</label><input type="text" name="full_name">
                <label>บทบาท (Role)</label><input type="text" name="role" placeholder="เช่น Entry Fragger, Support">
                <label>ตำแหน่ง (Positions 1-5)</label><input type="text" name="positions" placeholder="เช่น 1 2 4">
            </div>
            <div>
                <label>รูปภาพนักกีฬา</label><input type="file" name="image" accept="image/*">
                <hr>
                <h3>ค่าสถานะ (0-100)</h3>
                <div class="grid-2-col">
                    <div>
                        <label>อ่านเกม</label><input type="number" name="stat_game_sense" value="70" min="0" max="100">
                        <label>ฮีโร่พูล</label><input type="number" name="stat_hero_pool" value="70" min="0" max="100">
                    </div>
                    <div>
                        <label>รีเฟล็กซ์</label><input type="number" name="stat_reflex" value="70" min="0" max="100">
                        <label>เกมเพลย์</label><input type="number" name="stat_gameplay" value="70" min="0" max="100">
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <h3>จุดเด่น / จุดด้อย</h3>
        <div class="grid-2-col">
            <div><label>จุดเด่น</label><textarea name="strengths"></textarea></div>
            <div><label>จุดด้อย</label><textarea name="weaknesses"></textarea></div>
        </div>
        <button type="submit" name="add_player" style="margin-top: 20px;">เพิ่มนักกีฬา</button>
    </form>
</div>

<h2>รายชื่อนักกีฬาทั้งหมด</h2>
<table>
    <thead>
        <tr><th>รูป</th><th>IGN</th><th>บทบาท</th><th>สังกัดทีม</th><th>จัดการ</th></tr>
    </thead>
    <tbody>
        <?php while($row = $players_result->fetch_assoc()): ?>
        <tr>
            <td><img src="../<?php echo !empty($row['image_url']) ? htmlspecialchars($row['image_url']) : 'assets/img/default_player.png'; ?>" alt="player photo"></td>
            <td><?php echo htmlspecialchars($row['ign']); ?></td>
            <td><?php echo htmlspecialchars($row['role']); ?></td>
            <td><?php echo htmlspecialchars($row['team_name'] ?? '<i>ยังไม่สังกัดทีม</i>'); ?></td>
            <td>
                <a href="edit_player.php?id=<?php echo $row['id']; ?>">แก้ไข</a> |
                <a href="delete_player.php?id=<?php echo $row['id']; ?>" onclick="return confirm('ยืนยันการลบ?');">ลบ</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php require_once 'includes/admin_footer.php'; ?>