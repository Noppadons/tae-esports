<?php require_once 'includes/header.php'; ?>

<?php
// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว (มาจาก includes/header.php -> db_connect.php)
if (!isset($conn) || !$conn instanceof PDO) {
    echo "<p style='text-align:center; color:red;'>ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง</p>";
    exit;
}

$guides = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$sql_select_guides = "SELECT * FROM meta_guides ORDER BY created_at DESC";

try {
    $stmt_guides = $conn->query($sql_select_guides); // ใช้ query() สำหรับ SELECT ทั้งหมดที่ไม่มี Parameters
    $guides = $stmt_guides->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array

} catch (PDOException $e) {
    error_log("Database error fetching meta guides for public list: " . $e->getMessage());
    echo "<p style='text-align:center; color:red;'>ไม่สามารถโหลดรายการไกด์ได้ในขณะนี้</p>";
}
?>
<style>
    .guide-list-item {
        background-color: #1f1f1f;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #007bff;
    }
    .guide-list-item h3 {
        margin-top: 0;
    }
    .guide-list-item h3 a {
        color: #fff;
        text-decoration: none;
    }
    .guide-list-item h3 a:hover {
        text-decoration: underline;
    }
    .guide-meta {
        font-size: 0.9rem;
        color: #aaa;
    }
    .game-tag {
        background-color: #007bff;
        color: white;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
        margin-left: 10px;
    }
    .section-title { /* Assuming this style is from your global CSS/header */
        text-align: center;
        color: #fff;
        font-size: 2.5rem;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid #333;
    }
    .container { /* Assuming this style is from your global CSS/header */
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
</style>

<div class="container">
    <h1 class="section-title">แนะนำ Meta</h1>
    
    <?php if (!empty($guides)): ?>
        <?php foreach($guides as $row): ?>
            <div class="guide-list-item">
                <h3>
                    <a href="meta_detail.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>">
                        <?php echo htmlspecialchars($row['title'] ?? ''); ?>
                    </a>
                    <span class="game-tag"><?php echo htmlspecialchars($row['game_name'] ?? ''); ?></span>
                </h3>
                <div class="guide-meta">
                    อัปเดตล่าสุด: <?php echo htmlspecialchars(date('d F Y', strtotime($row['updated_at'] ?? ''))); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align:center;">ยังไม่มีไกด์แนะนำ Meta ในขณะนี้</p>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>