<?php require_once 'includes/header.php'; ?>

<?php
// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว (มาจาก includes/header.php -> db_connect.php)
if (!isset($conn) || !$conn instanceof PDO) {
    echo "<p style='text-align:center; color:red;'>ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง</p>";
    exit;
}

$matches = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$sql_select_matches = "SELECT * FROM matches ORDER BY match_datetime DESC";

try {
    $stmt_matches = $conn->query($sql_select_matches); // ใช้ query() สำหรับ SELECT ทั้งหมดที่ไม่มี Parameters
    $matches = $stmt_matches->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array

} catch (PDOException $e) {
    error_log("Database error fetching matches for public page: " . $e->getMessage());
    echo "<p style='text-align:center; color:red;'>ไม่สามารถโหลดตารางการแข่งขันได้ในขณะนี้</p>";
}
?>

<style>
    .matches-container {
        background-color: #1f1f1f;
        padding: 20px;
        border-radius: 8px;
    }
    .match-row {
        display: grid;
        grid-template-columns: 1fr 2fr 1fr; /* ปรับ Grid Columns ให้เหมาะสมกับข้อมูลที่จะแสดง */
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #333;
    }
    .match-row:last-child {
        border-bottom: none;
    }
    .match-info {
        text-align: center;
    }
    .match-info .game {
        font-size: 1.2rem;
        font-weight: bold;
        color: #007bff;
    }
    .match-details {
        text-align: center;
        font-size: 1.3rem;
    }
    .match-status {
        text-align: center;
    }
    .status-upcoming { color: #ffc107; font-weight: bold; }
    .status-finished { color: #28a745; font-weight: bold; }
</style>
<div class="container">
    <h2 class="section-title">ตารางการแข่งขัน</h2>
    <div class="matches-container">
        <?php if (!empty($matches)): ?>
            <?php foreach($matches as $row): ?>
            <div class="match-row">
                <div class="match-info">
                    <div class="game"><?php echo htmlspecialchars($row['game'] ?? ''); ?></div>
                    <p><?php echo htmlspecialchars(date('d F Y', strtotime($row['match_datetime'] ?? ''))); ?></p>
                    <p><?php echo htmlspecialchars(date('H:i', strtotime($row['match_datetime'] ?? ''))); ?></p>
                </div>
                <div class="match-details">
                    <a href="match_detail.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>">
                        <strong>TAE Esport</strong> vs <strong><?php echo htmlspecialchars($row['opponent_team'] ?? ''); ?></strong>
                    </a>
                </div>
                <div class="match-status">
                    <?php if (($row['status'] ?? '') == 'Finished'): ?>
                        <span class="status-finished">Finished</span><br>
                        ผล: <?php echo htmlspecialchars($row['result'] ?? '-'); ?>
                    <?php else: ?>
                        <span class="status-upcoming">Upcoming</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; grid-column: 1 / -1;">ยังไม่มีการแข่งขันในตาราง</p>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>