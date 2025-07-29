<?php
$active_page = 'news';
$page_title = 'แก้ไขข่าว';
require_once 'includes/admin_header.php';

$news_id = $_GET['id'] ?? 0;
if ($news_id == 0) { header("location: manage_news.php"); exit(); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $news_id_post = $_POST['id'];
    $title_post = $_POST['title'];
    $content_post = $_POST['content'];
    $current_image_url_post = $_POST['current_image_url'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        if(!empty($current_image_url_post) && file_exists("../".$current_image_url_post)) { unlink("../".$current_image_url_post); }
        $target_dir = "../assets/img/news/";
        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $current_image_url_post = "assets/img/news/" . $image_name;
        }
    }
    $sql_update = "UPDATE news SET title = ?, content = ?, image_url = ? WHERE id = ?";
    if ($stmt_update = $conn->prepare($sql_update)) {
        $stmt_update->bind_param("sssi", $title_post, $content_post, $current_image_url_post, $news_id_post);
        $stmt_update->execute();
        header("Location: manage_news.php");
        exit();
    }
}

$sql_select = "SELECT * FROM news WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("i", $news_id);
$stmt_select->execute();
$news = $stmt_select->get_result()->fetch_assoc();
if (!$news) { header("location: manage_news.php"); exit(); }

$title = $news['title'];
$content = $news['content'];
$current_image_url = $news['image_url'];
?>
<script src="https://cdn.tiny.cloud/1/wz0eup1bgddbnmjpimc2bfqbp9bc111yb78sfc50e04mjmuq/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: 'textarea#content',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
  });
</script>
<style>
    .current-image { max-width: 200px; display: block; margin-bottom: 10px; border-radius: 4px; }
    .back-link { display: inline-block; margin-top: 15px; text-decoration: none; color: #007bff; }
</style>

<h1>แก้ไขข่าว</h1>
<div class="form-container">
    <form action="edit_news.php?id=<?php echo $news_id; ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $news_id; ?>">
        <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($current_image_url); ?>">
        
        <label for="title">หัวข้อข่าว</label>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
        
        <label for="content">เนื้อหาข่าว</label>
        <textarea id="content" name="content"><?php echo htmlspecialchars($content); ?></textarea>

        <label style="margin-top:10px;">รูปภาพปัจจุบัน</label>
        <?php if (!empty($current_image_url)): ?>
            <img src="../<?php echo htmlspecialchars($current_image_url); ?>" alt="Current Image" class="current-image">
        <?php else: ?>
            <p>ไม่มีรูปภาพประกอบ</p>
        <?php endif; ?>
        
        <label for="image">เปลี่ยน/เพิ่ม รูปภาพประกอบ</label>
        <input type="file" id="image" name="image" accept="image/*">

        <button type="submit" style="margin-top:10px; background-color: #007bff;">บันทึกการเปลี่ยนแปลง</button>
    </form>
    <a href="manage_news.php" class="back-link">&laquo; กลับไปหน้าจัดการข่าวสาร</a>
</div>

<?php require_once 'includes/admin_footer.php'; ?>