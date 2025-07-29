<?php
session_start();
require_once '../includes/db_connect.php';
if (!isset($_SESSION["admin_logged_in"])) { header("location: login.php"); exit; }

$sponsor_id = $_GET['id'] ?? 0;
if ($sponsor_id == 0) { header("location: manage_sponsors.php"); exit(); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sponsor_id_post = $_POST['id'];
    $current_logo_url = $_POST['current_logo_url'];

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        if(!empty($current_logo_url) && file_exists("../".$current_logo_url)) { unlink("../".$current_logo_url); }
        $target_dir = "../assets/img/sponsors/";
        $image_name = time() . '_' . basename($_FILES["logo"]["name"]);
        $target_file = $target_dir . $image_name;
        if(move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $current_logo_url = "assets/img/sponsors/" . $image_name;
        }
    }

    $sql_update = "UPDATE sponsors SET name=?, logo_url=?, website_url=?, display_order=? WHERE id=?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssis", $_POST['name'], $current_logo_url, $_POST['website_url'], $_POST['display_order'], $sponsor_id_post);
    $stmt_update->execute();
    header("Location: manage_sponsors.php"); exit();
}

$sql_select = "SELECT * FROM sponsors WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("i", $sponsor_id);
$stmt_select->execute();
$sponsor = $stmt_select->get_result()->fetch_assoc();
if (!$sponsor) { header("location: manage_sponsors.php"); exit(); }
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
            <input type="hidden" name="id" value="<?php echo $sponsor['id']; ?>">
            <input type="hidden" name="current_logo_url" value="<?php echo htmlspecialchars($sponsor['logo_url']); ?>">
            <label>ชื่อผู้สนับสนุน</label><input type="text" name="name" value="<?php echo htmlspecialchars($sponsor['name']); ?>" required>
            <label>ลิงก์เว็บไซต์ (URL)</label><input type="text" name="website_url" value="<?php echo htmlspecialchars($sponsor['website_url']); ?>">
            <label>ลำดับการแสดงผล</label><input type="number" name="display_order" value="<?php echo $sponsor['display_order']; ?>">
            <label>โลโก้ปัจจุบัน</label><img src="../<?php echo htmlspecialchars($sponsor['logo_url']); ?>" class="current-logo">
            <label>เปลี่ยนโลโก้</label><input type="file" name="logo" accept="image/*">
            <button type="submit" style="margin-top:10px;">บันทึกการเปลี่ยนแปลง</button>
        </form>
    </div>
</div>

</body>
</html>