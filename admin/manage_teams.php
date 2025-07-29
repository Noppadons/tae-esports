<?php
$active_page = 'teams';
$page_title = 'จัดการทีม';
require_once 'includes/admin_header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_team'])) {
    $logo_url = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = "../assets/img/logos/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $image_name = time() . '_' . basename($_FILES["logo"]["name"]);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $logo_url = "assets/img/logos/" . $image_name;
        }
    }
    $sql = "INSERT INTO teams (team_name, game_name, logo_url, description) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $_POST['team_name'], $_POST['game_name'], $logo_url, $_POST['description']);
    $stmt->execute();
    header("Location: manage_teams.php"); exit();
}

$teams_result = $conn->query("SELECT * FROM teams ORDER BY game_name");
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
    <?php while($row = $teams_result->fetch_assoc()): ?>
    <tr>
        <td><img src="../<?php echo !empty($row['logo_url']) ? htmlspecialchars($row['logo_url']) : 'assets/img/default_logo.png'; ?>" alt="logo" style="max-width:50px; max-height: 50px; object-fit: contain;"></td>
        <td><?php echo htmlspecialchars($row['team_name']); ?></td>
        <td><?php echo htmlspecialchars($row['game_name']); ?></td>
        <td><a href="edit_team.php?id=<?php echo $row['id']; ?>">แก้ไข</a></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php require_once 'includes/admin_footer.php'; ?>