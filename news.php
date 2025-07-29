<?php require_once 'includes/header.php'; ?>

<?php
// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว (มาจาก includes/header.php -> db_connect.php)
if (!isset($conn) || !$conn instanceof PDO) {
    echo "<p style='text-align:center; color:red;'>ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง</p>";
    exit;
}

$news_items = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
// ใช้ SUBSTRING สำหรับ PostgreSQL (หรือ LEFT สำหรับ MySQL) เพื่อดึงเนื้อหาย่อ
// LEFT() function is generally compatible with PostgreSQL and MySQL
$sql_select_news = "SELECT id, title, image_url, LEFT(content, 100) as summary, created_at FROM news ORDER BY created_at DESC";

try {
    $stmt_news = $conn->query($sql_select_news); // ใช้ query() สำหรับ SELECT ทั้งหมดที่ไม่มี Parameters
    $news_items = $stmt_news->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array

} catch (PDOException $e) {
    error_log("Database error fetching news items for public list: " . $e->getMessage());
    echo "<p style='text-align:center; color:red;'>ไม่สามารถโหลดข่าวสารได้ในขณะนี้</p>";
}
?>
<style>
    .news-list-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
    }
    .news-list-card {
        background-color: #1f1f1f;
        border-radius: 8px;
        display: flex;
        overflow: hidden;
    }
    .news-list-card img {
        width: 150px;
        height: 100%;
        object-fit: cover;
    }
    .news-list-content {
        padding: 15px;
    }
    .news-list-content h3 {
        margin-top: 0;
    }
    .news-list-content a {
        color: #00aaff;
        text-decoration: none;
    }
    .news-list-content a:hover {
        text-decoration: underline;
    }
    .news-list-content .date {
        font-size: 0.9rem;
        color: #888;
        margin-top: 10px;
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
    <h2 class="section-title">ข่าวสารและประกาศ</h2>
    <div class="news-list-grid">
        <?php if (!empty($news_items)): ?>
            <?php foreach($news_items as $row): ?>
            <?php 
                // ถ้า image_url ว่าง ให้ใช้ default placeholder
                $image = !empty($row['image_url']) ? htmlspecialchars($row['image_url']) : 'https://via.placeholder.com/150x100.png?text=News'; // ปรับขนาด placeholder
            ?>
            <div class="news-list-card">
                <img src="../<?php echo $image; ?>" alt="<?php echo htmlspecialchars($row['title'] ?? ''); ?>">
                <div class="news-list-content">
                    <h3><a href="news_detail.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>"><?php echo htmlspecialchars($row['title'] ?? ''); ?></a></h3>
                    <p><?php echo htmlspecialchars(strip_tags($row['summary'] ?? '')) . (strlen($row['content'] ?? '') > 100 ? '...' : ''); ?></p>
                    <div class="date"><?php echo htmlspecialchars(date('d F Y', strtotime($row['created_at'] ?? ''))); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; grid-column: 1 / -1;">ยังไม่มีข่าวสาร</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>