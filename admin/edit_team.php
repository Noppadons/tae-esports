<?php
$active_page = 'teams';
$page_title = 'แก้ไขทีม';
require_once 'includes/admin_header.php';

$team_id = $_GET['id'] ?? 0;
if ($team_id == 0) { header("location: manage_teams.php"); exit(); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $team_id = $_POST['id'];
    $current_logo_url = $_POST['current_logo_url'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        if(!empty($current_logo_url) && file_exists("../".$current_logo_url)) { unlink("../".$current_logo_url); }
        $target_dir = "../assets/img/logos/";
        $image_name = time() . '_' . basename($_FILES["logo"]["name"]);
        $target_file = $target_dir . $image_name;
        if(move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $current_logo_url = "assets/img/logos/" . $image_name;
        }
    }
    $sql = "UPDATE teams SET team_name=?, game_name=?, logo_url=?, description=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $_POST['team_name'], $_POST['game_name'], $current_logo_url, $_POST['description'], $team_id);
    $stmt->execute();
    header("Location: manage_teams.php");
    exit();
}

$sql = "SELECT * FROM teams WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$team = $stmt->get_result()->fetch_assoc();
if (!$team) { header("location: manage_teams.php"); exit(); }
?>

<h1>แก้ไขทีม: <?php echo htmlspecialchars($team['team_name']); ?></h1>
<div class="form-container">
    <form method="post" enctype="multipart/form-data" action="edit_team.php?id=<?php echo $team_id; ?>">
        <input type="hidden" name="id" value="<?php echo $team['id']; ?>">
        <input type="hidden" name="current_logo_url" value="<?php echo htmlspecialchars($team['logo_url']); ?>">
        <label>ชื่อทีม</label>
        <input type="text" name="team_name" value="<?php echo htmlspecialchars($team['team_name']); ?>" required>
        <label>ชื่อเกม</label>
        <input type="text" name="game_name" value="<?php echo htmlspecialchars($team['game_name']); ?>" required>
        <label>คำอธิบายทีม</label>
        <textarea name="description" style="height:100px;"><?php echo htmlspecialchars($team['description']); ?></textarea>
        <label>โลโก้ปัจจุบัน</label>
        <img src="../<?php echo !empty($team['logo_url']) ? htmlspecialchars($team['logo_url']) : 'assets/img/default_logo.png'; ?>" style="max-width: 100px; display: block; margin-bottom: 10px;">
        <label>เปลี่ยนโลโก้</label>
        <input type="file" name="logo" accept="image/*">
        <button type="submit" style="margin-top:10px; background-color: #007bff;">บันทึกการเปลี่ยนแปลง</button>
    </form>
    <a href="manage_teams.php" style="display:inline-block; margin-top:15px;">&laquo; กลับไปหน้าจัดการทีม</a>
</div>

<?php require_once 'includes/admin_footer.php'; ?>