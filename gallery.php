<?php require_once 'includes/header.php'; ?>

<style>
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
}
.gallery-item {
    overflow: hidden;
    border-radius: 8px;
    cursor: pointer;
}
.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}
.gallery-item:hover img {
    transform: scale(1.05);
}
/* Lightbox styles */
.lightbox {
    display: none; position: fixed; z-index: 1001;
    top: 0; left: 0; width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.8);
    justify-content: center; align-items: center;
}
.lightbox img {
    max-width: 90%; max-height: 80%;
}
.lightbox .close {
    position: absolute; top: 20px; right: 30px;
    font-size: 40px; color: white; cursor: pointer;
}
</style>

<div class="container">
    <h2 class="section-title">แกลเลอรี่</h2>
    <div class="gallery-grid">
    <?php
    $sql = "SELECT image_url, caption FROM gallery ORDER BY uploaded_at DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
    ?>
        <div class="gallery-item" onclick="openLightbox('<?php echo htmlspecialchars($row['image_url']); ?>')">
            <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['caption']); ?>">
        </div>
    <?php
        }
    } else {
        echo "<p style='text-align:center; grid-column: 1 / -1;'>ยังไม่มีรูปภาพในแกลเลอรี่</p>";
    }
    ?>
    </div>
</div>

<div id="myLightbox" class="lightbox" onclick="closeLightbox()">
    <span class="close">&times;</span>
    <img id="lightboxImage" src="">
</div>

<script>
function openLightbox(imageUrl) {
    document.getElementById('myLightbox').style.display = 'flex';
    document.getElementById('lightboxImage').src = imageUrl;
}
function closeLightbox() {
    document.getElementById('myLightbox').style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>