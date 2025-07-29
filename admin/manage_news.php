<?php
$active_page = 'news';
$page_title = 'จัดการข่าวสาร';
require_once 'includes/admin_header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_news'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/img/news/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = "assets/img/news/" . $image_name;
        }
    }
    $sql = "INSERT INTO news (title, content, image_url) VALUES (?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sss", $title, $content, $image_url);
        $stmt->execute();
    }
    header("Location: manage_news.php");
    exit();
}
$news_result = $conn->query("SELECT id, title, content, created_at FROM news ORDER BY created_at DESC");
?>
<script src="https://cdn.tiny.cloud/1/wz0eup1bgddbnmjpimc2bfqbp9bc111yb78sfc50e04mjmuq/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: 'textarea#content',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
  });
</script>

<h1>จัดการข่าวสาร</h1>
<div class="form-container">
    <h2>สร้างข่าวใหม่</h2>
    <form action="manage_news.php" method="post" enctype="multipart/form-data">
        <label for="title">หัวข้อข่าว</label>
        <input type="text" id="title" name="title" required>
        <label for="content">เนื้อหาข่าว</label>
        <textarea id="content" name="content"></textarea>
        <label for="image" style="margin-top:10px;">รูปภาพประกอบ (ไม่บังคับ)</label>
        <input type="file" id="image" name="image" accept="image/*">
        <button type="submit" name="add_news" style="margin-top:10px;">เผยแพร่ข่าว</button>
    </form>
</div>

<h2>รายการข่าวทั้งหมด</h2>
<table>
    <thead>
        <tr>
            <th>หัวข้อ</th>
            <th>เนื้อหาย่อ</th>
            <th>วันที่สร้าง</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($news_result->num_rows > 0): ?>
            <?php while($row = $news_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo strip_tags(substr($row['content'], 0, 150)); ?>...</td>
                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                <td>
                    <a href="edit_news.php?id=<?php echo $row['id']; ?>">แก้ไข</a> |
                    <a href="delete_news.php?id=<?php echo $row['id']; ?>" onclick="return confirm('ยืนยันการลบ?');">ลบ</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" style="text-align: center;">ยังไม่มีข่าวในระบบ</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once 'includes/admin_footer.php'; ?>