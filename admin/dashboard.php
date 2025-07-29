<?php
// กำหนดหน้าปัจจุบันและชื่อหัวข้อ
$active_page = 'dashboard';
$page_title = 'Dashboard';

// เรียกใช้ header ซึ่งควรจะมีโค้ดเชื่อมต่อฐานข้อมูล ($conn เป็น PDO object)
require_once 'includes/admin_header.php';

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    die("Database connection failed. Please check includes/db_connect.php");
}

// --- ฟังก์ชันช่วยสำหรับดึงค่า COUNT จากฐานข้อมูล (PDO) ---
// การใช้ prepared statements สำหรับ query แบบ COUNT ที่ไม่มี user input โดยตรงอาจจะดูเกินความจำเป็น
// แต่เป็นแนวทางที่ดีสำหรับความปลอดภัยและประสิทธิภาพ
function getCount(PDO $pdo_conn, string $table, string $where_clause = ''): int {
    $sql = "SELECT COUNT(*) as count FROM " . $table;
    if (!empty($where_clause)) {
        $sql .= " WHERE " . $where_clause;
    }
    try {
        $stmt = $pdo_conn->query($sql);
        // fetch(PDO::FETCH_ASSOC) ดึงข้อมูลแถวแรกเป็น associative array
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['count'] : 0;
    } catch (PDOException $e) {
        error_log("Database error in getCount: " . $e->getMessage());
        return 0; // คืนค่า 0 หรือจัดการ error ตามความเหมาะสม
    }
}

// --- ดึงข้อมูลสรุปทั้งหมดจากฐานข้อมูล ---
$player_count = getCount($conn, "players");
$team_count = getCount($conn, "teams");
$upcoming_matches_count = getCount($conn, "matches", "status = 'Upcoming'"); // มี WHERE clause
$news_count = getCount($conn, "news");

// --- ดึงข่าวล่าสุด ---
$recent_news = []; // กำหนดค่าเริ่มต้นเป็น array เปล่า
try {
    $stmt_news = $conn->query("SELECT id, title FROM news ORDER BY created_at DESC LIMIT 5");
    $recent_news = $stmt_news->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array
} catch (PDOException $e) {
    error_log("Database error fetching recent news: " . $e->getMessage());
    // คุณอาจจะแสดงข้อความ error บนหน้าเว็บ หรือแสดง array ว่างเปล่า
}

// --- ดึงแฟนคลับล่าสุด ---
$recent_users = []; // กำหนดค่าเริ่มต้นเป็น array เปล่า
try {
    $stmt_users = $conn->query("SELECT username FROM users ORDER BY created_at DESC LIMIT 5");
    $recent_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array
} catch (PDOException $e) {
    error_log("Database error fetching recent users: " . $e->getMessage());
    // คุณอาจจะแสดงข้อความ error บนหน้าเว็บ หรือแสดง array ว่างเปล่า
}

?>

<style>
    .dashboard-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); text-align: center; }
    .stat-card h3 { font-size: 2.5rem; margin: 0 0 10px 0; color: #007bff; }
    .stat-card p { margin: 0; color: #6c757d; font-size: 1rem; }
    .dashboard-section { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .dashboard-section h2 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    .activity-list { list-style: none; padding: 0; }
    .activity-list li { padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
    .activity-list li:last-child { border-bottom: none; }
    .activity-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
</style>

<h1>Admin Dashboard</h1>

<div class="dashboard-stats">
    <div class="stat-card"><h3><?php echo htmlspecialchars($player_count); ?></h3><p>👥 นักกีฬาทั้งหมด</p></div>
    <div class="stat-card"><h3><?php echo htmlspecialchars($team_count); ?></h3><p>🚩 ทีมในสังกัด</p></div>
    <div class="stat-card"><h3><?php echo htmlspecialchars($upcoming_matches_count); ?></h3><p>🗓️ แมตช์ที่กำลังจะแข่ง</p></div>
    <div class="stat-card"><h3><?php echo htmlspecialchars($news_count); ?></h3><p>📰 ข่าวทั้งหมด</p></div>
</div>

<div class="activity-grid">
    <div class="dashboard-section">
        <h2>ข่าวล่าสุด</h2>
        <ul class="activity-list">
            <?php foreach($recent_news as $news): // ใช้ foreach สำหรับข้อมูลที่ fetchAll มาแล้ว ?>
                <li><a href="edit_news.php?id=<?php echo htmlspecialchars($news['id']); ?>"><?php echo htmlspecialchars($news['title']); ?></a></li>
            <?php endforeach; ?>
            <?php if (empty($recent_news)): ?>
                <li>ไม่มีข่าวล่าสุด</li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="dashboard-section">
        <h2>แฟนคลับล่าสุด</h2>
        <ul class="activity-list">
            <?php foreach($recent_users as $user): // ใช้ foreach สำหรับข้อมูลที่ fetchAll มาแล้ว ?>
                <li><?php echo htmlspecialchars($user['username']); ?></li>
            <?php endforeach; ?>
            <?php if (empty($recent_users)): ?>
                <li>ไม่มีแฟนคลับล่าสุด</li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>