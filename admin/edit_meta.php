<?php
// กำหนดหน้าปัจจุบันสำหรับเมนูและการตั้งค่าหน้า
$active_page = 'meta';
$page_title = 'แก้ไข Meta Guide';

// ตรวจสอบให้แน่ใจว่าไฟล์ที่จำเป็นถูกเรียกใช้
// โปรดตรวจสอบ Path ของ 'includes/admin_header.php' และ '../includes/api_helpers.php'
// ให้ถูกต้องตามโครงสร้างโฟลเดอร์ของคุณ
require_once 'includes/admin_header.php'; // สำหรับส่วนหัวของหน้า admin (รวมการเชื่อมต่อ DB)
require_once '../includes/api_helpers.php'; // สำหรับฟังก์ชันที่ช่วยเรียก API เช่น getDota2Heroes, getDota2HeroAbilitiesAndTalents

// ตรวจสอบ guide_id จาก URL
$guide_id = $_GET['id'] ?? 0;
if ($guide_id == 0) {
    header("location: manage_meta.php"); // ถ้าไม่มี ID ให้ redirect กลับไปหน้าจัดการไกด์
    exit();
}

// --- ส่วนจัดการข้อมูลเมื่อมีการส่ง Form (POST Request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sql = "UPDATE meta_guides SET
                game_name = ?,
                title = ?,
                content = ?,
                hero_name = ?,
                hero_image_url = ?,
                difficulty = ?,
                skill_build = ?,
                item_build_starting = ?,
                item_build_early = ?,
                item_build_mid = ?,
                item_build_late = ?,
                talent_build = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error); // แสดงข้อผิดพลาดถ้าเตรียม statement ไม่ได้
    }
    
    // Binding parameters
    $stmt->bind_param(
        "sssssissssssi",
        $_POST['game_name'],
        $_POST['title'],
        $_POST['content'],
        $_POST['hero_name'],
        $_POST['hero_image_url'],
        $_POST['difficulty'],
        $_POST['skill_build'],
        $_POST['item_build_starting'],
        $_POST['item_build_early'],
        $_POST['item_build_mid'],
        $_POST['item_build_late'],
        $_POST['talent_build'],
        $guide_id
    );
    
    $stmt->execute();
    if ($stmt->error) {
        die("Error executing statement: " . $stmt->error); // แสดงข้อผิดพลาดถ้า execute ไม่ได้
    }
    
    $stmt->close();
    header("Location: manage_meta.php"); // Redirect กลับไปหน้าจัดการหลังบันทึกสำเร็จ
    exit();
}

// --- ดึงข้อมูลไกด์ปัจจุบันจากฐานข้อมูล (GET Request หรือหลังจากการอัปเดต) ---
$sql = "SELECT * FROM meta_guides WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $guide_id);
$stmt->execute();
$guide = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$guide) {
    header("location: manage_meta.php"); // ถ้าไม่พบไกด์ ให้ redirect กลับไปหน้าจัดการ
    exit();
}

// --- ดึงข้อมูลฮีโร่ สกิล และ Talent จาก API สำหรับข้อมูลอ้างอิง ---
$dota_heroes = getDota2Heroes(); // ดึงข้อมูลฮีโร่ทั้งหมดจาก API (จาก api_helpers.php)
$selected_hero_id = null;
$selected_hero_internal_name = null;

// ค้นหา ID และ internal name ของฮีโร่ที่เกี่ยวข้องกับไกด์ปัจจุบัน
foreach($dota_heroes as $h) {
    // ใช้ isset เพื่อหลีกเลี่ยง Warning ถ้า key ไม่มีอยู่
    if(isset($h['localized_name']) && $h['localized_name'] == $guide['hero_name']) {
        $selected_hero_id = $h['id'] ?? null;
        $selected_hero_internal_name = $h['name'] ?? null;
        break; // เมื่อเจอฮีโร่แล้ว ให้ออกจาก loop ทันที
    }
}

$hero_details = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
if ($selected_hero_id && $selected_hero_internal_name) {
    // ถ้าพบฮีโร่ที่เลือก ให้ดึงข้อมูลสกิลและ Talent
    $hero_details = getDota2HeroAbilitiesAndTalents($selected_hero_id, $selected_hero_internal_name); // จาก api_helpers.php
}

// --- ฟังก์ชันสำหรับแปลง string ที่คั่นด้วย comma เป็น array และกรองค่าว่างเปล่าออก ---
function text_to_array_filtered($text) {
    if(empty($text)) return []; // ถ้า string ว่าง ให้คืน array ว่างทันที
    $items = array_map('trim', explode(',', $text)); // แยก string และ trim ช่องว่าง
    return array_filter($items, 'strlen'); // กรอง string ที่มีความยาวเป็น 0 ออกไป (ว่างเปล่า)
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
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
        /* --- General Body & Container Styling (Adjusted for Light Theme based on edit_player.php context) --- */
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa; /* Light background like common admin panels */
            color: #343a40; /* Darker text */
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 25px;
            background-color: #ffffff; /* White background */
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Lighter shadow */
        }

        /* --- Page Title --- */
        h1 {
            color: #007bff; /* Blue heading */
            margin-bottom: 25px;
            text-align: center;
            font-size: 2.5rem;
            border-bottom: 1px solid #dee2e6; /* Lighter border */
            padding-bottom: 15px;
        }

        /* --- Layout for Form and Reference Panel --- */
        .edit-guide-layout {
            display: grid;
            grid-template-columns: 2fr 1fr; /* Form takes 2/3, Reference takes 1/3 */
            gap: 30px; /* Increased gap for better spacing */
        }
        .form-container {
            grid-column: 1 / 2;
            /* This corresponds to the general form area style, should inherit from .container or admin_header */
        }
        .reference-panel {
            grid-column: 2 / 3;
            background-color: #e9ecef; /* Lighter background for panel, similar to default light theme */
            padding: 20px; /* Increased padding */
            border-radius: 8px;
            height: fit-content; /* Adjust height based on content */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Lighter shadow */
            border: 1px solid #ced4da; /* Subtle border */
        }
        .reference-panel h4 {
            margin-top: 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #adb5bd; /* Lighter border */
            color: #343a40; /* Darker text */
            font-size: 1.6rem;
            margin-bottom: 15px;
        }
        .reference-panel h5 {
            color: #6c757d; /* Medium-dark text */
            margin-top: 20px;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }
        .reference-list {
            list-style: none;
            padding: 0;
            font-size: 0.95rem;
            max-height: 280px; /* Increased height for more items */
            overflow-y: auto;
            border: 1px solid #ced4da; /* Lighter border */
            padding: 10px;
            border-radius: 6px;
            background-color: #f1f3f5; /* Very light background for list */
        }
        .reference-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef; /* Subtle separator */
            color: #495057; /* Darker text for list items */
        }
        .reference-list li:last-child {
            border-bottom: none;
        }

        /* --- Form Section Styling (from edit_player.php's context) --- */
        .form-section {
            margin-bottom: 25px; /* Consistent margin */
            border: 1px solid #ced4da; /* Lighter border */
            padding: 20px; /* Consistent padding */
            border-radius: 8px;
            background-color: #ffffff; /* White background */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); /* Very light shadow */
        }
        .form-section h3 {
            margin-top: 0;
            color: #343a40; /* Dark text */
            border-bottom: 1px solid #adb5bd; /* Consistent border */
            padding-bottom: 12px;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        label {
            display: block;
            margin-bottom: 8px; /* Consistent margin */
            color: #495057; /* Dark text */
            font-weight: bold;
            font-size: 1rem;
        }
        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: calc(100% - 20px); /* Adjusted for 10px padding on each side */
            padding: 10px; /* Consistent padding */
            margin-bottom: 15px; /* Consistent margin */
            border: 1px solid #ced4da; /* Lighter border */
            border-radius: 5px;
            background-color: #f8f9fa; /* Very light background */
            color: #495057; /* Darker text */
            font-size: 1rem;
        }
        textarea {
            height: 100px; /* Adjusted to 100px from edit_player.php */
            resize: vertical; /* Allow vertical resizing */
        }
        /* Readonly input style */
        input[readonly] {
            background-color: #e9ecef; /* Lighter background for readonly */
            cursor: not-allowed;
            opacity: 1; /* Not faded */
        }

        /* --- Buttons (from edit_player.php's context) --- */
        button {
            padding: 12px 25px; /* Consistent padding */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: background-color 0.3s ease;
            color: #fff; /* White text for buttons */
            font-weight: bold;
        }
        button[type="submit"] {
            background-color: #007bff;
            margin-top: 20px; /* Consistent margin */
        }
        button[type="submit"]:hover {
            background-color: #0056b3;
        }
        button[onclick="openHeroModal()"] {
            background-color: #6c757d; /* Gray color for modal button */
            margin-top:15px; /* Consistent margin */
            margin-bottom:15px; /* Consistent margin */
        }
        button[onclick="openHeroModal()"]:hover {
            background-color: #5a6268;
        }

        /* --- Back Link (from edit_player.php's context) --- */
        .back-link {
            display: inline-block;
            margin-top: 25px; /* Consistent margin */
            color: #007bff;
            text-decoration: none;
            font-size: 1.1rem;
        }
        .back-link:hover {
            text-decoration: underline;
        }

        /* --- Modal Styles (Consistent with edit_player.php context, adjusted for light theme) --- */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5); /* Slightly less opaque overlay */
            padding-top: 50px;
        }
        .modal-content {
            background-color: #f8f9fa; /* Light background for modal content */
            margin: auto;
            padding: 30px;
            border: 1px solid #adb5bd; /* Lighter border */
            width: 90%;
            max-width: 700px;
            border-radius: 10px;
            position: relative;
            box-shadow: 0 6px 15px rgba(0,0,0,0.2); /* Lighter shadow */
            color: #343a40; /* Darker text */
        }
        .close-button {
            color: #6c757d; /* Gray close button */
            float: right;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        .close-button:hover,
        .close-button:focus {
            color: #495057;
        }
        .hero-search-input {
            width: calc(100% - 22px);
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ced4da; /* Lighter border */
            border-radius: 5px;
            background-color: #ffffff; /* White background */
            color: #495057; /* Darker text */
            font-size: 1rem;
        }
        .hero-list-container {
            max-height: 450px;
            overflow-y: auto;
            border: 1px solid #ced4da; /* Lighter border */
            border-radius: 6px;
            background-color: #ffffff; /* White background */
        }
        .hero-list-container ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .hero-list-container li {
            padding: 12px;
            border-bottom: 1px solid #e9ecef; /* Lighter separator */
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
            color: #495057; /* Darker text */
            font-size: 1rem;
        }
        .hero-list-container li:hover {
            background-color: #f1f3f5; /* Light hover effect */
        }
        .hero-list-container li:last-child {
            border-bottom: none;
        }
        .hero-list-container li img {
            width: 48px;
            height: 36px;
            margin-right: 15px;
            border-radius: 4px;
            border: 1px solid #ced4da; /* Lighter border */
        }
        /* New: Grid for 2 columns (if used, from edit_player context) */
        .grid-2-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        hr {
            border: none;
            border-top: 1px solid #dee2e6; /* Lighter border from edit_player context */
            margin: 30px 0; /* Consistent margin */
        }
    </style>
</head>
<body>

<div class="container">
    <h1>แก้ไขไกด์: <?php echo htmlspecialchars($guide['title']); ?></h1>

    <div class="edit-guide-layout">
        <div class="form-container">
            <form method="post" action="edit_meta.php?id=<?php echo $guide_id; ?>">
                <div class="form-section">
                    <h3>ข้อมูลหลัก</h3>
                    <label for="title">หัวข้อไกด์</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($guide['title']); ?>" required>

                    <label for="game_name">เกม</label>
                    <select id="game_name" name="game_name">
                        <option value="DOTA2" <?php if($guide['game_name'] == 'DOTA2') echo 'selected'; ?>>DOTA2</option>
                    </select>

                    <button type="button" onclick="openHeroModal()">เปลี่ยนฮีโร่ / ดึงข้อมูลใหม่</button>

                    <label for="hero_name">ชื่อฮีโร่</label>
                    <input type="text" id="hero_name" name="hero_name" value="<?php echo htmlspecialchars($guide['hero_name']); ?>" readonly>
                    
                    <label for="hero_image_url">URL รูปฮีโร่</label>
                    <input type="text" id="hero_image_url" name="hero_image_url" value="<?php echo htmlspecialchars($guide['hero_image_url']); ?>" readonly>
                    
                    <label for="difficulty">ระดับความยาก (1-5)</label>
                    <input type="number" id="difficulty" name="difficulty" value="<?php echo $guide['difficulty']; ?>" min="1" max="5" required>
                </div>

                <div class="form-section">
                    <h3>การออกของ (ใส่ชื่อไอเทม คั่นด้วย ,)</h3>
                    <label for="item_build_starting">Starting Items</label>
                    <textarea name="item_build_starting" id="item_build_starting"><?php echo htmlspecialchars($guide['item_build_starting']); ?></textarea>
                    
                    <label for="item_build_early">Early Game</label>
                    <textarea name="item_build_early" id="item_build_early"><?php echo htmlspecialchars($guide['item_build_early']); ?></textarea>
                    
                    <label for="item_build_mid">Mid Game</label>
                    <textarea name="item_build_mid" id="item_build_mid"><?php echo htmlspecialchars($guide['item_build_mid']); ?></textarea>
                    
                    <label for="item_build_late">Late Game</label>
                    <textarea name="item_build_late" id="item_build_late"><?php echo htmlspecialchars($guide['item_build_late']); ?></textarea>
                </div>

                <div class="form-section">
                    <h3>การอัปสกิล (ใส่ชื่อสกิล คั่นด้วย ,)</h3>
                    <textarea name="skill_build" id="skill_build"><?php echo htmlspecialchars($guide['skill_build']); ?></textarea>
                </div>

                <div class="form-section">
                    <h3>การอัป Talent (ใส่ชื่อ Talent คั่นด้วย ,)</h3>
                    <textarea name="talent_build" id="talent_build"><?php echo htmlspecialchars($guide['talent_build']); ?></textarea>
                </div>

                <div class="form-section">
                    <h3>เนื้อหาไกด์</h3>
                    <textarea id="content" name="content"><?php echo htmlspecialchars($guide['content']); ?></textarea>
                </div>

                <button type="submit">บันทึกการเปลี่ยนแปลง</button>
            </form>
            <a href="manage_meta.php" class="back-link">&laquo; กลับไปหน้าจัดการ Meta</a>
        </div>

        <div class="reference-panel">
            <h4>ข้อมูลอ้างอิง: <?php echo htmlspecialchars($guide['hero_name'] ?: 'ยังไม่เลือกฮีโร่'); ?></h4>
            <?php if($selected_hero_id): ?>
                <div>
                    <h5>สกิลทั้งหมด</h5>
                    <ul class="reference-list">
                        <?php if(!empty($hero_details['abilities'])): ?>
                            <?php foreach($hero_details['abilities'] as $ability): ?>
                                <li><?php echo htmlspecialchars($ability); ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>ไม่พบข้อมูลสกิล</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div>
                    <h5>Talent ทั้งหมด</h5>
                    <ul class="reference-list">
                        <?php if(!empty($hero_details['talents'])): ?>
                            <?php foreach($hero_details['talents'] as $talent): ?>
                                <li><?php echo htmlspecialchars($talent); ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>ไม่พบข้อมูล Talent</li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p>โปรดเลือกฮีโร่เพื่อดูข้อมูลอ้างอิงสกิลและ Talent</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="heroModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>เลือกฮีโร่</h2>
        <input type="text" id="heroSearch" class="hero-search-input" placeholder="ค้นหาฮีโร่...">
        <div class="hero-list-container">
            <ul id="heroList">
                <?php foreach($dota_heroes as $hero): ?>
                    <li data-hero-name="<?php echo htmlspecialchars($hero['localized_name']); ?>"
                        data-hero-image-url="<?php echo htmlspecialchars('https://cdn.cloudflare.steamstatic.com' . ($hero['img'] ?? '')); ?>"
                        onclick="selectHero(this)">
                        <img src="<?php echo htmlspecialchars('https://cdn.cloudflare.steamstatic.com' . ($hero['img'] ?? '')); ?>" alt="<?php echo htmlspecialchars($hero['localized_name']); ?>">
                        <?php echo htmlspecialchars($hero['localized_name']); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<script>
    // Get the modal elements
    var modal = document.getElementById("heroModal");
    var span = document.getElementsByClassName("close-button")[0];
    var heroSearchInput = document.getElementById("heroSearch");
    var heroList = document.getElementById("heroList");

    // When the user clicks the button, open the modal
    function openHeroModal() {
        modal.style.display = "block";
        heroSearchInput.value = ''; // Clear search on open
        filterHeroList(); // Show all heroes
    }

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Filter hero list based on search input
    heroSearchInput.onkeyup = function() {
        filterHeroList();
    }

    function filterHeroList() {
        var filter = heroSearchInput.value.toLowerCase();
        var li = heroList.getElementsByTagName('li');
        for (var i = 0; i < li.length; i++) {
            var heroName = li[i].getAttribute('data-hero-name');
            if (heroName.toLowerCase().indexOf(filter) > -1) {
                li[i].style.display = "";
            } else {
                li[i].style.display = "none";
            }
        }
    }

    // Function to select a hero from the modal
    function selectHero(element) {
        var heroName = element.getAttribute('data-hero-name');
        var heroImageUrl = element.getAttribute('data-hero-image-url');

        // Set the selected hero's name and image URL to the form fields
        document.getElementById('hero_name').value = heroName;
        document.getElementById('hero_image_url').value = heroImageUrl;

        // Close the modal
        modal.style.display = "none";

        // Inform the user to save changes
        alert('ฮีโร่ถูกเลือกแล้ว: ' + heroName + '\nโปรดบันทึกการเปลี่ยนแปลงเพื่ออัปเดตข้อมูลในไกด์และข้อมูลอ้างอิง');
    }
</script>

</body>
</html>
<?php require_once 'includes/admin_footer.php'; ?>