<?php
// กำหนดหน้าปัจจุบันสำหรับเมนู (ถ้ามี)
$active_page = 'meta_detail';
$page_title = 'รายละเอียด Meta Guide'; // ตั้งชื่อ title ของหน้า

// ตรวจสอบให้แน่ใจว่าไฟล์ที่จำเป็นถูกเรียกใช้
require_once 'includes/header.php'; // สำหรับส่วนหัวของหน้าเว็บทั่วไป (ไม่ใช่ admin header)
require_once 'includes/api_helpers.php'; // สำหรับฟังก์ชันที่ช่วยเรียก API เช่น getDota2Items

// ตรวจสอบ guide_id จาก URL
$guide_id = $_GET['id'] ?? 0;
if ($guide_id == 0) {
    // ถ้าไม่มี ID ให้กลับไปหน้าแสดงรายการ Meta Guides
    header("location: meta.php");
    exit();
}

// --- Fetch current guide data from database ---
$sql = "SELECT * FROM meta_guides WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $guide_id);
$stmt->execute();
$guide = $stmt->get_result()->fetch_assoc();

// ถ้าไม่พบไกด์ ให้กลับไปหน้าแสดงรายการ Meta Guides
if (!$guide) {
    header("location: meta.php");
    exit();
}

// --- 1. ดึงข้อมูลไอเทมทั้งหมดมาสร้างแผนที่รูปภาพ ---
$all_items = getDota2Items(); // สมมติว่าฟังก์ชันนี้อยู่ใน includes/api_helpers.php
$item_image_map = [];
if (is_array($all_items)) {
    foreach ($all_items as $internal_name => $item_data) {
        if (isset($item_data['dname'])) {
            // สร้าง key ด้วยชื่อที่แสดงผลเป็นตัวพิมพ์เล็กทั้งหมด
            $lowercase_dname = strtolower($item_data['dname']);
            // ตรวจสอบให้แน่ใจว่า 'img' key มีอยู่ก่อนที่จะเข้าถึง
            $item_image_map[$lowercase_dname] = 'https://cdn.cloudflare.steamstatic.com' . ($item_data['img'] ?? '');
        }
    }
}

// --- Function to convert comma-separated string to filtered array ---
// ฟังก์ชันนี้จะกรองค่าว่างเปล่าออกไป
function text_to_array_filtered($text) {
    if(empty($text)) return [];
    $items = array_map('trim', explode(',', $text));
    return array_filter($items, 'strlen'); // กรอง string ที่มีความยาวเป็น 0 ออกไป
}

// --- ดึงข้อมูลสกิลและ Talent สำหรับแสดงในส่วนเนื้อหาหลัก (ถ้าต้องการแสดง) ---
// ส่วนนี้จะเป็นการดึงข้อมูล API คล้ายกับใน edit_meta.php
$dota_heroes = getDota2Heroes(); // สมมติว่าฟังก์ชันนี้อยู่ใน includes/api_helpers.php
$selected_hero_id = null;
$selected_hero_internal_name = null;

foreach($dota_heroes as $h) {
    if(isset($h['localized_name']) && $h['localized_name'] == $guide['hero_name']) {
        $selected_hero_id = $h['id'] ?? null;
        $selected_hero_internal_name = $h['name'] ?? null;
        break;
    }
}

$hero_details = [];
if ($selected_hero_id && $selected_hero_internal_name) {
    $hero_details = getDota2HeroAbilitiesAndTalents($selected_hero_id, $selected_hero_internal_name); // สมมติว่าฟังก์ชันนี้อยู่ใน includes/api_helpers.php
}
?>

<style>
    /* CSS Styles for meta_detail.php */
    body {
        font-family: Arial, sans-serif;
        background-color: #1a1a1a; /* Darker background */
        color: #e0e0e0; /* Light text color */
        margin: 0;
        padding: 0;
    }
    .guide-container {
        max-width: 1000px;
        margin: 40px auto;
        padding: 25px;
        background-color: #2a2a2a; /* Slightly lighter dark background */
        border-radius: 8px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.6);
    }
    .guide-header {
        display: flex;
        gap: 30px;
        align-items: center;
        background-color: #1f1f1f;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
    }
    .guide-header img {
        width: 120px;
        height: 90px; /* Fixed height for consistent display */
        object-fit: cover; /* Ensure image covers the area */
        border-radius: 8px;
        border: 2px solid #555;
    }
    .guide-header h1 {
        margin: 0;
        font-size: 2.8rem;
        color: #007bff; /* Blue for guide title */
    }
    .difficulty-stars {
        color: #ffc107; /* Gold for stars */
        font-size: 1.5rem;
    }
    .guide-section {
        background-color: #1f1f1f;
        padding: 25px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    }
    .guide-section h3 {
        margin-top: 0;
        border-bottom: 1px solid #444;
        padding-bottom: 10px;
        color: #e0e0e0;
        font-size: 1.8rem;
    }
    .guide-section h4 {
        color: #ccc;
        margin-top: 15px;
        margin-bottom: 10px;
        font-size: 1.2rem;
    }

    /* --- CSS for Item Build Grid --- */
    .item-build-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }
    .item-icon-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px; /* Space between item icons */
        margin-top: 10px;
    }
    .item-icon {
        background-color: #2c2c2c; /* Background for each item icon */
        border-radius: 4px;
        padding: 4px;
        display: inline-flex; /* Use flex to center image if needed */
        align-items: center;
        justify-content: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }
    .item-icon img {
        width: 64px; /* Standard item icon size */
        height: 48px;
        border-radius: 3px;
        border: 1px solid #444;
        display: block; /* Remove extra space below image */
    }
    /* Fallback image style */
    .item-icon img[src*="default_item.png"] {
        opacity: 0.5; /* Make default items slightly faded */
    }

    /* --- CSS for Skill Build & Talent Build Lists --- */
    .skill-list, .talent-list {
        list-style: none;
        padding: 0;
    }
    .skill-list li, .talent-list li {
        background-color: #2c2c2c;
        padding: 10px 15px;
        border-radius: 4px;
        margin-bottom: 8px;
        border-left: 3px solid #007bff; /* Blue line on the left */
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .guide-content {
        line-height: 1.8;
        font-size: 1.1rem;
        color: #d0d0d0;
    }
    .guide-content p {
        margin-bottom: 1em;
    }
    /* Ensure content from TinyMCE is styled */
    .guide-content img {
        max-width: 100%;
        height: auto;
        border-radius: 5px;
        margin: 10px 0;
    }
    .guide-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }
    .guide-content th, .guide-content td {
        border: 1px solid #444;
        padding: 8px;
        text-align: left;
    }
    .guide-content th {
        background-color: #3a3a3a;
        color: #fff;
    }
</style>

<div class="container guide-container">
    <header class="guide-header">
        <img src="<?php echo htmlspecialchars($guide['hero_image_url']); ?>" alt="<?php echo htmlspecialchars($guide['hero_name']); ?>">
        <div>
            <h1><?php echo htmlspecialchars($guide['title']); ?></h1>
            <div class="difficulty-stars">
                <?php for($i = 0; $i < 5; $i++): ?>
                    <span><?php echo ($i < $guide['difficulty']) ? '★' : '☆'; ?></span>
                <?php endfor; ?>
            </div>
            <p>ฮีโร่: <strong><?php echo htmlspecialchars($guide['hero_name']); ?></strong></p>
            <p>เกม: <strong><?php echo htmlspecialchars($guide['game_name']); ?></strong></p>
        </div>
    </header>

    <div class="guide-section">
        <h3>Item Build</h3>
        <div class="item-build-grid">
            <div>
                <h4>Starting Items</h4>
                <div class="item-icon-grid">
                    <?php foreach(text_to_array_filtered($guide['item_build_starting']) as $item_name):
                        $lookup_key = strtolower($item_name);
                        // Fallback image URL. ตรวจสอบว่า `assets/img/default_item.png` มีอยู่จริง
                        $image_url = $item_image_map[$lookup_key] ?? 'assets/img/default_item.png';
                    ?>
                        <div class="item-icon" title="<?php echo htmlspecialchars($item_name); ?>">
                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($item_name); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <h4>Early Game</h4>
                <div class="item-icon-grid">
                    <?php foreach(text_to_array_filtered($guide['item_build_early']) as $item_name):
                        $lookup_key = strtolower($item_name);
                        $image_url = $item_image_map[$lookup_key] ?? 'assets/img/default_item.png';
                    ?>
                        <div class="item-icon" title="<?php echo htmlspecialchars($item_name); ?>">
                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($item_name); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <h4>Mid Game</h4>
                <div class="item-icon-grid">
                     <?php foreach(text_to_array_filtered($guide['item_build_mid']) as $item_name):
                        $lookup_key = strtolower($item_name);
                        $image_url = $item_image_map[$lookup_key] ?? 'assets/img/default_item.png';
                    ?>
                        <div class="item-icon" title="<?php echo htmlspecialchars($item_name); ?>">
                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($item_name); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <h4>Late Game</h4>
                <div class="item-icon-grid">
                     <?php foreach(text_to_array_filtered($guide['item_build_late']) as $item_name):
                        $lookup_key = strtolower($item_name);
                        $image_url = $item_image_map[$lookup_key] ?? 'assets/img/default_item.png';
                    ?>
                        <div class="item-icon" title="<?php echo htmlspecialchars($item_name); ?>">
                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($item_name); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="guide-section">
        <h3>Skill Build</h3>
        <div class="skill-list">
            <?php $skills = text_to_array_filtered($guide['skill_build']); ?>
            <?php if (!empty($skills)): ?>
                <?php foreach($skills as $skill_name): ?>
                    <li><?php echo htmlspecialchars($skill_name); ?></li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>ไม่พบข้อมูล Skill Build</li>
            <?php endif; ?>
        </div>
    </div>

    <div class="guide-section">
        <h3>Talent Build</h3>
        <div class="talent-list">
            <?php $talents = text_to_array_filtered($guide['talent_build']); ?>
            <?php if (!empty($talents)): ?>
                <?php foreach($talents as $talent_name): ?>
                    <li><?php echo htmlspecialchars($talent_name); ?></li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>ไม่พบข้อมูล Talent Build</li>
            <?php endif; ?>
        </div>
    </div>

    <div class="guide-section">
        <h3>เนื้อหาไกด์</h3>
        <div class="guide-content">
            <?php echo $guide['content']; // เนื้อหาจาก TinyMCE ควรแสดงผลเป็น HTML ?>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="meta.php" class="btn btn-primary">&laquo; กลับไปหน้า Meta Guides</a>
        </div>

</div>

<?php require_once 'includes/footer.php'; ?>