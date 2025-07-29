<?php
$active_page = 'players';
$page_title = 'แก้ไขข้อมูลนักกีฬา';
require_once 'includes/admin_header.php';

$player_id = $_GET['id'] ?? 0;
if ($player_id == 0) { header("location: manage_players.php"); exit(); }

// --- Fetch all teams for the dropdown ---
$teams_result_for_form = $conn->query("SELECT id, team_name FROM teams ORDER BY team_name");

// --- Handle form submission for UPDATE ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $player_id = $_POST['id'];
    $current_image_url = $_POST['current_image_url'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        if(!empty($current_image_url) && file_exists("../".$current_image_url)) { unlink("../".$current_image_url); }
        $target_dir = "../assets/img/players/";
        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $current_image_url = "assets/img/players/" . $image_name;
        }
    }

    $sql = "UPDATE players SET team_id=?, ign=?, full_name=?, role=?, image_url=?, positions=?, stat_game_sense=?, stat_hero_pool=?, stat_reflex=?, stat_gameplay=?, strengths=?, weaknesses=? WHERE id=?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isssssiiisssi",
            $_POST['team_id'], $_POST['ign'], $_POST['full_name'], $_POST['role'], $current_image_url,
            $_POST['positions'], $_POST['stat_game_sense'], $_POST['stat_hero_pool'],
            $_POST['stat_reflex'], $_POST['stat_gameplay'], $_POST['strengths'], $_POST['weaknesses'],
            $player_id
        );
        $stmt->execute();
        header("Location: manage_players.php");
        exit();
    }
}

// --- Fetch current player data for the form ---
$sql = "SELECT * FROM players WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $player_id);
$stmt->execute();
$player = $stmt->get_result()->fetch_assoc();
if (!$player) { header("location: manage_players.php"); exit(); }
?>
<style>
    textarea { height: 100px; }
    .current-image { max-width: 150px; display: block; margin: 10px 0; border-radius: 5px; }
    hr { border: none; border-top: 1px solid #eee; margin: 20px 0;}
    .back-link { display: inline-block; margin-top: 15px; text-decoration: none; color: #007bff; }
</style>

<h1>แก้ไขข้อมูลนักกีฬา: <?php echo htmlspecialchars($player['ign']); ?></h1>
<div class="form-container">
    <form method="post" enctype="multipart/form-data" action="edit_player.php?id=<?php echo $player_id; ?>">
        <input type="hidden" name="id" value="<?php echo $player['id']; ?>">
        <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($player['image_url']); ?>">
        
        <label>สังกัดทีม</label>
        <select name="team_id" required>
            <option value="">-- กรุณาเลือกทีม --</option>
            <?php
            $teams_result_for_form->data_seek(0); 
            if ($teams_result_for_form->num_rows > 0) {
                while($team = $teams_result_for_form->fetch_assoc()) {
                    $selected = ($player['team_id'] == $team['id']) ? 'selected' : '';
                    echo '<option value="' . $team['id'] . '" ' . $selected . '>' . htmlspecialchars($team['team_name']) . '</option>';
                }
            }
            ?>
        </select>
        
        <div class="grid-2-col">
            <div>
                <label>ชื่อในเกม (IGN)</label><input type="text" name="ign" value="<?php echo htmlspecialchars($player['ign']); ?>">
                <label>ชื่อ-นามสกุล</label><input type="text" name="full_name" value="<?php echo htmlspecialchars($player['full_name']); ?>">
                <label>บทบาท (Role)</label><input type="text" name="role" value="<?php echo htmlspecialchars($player['role']); ?>">
                <label>ตำแหน่ง (Positions 1-5)</label><input type="text" name="positions" value="<?php echo htmlspecialchars($player['positions']); ?>">
            </div>
            <div>
                <label>รูปภาพปัจจุบัน</label>
                <img src="../<?php echo !empty($player['image_url']) ? htmlspecialchars($player['image_url']) : 'assets/img/default_player.png'; ?>" class="current-image">
                <label>เปลี่ยนรูปภาพ</label><input type="file" name="image" accept="image/*">
            </div>
        </div>
        
        <hr><h3>ค่าสถานะ (0-100)</h3>
        <div class="grid-2-col">
            <div>
                <label>อ่านเกม</label><input type="number" name="stat_game_sense" value="<?php echo $player['stat_game_sense']; ?>" min="0" max="100">
                <label>ฮีโร่พูล</label><input type="number" name="stat_hero_pool" value="<?php echo $player['stat_hero_pool']; ?>" min="0" max="100">
            </div>
            <div>
                <label>รีเฟล็กซ์</label><input type="number" name="stat_reflex" value="<?php echo $player['stat_reflex']; ?>" min="0" max="100">
                <label>เกมเพลย์</label><input type="number" name="stat_gameplay" value="<?php echo $player['stat_gameplay']; ?>" min="0" max="100">
            </div>
        </div>

        <hr><h3>จุดเด่น / จุดด้อย</h3>
        <div class="grid-2-col">
             <div><label>จุดเด่น</label><textarea name="strengths"><?php echo htmlspecialchars($player['strengths']); ?></textarea></div>
            <div><label>จุดด้อย</label><textarea name="weaknesses"><?php echo htmlspecialchars($player['weaknesses']); ?></textarea></div>
        </div>

        <button type="submit" style="margin-top:20px; background-color:#007bff;">บันทึกการเปลี่ยนแปลง</button>
    </form>
     <a href="manage_players.php" class="back-link">&laquo; กลับไปหน้าจัดการนักกีฬา</a>
</div>

<?php require_once 'includes/admin_footer.php'; ?>