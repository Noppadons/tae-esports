<?php
$active_page = 'meta';
$page_title = 'จัดการ Meta';
require_once 'includes/admin_header.php';

// --- Handle Add Meta Guide Form ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_guide'])) {
    $game_name = $_POST['game_name'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $author_id = $_SESSION['admin_id'];
    $hero_name = $_POST['hero_name'] ?? null;
    $hero_image_url = $_POST['hero_image_url'] ?? null;

    $sql = "INSERT INTO meta_guides (game_name, title, content, author_id, hero_name, hero_image_url) VALUES (?, ?, ?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssiss", $game_name, $title, $content, $author_id, $hero_name, $hero_image_url);
        $stmt->execute();
    }
    header("Location: manage_meta.php");
    exit();
}

$guides_result = $conn->query("SELECT * FROM meta_guides ORDER BY created_at DESC");
?>
<script src="https://cdn.tiny.cloud/1/wz0eup1bgddbnmjpimc2bfqbp9bc111yb78sfc50e04mjmuq/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: 'textarea#content',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
    height: 400,
  });
</script>
<style>
    .modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); }
    .modal-content { background-color: #333; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 900px; border-radius: 8px; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #555; padding-bottom: 10px; }
    .close-btn { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
    .hero-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 15px; margin-top: 20px; max-height: 60vh; overflow-y: auto; }
    .hero-portrait { text-align: center; cursor: pointer; }
    .hero-portrait img { width: 100%; border-radius: 8px; border: 2px solid transparent; }
    .hero-portrait:hover img { border-color: #00aaff; }
    .hero-portrait p { font-size: 0.9rem; color: #ddd; margin-top: 5px; }
</style>

<h1>จัดการ Meta แนะนำ</h1>
<div class="form-container">
    <h2>สร้างไกด์ Meta ใหม่</h2>
    <form action="manage_meta.php" method="post">
        <label for="game_name">เลือกเกม</label>
        <select id="game_name" name="game_name" required>
            <option value="DOTA2">DOTA2</option>
            <option value="PUBG">PUBG</option>
            <option value="ROV">ROV</option>
            <option value="Other">Other</option>
        </select>
        <label for="title">หัวข้อไกด์</label>
        <input type="text" id="title" name="title" required>
        
        <button type="button" onclick="openHeroModal()" style="margin-top:10px; margin-bottom:10px; background-color:#555;">เลือกฮีโร่เพื่อเริ่มสร้างไกด์</button>
        
        <input type="hidden" id="hero_name" name="hero_name">
        <input type="hidden" id="hero_image_url" name="hero_image_url">

        <label for="content">เนื้อหา</label>
        <textarea id="content" name="content"></textarea>
        
        <button type="submit" name="add_guide" style="margin-top:10px;">เผยแพร่ไกด์</button>
    </form>
</div>

<h2>ไกด์ทั้งหมดในระบบ</h2>
<table>
    <thead>
        <tr><th>หัวข้อ</th><th>เกม</th><th>วันที่สร้าง</th><th>จัดการ</th></tr>
    </thead>
    <tbody>
        <?php if ($guides_result->num_rows > 0): ?>
            <?php while($row = $guides_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['game_name']); ?></td>
                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                <td>
                    <a href="edit_meta.php?id=<?php echo $row['id']; ?>">แก้ไข</a> |
                    <a href="delete_meta.php?id=<?php echo $row['id']; ?>" onclick="return confirm('ยืนยันการลบ?');" style="color:#dc3545;">ลบ</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4" style="text-align:center;">ยังไม่มีไกด์ในระบบ</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div id="heroModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="color:white;">เลือกฮีโร่ DOTA 2</h2>
            <span class="close-btn" onclick="closeHeroModal()">&times;</span>
        </div>
        <div class="hero-grid" id="hero-grid-container">
            <p style="color:white; text-align:center;">กำลังโหลดข้อมูลฮีโร่...</p>
        </div>
    </div>
</div>

<script>
let heroesLoaded = false;
function openHeroModal() {
    document.getElementById('heroModal').style.display = 'block';
    if (!heroesLoaded) {
        fetch('api_get_heroes.php')
            .then(response => response.json())
            .then(heroes => {
                const container = document.getElementById('hero-grid-container');
                container.innerHTML = '';
                heroes.forEach(hero => {
                    const heroDiv = document.createElement('div');
                    heroDiv.className = 'hero-portrait';
                    heroDiv.setAttribute('onclick', `selectHero(${JSON.stringify(hero)})`);
                    heroDiv.innerHTML = `
                        <img src="${hero.full_img_url}" alt="${hero.localized_name}">
                        <p>${hero.localized_name}</p>
                    `;
                    container.appendChild(heroDiv);
                });
                heroesLoaded = true;
            })
            .catch(error => {
                console.error('Error fetching heroes:', error);
                document.getElementById('hero-grid-container').innerHTML = '<p style="color:red;">เกิดข้อผิดพลาดในการโหลดข้อมูลฮีโร่</p>';
            });
    }
}
function closeHeroModal() { document.getElementById('heroModal').style.display = 'none'; }
function selectHero(heroData) {
    document.getElementById('title').value = "ไกด์ Meta: " + heroData.localized_name;
    document.getElementById('hero_name').value = heroData.localized_name;
    document.getElementById('hero_image_url').value = heroData.full_img_url;
    const initialContent = `
        <table style="width: 100%; border-collapse: collapse; background-color: #2c2c2c; border-radius: 8px; margin: 15px 0;">
            <tbody><tr>
                <td style="width: 120px; padding: 10px;"><img src="${heroData.full_img_url}" alt="${heroData.localized_name}" style="width: 100%; border-radius: 8px;" /></td>
                <td style="padding: 10px; vertical-align: top; color: #fff;">
                    <h3 style="margin-top: 0;">${heroData.localized_name}</h3>
                    <p><strong>Primary Attribute:</strong> ${heroData.primary_attr.toUpperCase()}</p>
                    <p><strong>Roles:</strong> ${heroData.roles.join(', ')}</p>
                </td>
            </tr></tbody>
        </table>
        <h2>ภาพรวมการเล่น</h2><p>เริ่มต้นเขียนไกด์ที่นี่...</p>
    `;
    tinymce.get('content').setContent(initialContent);
    closeHeroModal();
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>