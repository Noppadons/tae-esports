<?php
$active_page = 'sponsors';
$page_title = 'จัดการผู้สนับสนุน';
require_once 'includes/admin_header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_sponsor'])) {
    $logo_url = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = "../assets/img/sponsors/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $image_name = time() . '_' . basename($_FILES["logo"]["name"]);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $logo_url = "assets/img/sponsors/" . $image_name;
        }
    }
    $sql = "INSERT INTO sponsors (name, logo_url, website_url, display_order) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $_POST['name'], $logo_url, $_POST['website_url'], $_POST['display_order']);
    $stmt->execute();
    header("Location: manage_sponsors.php"); exit();
}

$sponsors_result = $conn->query("SELECT * FROM sponsors ORDER BY display_order ASC, name ASC");
?>
<style>
    td img { max-width: 100px; max-height: 40px; object-fit: contain; }
</style>

<h1>จัดการผู้สนับสนุน</h1>
<div class="form-container">
    <h2>เพิ่มผู้สนับสนุนใหม่</h2>
    <form action="manage_sponsors.php" method="post" enctype="multipart/form-data">
        <label>ชื่อผู้สนับสนุน</label><input type="text" name="name" required>
        <label>ลิงก์เว็บไซต์ (URL)</label><input type="text" name="website_url" placeholder="https://www.example.com">
        <label>ลำดับการแสดงผล (เลขน้อยขึ้นก่อน)</label><input type="number" name="display_order" value="0">
        <label>โลโก้</label><input type="file" name="logo" accept="image/*" required>
        <button type="submit" name="add_sponsor" style="margin-top:10px;">เพิ่มผู้สนับสนุน</button>
    </form>
</div>

<h2>รายชื่อผู้สนับสนุนทั้งหมด</h2>
<table>
    <thead>
        <tr><th>โลโก้</th><th>ชื่อ</th><th>ลำดับ</th><th>จัดการ</th></tr>
    </thead>
    <tbody>
    <?php while($row = $sponsors_result->fetch_assoc()): ?>
        <tr>
            <td><img src="../<?php echo htmlspecialchars($row['logo_url']); ?>" alt="logo"></td>
            <td><a href="<?php echo htmlspecialchars($row['website_url']); ?>" target="_blank"><?php echo htmlspecialchars($row['name']); ?></a></td>
            <td><?php echo $row['display_order']; ?></td>
            <td>
                <a href="edit_sponsor.php?id=<?php echo $row['id']; ?>">แก้ไข</a> |
                <a href="delete_sponsor.php?id=<?php echo $row['id']; ?>" onclick="return confirm('ยืนยันการลบ?');">ลบ</a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php require_once 'includes/admin_footer.php'; ?>