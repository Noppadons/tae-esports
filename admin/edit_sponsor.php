<?php
$active_page = 'sponsors';
$page_title = 'แก้ไขผู้สนับสนุน';
require_once 'includes/admin_header.php';

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
<style>
    .current-logo { max-width: 150px; max-height: 80px; object-fit: contain; display: block; margin: 10px 0; background: #eee; padding: 5px; border-radius: 4px; }
    .back-link { display: inline-block; margin-top: 15px; text-decoration: none; color: #007bff; }
</style>

<h1>แก้ไขผู้สนับสนุน</h1>
<div class="form-container">
    <form method="post" enctype="multipart/form-data" action="edit_sponsor.php?id=<?php echo $sponsor_id; ?>">
        <input type="hidden" name="id" value="<?php echo $sponsor['id']; ?>">
        <input type="hidden" name="current_logo_url" value="<?php echo htmlspecialchars($sponsor['logo_url']); ?>">
        <label>ชื่อผู้สนับสนุน</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($sponsor['name']); ?>" required>
        <label>ลิงก์เว็บไซต์ (URL)</label>
        <input type="text" name="website_url" value="<?php echo htmlspecialchars($sponsor['website_url']); ?>">
        <label>ลำดับการแสดงผล</label>
        <input type="number" name="display_order" value="<?php echo $sponsor['display_order']; ?>">
        <label>โลโก้ปัจจุบัน</label>
        <img src="../<?php echo !empty($sponsor['logo_url']) ? htmlspecialchars($sponsor['logo_url']) : 'assets/img/default_logo.png'; ?>" class="current-logo">
        <label>เปลี่ยนโลโก้</label>
        <input type="file" name="logo" accept="image/*">
        <button type="submit" style="margin-top:10px; background-color: #007bff;">บันทึกการเปลี่ยนแปลง</button>
    </form>
    <a href="manage_sponsors.php" class="back-link">&laquo; กลับไปหน้าจัดการผู้สนับสนุน</a>
</div>

<?php require_once 'includes/admin_footer.php'; ?>