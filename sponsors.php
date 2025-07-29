<?php require_once 'includes/header.php'; ?>

<?php
// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว (มาจาก includes/header.php -> db_connect.php)
if (!isset($conn) || !$conn instanceof PDO) {
    echo "<p style='text-align:center; color:red;'>ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง</p>";
    exit;
}

$sponsors = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$sql_select_sponsors = "SELECT * FROM sponsors ORDER BY display_order ASC, name ASC";

try {
    $stmt_sponsors = $conn->query($sql_select_sponsors); // ใช้ query() สำหรับ SELECT ทั้งหมดที่ไม่มี Parameters
    $sponsors = $stmt_sponsors->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array

} catch (PDOException $e) {
    error_log("Database error fetching sponsors for public page: " . $e->getMessage());
    echo "<p style='text-align:center; color:red;'>ไม่สามารถโหลดข้อมูลผู้สนับสนุนได้ในขณะนี้</p>";
}
?>
<style>
    .sponsor-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 40px;
        align-items: center;
        justify-content: center;
    }
    .sponsor-item {
        background-color: #fff; /* ทำให้โลโก้เด่นบนพื้นหลังสีขาว */
        padding: 20px;
        border-radius: 8px;
        transition: transform 0.3s, box-shadow 0.3s;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 120px;
        text-decoration: none; /* เพื่อให้ลิงก์ไม่ขีดเส้นใต้ */
    }
    .sponsor-item:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.4);
    }
    .sponsor-item img {
        max-width: 100%;
        max-height: 80px;
        object-fit: contain;
        filter: grayscale(100%); /* ทำให้เป็นสีเทา */
        transition: filter 0.3s;
    }
    .sponsor-item:hover img {
        filter: grayscale(0%); /* เมื่อ hover ให้กลับเป็นสีปกติ */
    }
    /* Assuming .container and .section-title are from includes/header.php or global CSS */
    .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .section-title { text-align: center; color: #fff; font-size: 2.5rem; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 1px solid #333; }
</style>

<div class="container">
    <h1 class="section-title">ผู้สนับสนุนของเรา</h1>
    <p style="text-align:center; max-width:600px; margin: 0 auto 40px auto; color:#aaa;">
        เราขอขอบคุณผู้สนับสนุนทุกท่านที่เชื่อมั่นและร่วมเดินทางไปกับเราสู่ความสำเร็จ
    </p>

    <div class="sponsor-grid">
        <?php if (!empty($sponsors)): ?>
            <?php foreach($sponsors as $row): ?>
            <a href="<?php echo htmlspecialchars($row['website_url'] ?? ''); ?>" target="_blank" class="sponsor-item">
                <img src="../<?php echo !empty($row['logo_url']) ? htmlspecialchars($row['logo_url']) : 'assets/img/default_logo.png'; ?>" alt="<?php echo htmlspecialchars($row['name'] ?? ''); ?>">
            </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p style='text-align:center; grid-column: 1 / -1;'>ยังไม่มีผู้สนับสนุนอย่างเป็นทางการ</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>