<?php
$active_page = 'meta';
$page_title = 'จัดการ Meta';
require_once 'includes/admin_header.php'; // admin_header.php ควรจะมีการเรียก db_connect.php อยู่แล้ว

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    die("Database connection failed. Please check includes/db_connect.php");
}

// Security check (ตรวจสอบสิทธิ์ Admin) - ควรมีในทุกหน้า admin
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}

// --- Handle Add Meta Guide Form (PDO) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_guide'])) {
    $game_name = $_POST['game_name'] ?? '';
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $author_id = $_SESSION['admin_id'] ?? null; // ตรวจสอบว่ามี admin_id ใน session
    $hero_name = $_POST['hero_name'] ?? null;
    $hero_image_url = $_POST['hero_image_url'] ?? null;

    $sql_insert = "INSERT INTO meta_guides (game_name, title, content, author_id, hero_name, hero_image_url) VALUES (:game_name, :title, :content, :author_id, :hero_name, :hero_image_url)";
    
    try {
        $stmt_insert = $conn->prepare($sql_insert);
        // ผูกค่า Parameters
        $stmt_insert->bindParam(':game_name', $game_name, PDO::PARAM_STR);
        $stmt_insert->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt_insert->bindParam(':content', $content, PDO::PARAM_STR);
        $stmt_insert->bindParam(':author_id', $author_id, PDO::PARAM_INT); // author_id เป็น Integer
        $stmt_insert->bindParam(':hero_name', $hero_name, PDO::PARAM_STR);
        $stmt_insert->bindParam(':hero_image_url', $hero_image_url, PDO::PARAM_STR);
        
        $stmt_insert->execute();
        
        header("Location: manage_meta.php"); // Redirect เพื่อ Refresh หน้า
        exit();

    } catch (PDOException $e) {
        error_log("Database error adding new meta guide: " . $e->getMessage());
        // คุณอาจจะแจ้งผู้ใช้ว่ามีข้อผิดพลาดในการเพิ่มข้อมูล
        echo "มีข้อผิดพลาดในการเพิ่มไกด์ใหม่: " . $e->getMessage();
    }
}

// --- ดึงไกด์ทั้งหมดในระบบมาแสดง (PDO) ---
$guides = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$guides_sql = "SELECT id, title, game_name, created_at FROM meta_guides ORDER BY created_at DESC";
try {
    $stmt_guides = $conn->query($guides_sql); // ใช้ query() สำหรับ SELECT ทั้งหมดที่ไม่มี Parameters
    $guides = $stmt_guides->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array
} catch (PDOException $e) {
    error_log("Database error fetching meta guides: " . $e->getMessage());
    // คุณอาจจะแสดงข้อความ error หรือแสดง array ว่างเปล่า
}
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
    /* ... (CSS styles remains unchanged, if any. Styles from previous example for modal are included here) ... */
    .modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); }
    .modal-content { background-color: #f8f9fa; /* Changed to light theme */ margin: 5% auto; padding: 30px; border: 1px solid #adb5bd; width: 80%; max-width: 900px; border-radius: 8px; box-shadow: 0 6px 15px rgba(0,0,0,0.2); color: #343a40; /* Darker text */ }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ced4da; padding-bottom: 15px; margin-bottom: 20px; }
    .modal-header h2 { color: #343a40; /* Darker text */ margin: 0; font-size: 1.8rem; }
    .close-btn { color: #6c757d; font-size: 32px; font-weight: bold; cursor: pointer; line-height: 1; }
    .close-btn:hover { color: #495057; }
    .hero-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 15px; margin-top: 20px; max-height: 60vh; overflow-y: auto; border: 1px solid #ced4da; /* Light border */ border-radius: 8px; padding: 10px; background-color: #ffffff; /* White background */ }
    .hero-portrait { text-align: center; cursor: pointer; padding: 5px; border-radius: 8px; transition: background-color 0.2s ease; }
    .hero-portrait img { width: 100%; border-radius: 8px; border: 2px solid transparent; }
    .hero-portrait:hover { background-color: #f1f3f5; /* Light hover effect */ }
    .hero-portrait:hover img { border-color: #007bff; /* Blue hover border */ }
    .hero-portrait p { font-size: 0.9rem; color: #495057; /* Darker text */ margin-top: 5px; }

    /* General Form and Table Styles - assuming they are inherited from admin_header or global CSS */
    .form-container { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); margin-bottom: 30px; }
    .form-container h2 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    label { display: block; margin-bottom: 8px; font-weight: bold; }
    input[type="text"], select, textarea { width: 100%; padding: 8px; margin: 6px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    button[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 10px; }
    button[type="submit"]:hover { background-color: #0056b3; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
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
        
        <button type="button" onclick="openHeroModal()" style="margin-top:10px; margin-bottom:10px; background-color:#6c757d;">เลือกฮีโร่เพื่อเริ่มสร้างไกด์</button>
        
        <input type="hidden" id="hero_name" name="hero_name">
        <input type="hidden" id="hero_image_url" name="hero_image_url">

        <label for="content">เนื้อหา</label>
        <textarea id="content" name="content"></textarea>
        
        <button type="submit" name="add_guide">เผยแพร่ไกด์</button>
    </form>
</div>

<h2>ไกด์ทั้งหมดในระบบ</h2>
<table>
    <thead>
        <tr><th>หัวข้อ</th><th>เกม</th><th>วันที่สร้าง</th><th>จัดการ</th></tr>
    </thead>
    <tbody>
        <?php if (!empty($guides)): // ตรวจสอบว่า $guides ไม่ว่างเปล่า ?>
            <?php foreach($guides as $row): // ใช้ foreach loop ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['game_name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars(date('d M Y', strtotime($row['created_at'] ?? ''))); ?></td>
                <td>
                    <a href="edit_meta.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>">แก้ไข</a> |
                    <a href="delete_meta.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>" onclick="return confirm('ยืนยันการลบไกด์นี้?');" style="color:#dc3545;">ลบ</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4" style="text-align:center;">ยังไม่มีไกด์ในระบบ</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div id="heroModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>เลือกฮีโร่ DOTA 2</h2>
            <span class="close-btn" onclick="closeHeroModal()">&times;</span>
        </div>
        <div class="hero-grid" id="hero-grid-container">
            <p style="color:#495057; text-align:center;">กำลังโหลดข้อมูลฮีโร่...</p>
        </div>
    </div>
</div>

<script>
let heroesLoaded = false;
function openHeroModal() {
    document.getElementById('heroModal').style.display = 'block';
    if (!heroesLoaded) {
        // ใช้ api_helpers.php ซึ่งควรจะอยู่ใน ../includes/
        fetch('../includes/api_get_heroes.php') // **แก้ไข Path ของ API ตรงนี้**
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(heroes => {
                const container = document.getElementById('hero-grid-container');
                container.innerHTML = ''; // Clear loading text
                if (heroes && heroes.length > 0) {
                    heroes.forEach(hero => {
                        const heroDiv = document.createElement('div');
                        heroDiv.className = 'hero-portrait';
                        heroDiv.setAttribute('onclick', `selectHero(${JSON.stringify(hero)})`);
                        heroDiv.innerHTML = `
                            <img src="${hero.full_img_url ?? ''}" alt="${hero.localized_name ?? ''}">
                            <p>${hero.localized_name ?? ''}</p>
                        `;
                        container.appendChild(heroDiv);
                    });
                    heroesLoaded = true;
                } else {
                    container.innerHTML = '<p style="color:#dc3545; text-align:center;">ไม่พบข้อมูลฮีโร่</p>';
                }
            })
            .catch(error => {
                console.error('Error fetching heroes:', error);
                document.getElementById('hero-grid-container').innerHTML = '<p style="color:#dc3545; text-align:center;">เกิดข้อผิดพลาดในการโหลดข้อมูลฮีโร่</p>';
            });
    }
}
function closeHeroModal() { document.getElementById('heroModal').style.display = 'none'; }
function selectHero(heroData) {
    document.getElementById('title').value = "ไกด์ Meta: " + (heroData.localized_name ?? '');
    document.getElementById('hero_name').value = (heroData.localized_name ?? '');
    document.getElementById('hero_image_url').value = (heroData.full_img_url ?? '');
    
    // ตรวจสอบว่า tinymce editor พร้อมใช้งานหรือไม่
    if (tinymce.get('content')) {
        const initialContent = `
            <table style="width: 100%; border-collapse: collapse; background-color: #f1f3f5; border-radius: 8px; margin: 15px 0; color: #343a40;">
                <tbody><tr>
                    <td style="width: 120px; padding: 10px;"><img src="${heroData.full_img_url ?? ''}" alt="${heroData.localized_name ?? ''}" style="width: 100%; border-radius: 8px; border: 1px solid #ced4da;" /></td>
                    <td style="padding: 10px; vertical-align: top;">
                        <h3 style="margin-top: 0; color: #007bff;">${heroData.localized_name ?? ''}</h3>
                        <p><strong>Primary Attribute:</strong> ${(heroData.primary_attr ?? '').toUpperCase()}</p>
                        <p><strong>Roles:</strong> ${(heroData.roles ?? []).join(', ')}</p>
                    </td>
                </tr></tbody>
            </table>
            <h2>ภาพรวมการเล่น</h2><p>เริ่มต้นเขียนไกด์ที่นี่...</p>
        `;
        tinymce.get('content').setContent(initialContent);
    } else {
        console.warn("TinyMCE editor for 'content' not found.");
        // Fallback for non-TinyMCE textareas
        document.getElementById('content').value = "ไกด์ Meta: " + (heroData.localized_name ?? '') + "\n\n" + initialContent;
    }
    closeHeroModal();
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>