<?php
$active_page = 'dashboard';
$page_title = 'Dashboard';
require_once 'includes/admin_header.php';

// ดึงข้อมูลสรุปทั้งหมดจากฐานข้อมูล
$player_count_result = $conn->query("SELECT COUNT(*) as count FROM players");
$player_count = $player_count_result->fetch_assoc()['count'];
$team_count_result = $conn->query("SELECT COUNT(*) as count FROM teams");
$team_count = $team_count_result->fetch_assoc()['count'];
$upcoming_matches_result = $conn->query("SELECT COUNT(*) as count FROM matches WHERE status = 'Upcoming'");
$upcoming_matches_count = $upcoming_matches_result->fetch_assoc()['count'];
$news_count_result = $conn->query("SELECT COUNT(*) as count FROM news");
$news_count = $news_count_result->fetch_assoc()['count'];
$recent_news = $conn->query("SELECT id, title FROM news ORDER BY created_at DESC LIMIT 5");
$recent_users = $conn->query("SELECT username FROM users ORDER BY created_at DESC LIMIT 5");
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
    <div class="stat-card"><h3><?php echo $player_count; ?></h3><p>👥 นักกีฬาทั้งหมด</p></div>
    <div class="stat-card"><h3><?php echo $team_count; ?></h3><p>🚩 ทีมในสังกัด</p></div>
    <div class="stat-card"><h3><?php echo $upcoming_matches_count; ?></h3><p>🗓️ แมตช์ที่กำลังจะแข่ง</p></div>
    <div class="stat-card"><h3><?php echo $news_count; ?></h3><p>📰 ข่าวทั้งหมด</p></div>
</div>

<div class="activity-grid">
    <div class="dashboard-section">
        <h2>ข่าวล่าสุด</h2>
        <ul class="activity-list">
            <?php while($news = $recent_news->fetch_assoc()): ?>
                <li><a href="edit_news.php?id=<?php echo $news['id']; ?>"><?php echo htmlspecialchars($news['title']); ?></a></li>
            <?php endwhile; ?>
        </ul>
    </div>
    <div class="dashboard-section">
        <h2>แฟนคลับล่าสุด</h2>
        <ul class="activity-list">
             <?php while($user = $recent_users->fetch_assoc()): ?>
                <li><?php echo htmlspecialchars($user['username']); ?></li>
            <?php endwhile; ?>
        </ul>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>