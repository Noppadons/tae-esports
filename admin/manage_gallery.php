<?php
$active_page = 'gallery';
$page_title = 'จัดการแกลเลอรี่';
require_once 'includes/admin_header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_image'])) {
    $caption = $_POST['caption'];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/img/gallery/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = "assets/img/gallery/" . $image_name;
            $sql = "INSERT INTO gallery (image_url, caption) VALUES (?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ss", $image_url, $caption);
                $stmt->execute();
            }
        }
    }
    header("Location: manage_gallery.php");
    exit();
}

$gallery_result = $conn->query("SELECT * FROM gallery ORDER BY uploaded_at DESC");
?>
<style>
    .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
    .gallery-admin-item { position: relative; }
    .gallery-admin-item img { width: 100%; height: 150px; object-fit: cover; border-radius: 4px; display: block; }
    .delete-btn { position: absolute; top: 5px; right: 5px; background-color: rgba(220, 53, 69, 0.9); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-weight: bold; font-size: 1rem; line-height: 30px; text-align: center; }
    .delete-btn:hover { background-color: rgba(200, 33, 49, 1); }
</style>

<h1>จัดการแกลเลอรี่</h1>
<div class="form-container">
    <h2>อัปโหลดรูปภาพใหม่</h2>
    <form action="manage_gallery.php" method="post" enctype="multipart/form-data">
        <input type="file" name="image" accept="image/*" required>
        <input type="text" name="caption" placeholder="คำอธิบายภาพ (ไม่บังคับ)">
        <button type="submit" name="upload_image">อัปโหลด</button>
    </form>
</div>

<div class="gallery-grid">
<?php while($row = $gallery_result->fetch_assoc()): ?>
    <div class="gallery-admin-item">
        <img src="../<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['caption']); ?>">
        <a href="delete_gallery_image.php?id=<?php echo $row['id']; ?>" onclick="return confirm('ยืนยันการลบรูปภาพนี้?');" class="delete-btn">&times;</a>
    </div>
<?php endwhile; ?>
</div>

<?php require_once 'includes/admin_footer.php'; ?>