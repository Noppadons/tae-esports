<?php
require_once 'includes/header.php';

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว (มาจาก includes/header.php -> db_connect.php)
if (!isset($conn) || !$conn instanceof PDO) {
    echo "<p style='text-align:center; color:red;'>ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง</p>";
    exit;
}

if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("Location: players.php");
    exit;
}

$player_id = trim($_GET['id']);

// --- Get player details with team info (PDO) ---
$player = null; // กำหนดค่าเริ่มต้น
$sql_select_player = "
    SELECT p.*, t.team_name, t.game_name
    FROM players p
    LEFT JOIN teams t ON p.team_id = t.id
    WHERE p.id = :id
";

try {
    $stmt_player = $conn->prepare($sql_select_player);
    $stmt_player->bindParam(':id', $player_id, PDO::PARAM_INT);
    $stmt_player->execute();
    $player = $stmt_player->fetch(PDO::FETCH_ASSOC);

    if (!$player) {
        header("Location: players.php"); // ถ้าไม่พบนักกีฬา
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error fetching player details for public page: " . $e->getMessage());
    die("<p style='text-align:center; color:red;'>ไม่สามารถโหลดข้อมูลนักกีฬาได้ในขณะนี้</p>");
}
?>
<style>
    .profile-container { display: grid; grid-template-columns: 300px 1fr; gap: 40px; align-items: flex-start; }
    .profile-sidebar { text-align: center; }
    .profile-image { width: 250px; height: 250px; border-radius: 50%; object-fit: cover; border: 5px solid #007bff; }
    .ign-title { font-size: 2.5rem; color: #fff; margin-top: 15px; }
    .full-name { color: #bbb; font-size: 1.2rem; }
    .profile-main { background-color: #1f1f1f; padding: 30px; border-radius: 8px; }
    .profile-section h3 { font-size: 1.5rem; color: #007bff; border-bottom: 1px solid #444; padding-bottom: 10px; margin-top: 0; margin-bottom: 20px; }
    .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .stat-item { margin-bottom: 15px; }
    .stat-item label { display: block; margin-bottom: 8px; color: #ddd; }
    .progress-bar { background-color: #333; border-radius: 20px; height: 20px; width: 100%; overflow: hidden; }
    .progress-bar-inner { height: 100%; background: linear-gradient(90deg, #007bff, #00aaff); border-radius: 20px; }
    .strengths-weaknesses ul { list-style: none; padding: 0; }
    .strengths-weaknesses li { padding: 8px; margin-bottom: 5px; border-radius: 4px; }
    .strengths li { background-color: rgba(40, 167, 69, 0.2); border-left: 3px solid #28a745; }
    .weaknesses li { background-color: rgba(220, 53, 69, 0.2); border-left: 3px solid #dc3545; }
    .grid-2-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    /* Assuming .container and .section-title are from includes/header.php or global CSS */
    .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .section-title { text-align: center; color: #fff; font-size: 2.5rem; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 1px solid #333; }
</style>

<div class="container">
    <div class="profile-container">
        <aside class="profile-sidebar">
            <img src="../<?php echo !empty($player['image_url']) ? htmlspecialchars($player['image_url']) : 'assets/img/default_player.png'; ?>" alt="<?php echo htmlspecialchars($player['ign'] ?? ''); ?>" class="profile-image">
            <h1 class="ign-title"><?php echo htmlspecialchars($player['ign'] ?? ''); ?></h1>
            <p class="full-name"><?php echo htmlspecialchars($player['full_name'] ?? ''); ?></p>
        </aside>
        <main class="profile-main">
            <div class="profile-section">
                <h3>ข้อมูลพื้นฐาน</h3>
                <p><strong>เกม:</strong> <?php echo htmlspecialchars($player['game_name'] ?? 'N/A'); ?></p>
                <p><strong>ทีม:</strong> <?php echo htmlspecialchars($player['team_name'] ?? '<i>ยังไม่สังกัดทีม</i>'); ?></p>
                <p><strong>บทบาท (Role):</strong> <?php echo htmlspecialchars($player['role'] ?? ''); ?></p>
                <p><strong>ตำแหน่ง (Positions):</strong> <?php echo htmlspecialchars($player['positions'] ?? ''); ?></p>
            </div>
            <div class="profile-section">
                <h3>ค่าสถานะ</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <label>อ่านเกม (Game Sense)</label>
                        <div class="progress-bar"><div class="progress-bar-inner" style="width: <?php echo htmlspecialchars($player['stat_game_sense'] ?? 0); ?>%;"></div></div>
                    </div>
                    <div class="stat-item">
                        <label>ฮีโร่พูล (Hero Pool)</label>
                        <div class="progress-bar"><div class="progress-bar-inner" style="width: <?php echo htmlspecialchars($player['stat_hero_pool'] ?? 0); ?>%;"></div></div>
                    </div>
                    <div class="stat-item">
                        <label>รีเฟล็กซ์ (Reflex)</label>
                        <div class="progress-bar"><div class="progress-bar-inner" style="width: <?php echo htmlspecialchars($player['stat_reflex'] ?? 0); ?>%;"></div></div>
                    </div>
                    <div class="stat-item">
                        <label>เกมเพลย์ (Gameplay)</label>
                        <div class="progress-bar"><div class="progress-bar-inner" style="width: <?php echo htmlspecialchars($player['stat_gameplay'] ?? 0); ?>%;"></div></div>
                    </div>
                </div>
            </div>
            <div class="profile-section strengths-weaknesses">
                <div class="grid-2-col">
                    <div class="strengths">
                        <h3>จุดเด่น</h3>
                        <ul><li><?php echo nl2br(htmlspecialchars($player['strengths'] ?? '')); ?></li></ul>
                    </div>
                    <div class="weaknesses">
                        <h3>จุดด้อย</h3>
                        <ul><li><?php echo nl2br(htmlspecialchars($player['weaknesses'] ?? '')); ?></li></ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>