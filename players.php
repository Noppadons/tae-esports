<?php require_once 'includes/header.php'; ?>

<?php
// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว (มาจาก includes/header.php -> db_connect.php)
if (!isset($conn) || !$conn instanceof PDO) {
    echo "<p style='text-align:center; color:red;'>ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง</p>";
    exit;
}

$teams_with_players = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง

// 1. ดึงข้อมูลทีมและผู้เล่นทั้งหมดใน Query เดียว (PDO)
$sql_teams_players = "
    SELECT 
        t.id AS team_id, t.team_name, t.logo_url,
        p.id AS player_id, p.ign, p.role, p.image_url
    FROM teams t
    LEFT JOIN players p ON t.id = p.team_id
    ORDER BY t.team_name ASC, p.ign ASC
";

try {
    $stmt_teams_players = $conn->query($sql_teams_players); // ใช้ query() สำหรับ SELECT ทั้งหมดที่ไม่มี Parameters
    
    // 2. จัดกลุ่มข้อมูลด้วย PHP
    while ($row = $stmt_teams_players->fetch(PDO::FETCH_ASSOC)) {
        $team_id = $row['team_id'];
        if (!isset($teams_with_players[$team_id])) {
            $teams_with_players[$team_id] = [
                'team_name' => $row['team_name'] ?? '',
                'logo_url' => $row['logo_url'] ?? '',
                'players' => []
            ];
        }
        // เพิ่มผู้เล่นเข้าไปในทีม ถ้ามีข้อมูลผู้เล่น (player_id ไม่เป็น null)
        if ($row['player_id'] !== null) {
            $teams_with_players[$team_id]['players'][] = $row;
        }
    }
} catch (PDOException $e) {
    error_log("Database error fetching teams and players: " . $e->getMessage());
    echo "<p style='text-align:center; color:red;'>ไม่สามารถโหลดข้อมูลทีมและผู้เล่นได้ในขณะนี้</p>";
}
?>

<style>
    /* ... CSS ทั้งหมดเหมือนกับใน "แบบที่ 1" ... */
    .team-section { margin-bottom: 50px; }
    .team-header { display: flex; align-items: center; gap: 20px; border-bottom: 3px solid #007bff; padding-bottom: 15px; margin-bottom: 25px; }
    .team-header img { width: 80px; height: 80px; object-fit: contain; }
    .team-header h2 { margin: 0; font-size: 2.5rem; }
    .player-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; }
    .player-card { background-color: #1f1f1f; border-radius: 8px; text-align: center; padding: 20px; transition: transform 0.3s, background-color 0.3s; text-decoration: none; display: block; color: inherit; }
    .player-card:hover { transform: translateY(-10px); background-color: #2a2a2a; }
    .player-card img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #007bff; }
    .player-card .ign { font-size: 1.5rem; font-weight: bold; margin: 15px 0 5px 0; color: #fff; }
    .player-card .role { color: #aaa; }
    /* Assuming .container and .section-title are from includes/header.php or global CSS */
    .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .section-title { text-align: center; color: #fff; font-size: 2.5rem; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 1px solid #333; }
</style>

<div class="container">
    <h1 class="section-title">ทีมและผู้เล่นของเรา</h1>

    <?php 
    // 3. แสดงผลจาก Array ที่จัดกลุ่มแล้ว
    if (!empty($teams_with_players)) {
        foreach ($teams_with_players as $team_id => $team_data) {
    ?>
    <section class="team-section">
        <div class="team-header">
            <img src="../<?php echo !empty($team_data['logo_url']) ? htmlspecialchars($team_data['logo_url']) : 'assets/img/default_logo.png'; ?>" alt="<?php echo htmlspecialchars($team_data['team_name']); ?>">
            <h2><?php echo htmlspecialchars($team_data['team_name']); ?></h2>
        </div>
        
        <div class="player-grid">
            <?php if (!empty($team_data['players'])): ?>
                <?php foreach ($team_data['players'] as $player): ?>
                    <a href="player_detail.php?id=<?php echo htmlspecialchars($player['player_id'] ?? ''); ?>" class="player-card">
                        <img src="../<?php echo !empty($player['image_url']) ? htmlspecialchars($player['image_url']) : 'assets/img/default_player.png'; ?>" alt="<?php echo htmlspecialchars($player['ign'] ?? ''); ?>">
                        <div class="ign"><?php echo htmlspecialchars($player['ign'] ?? ''); ?></div>
                        <div class="role"><?php echo htmlspecialchars($player['role'] ?? ''); ?></div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align: center;">ยังไม่มีผู้เล่นในทีมนี้</p>
            <?php endif; ?>
        </div>
    </section>
    <?php
        }
    } else {
        echo "<p style='text-align:center;'>ยังไม่มีการสร้างทีมในระบบ</p>";
    }
    ?>
</div>

<?php require_once 'includes/footer.php'; ?>