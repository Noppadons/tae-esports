<?php require_once 'includes/header.php'; ?>

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
</style>

<div class="container">
    <h1 class="section-title">ทีมและผู้เล่นของเรา</h1>

    <?php
    // 1. ดึงข้อมูลทีมและผู้เล่นทั้งหมดใน Query เดียว
    $sql = "
        SELECT 
            t.id AS team_id, t.team_name, t.logo_url,
            p.id AS player_id, p.ign, p.role, p.image_url
        FROM teams t
        LEFT JOIN players p ON t.id = p.team_id
        ORDER BY t.team_name, p.ign
    ";
    $result = $conn->query($sql);

    $teams_with_players = [];
    if ($result->num_rows > 0) {
        // 2. จัดกลุ่มข้อมูลด้วย PHP
        while ($row = $result->fetch_assoc()) {
            $team_id = $row['team_id'];
            if (!isset($teams_with_players[$team_id])) {
                $teams_with_players[$team_id] = [
                    'team_name' => $row['team_name'],
                    'logo_url' => $row['logo_url'],
                    'players' => []
                ];
            }
            // เพิ่มผู้เล่นเข้าไปในทีม ถ้ามีข้อมูลผู้เล่น
            if ($row['player_id'] !== null) {
                $teams_with_players[$team_id]['players'][] = $row;
            }
        }
    }

    // 3. แสดงผลจาก Array ที่จัดกลุ่มแล้ว
    if (!empty($teams_with_players)) {
        foreach ($teams_with_players as $team_id => $team_data) {
    ?>
    <section class="team-section">
        <div class="team-header">
            <img src="<?php echo !empty($team_data['logo_url']) ? htmlspecialchars($team_data['logo_url']) : 'assets/img/default_logo.png'; ?>" alt="<?php echo htmlspecialchars($team_data['team_name']); ?>">
            <h2><?php echo htmlspecialchars($team_data['team_name']); ?></h2>
        </div>
        
        <div class="player-grid">
            <?php if (!empty($team_data['players'])): ?>
                <?php foreach ($team_data['players'] as $player): ?>
                    <a href="player_detail.php?id=<?php echo $player['player_id']; ?>" class="player-card">
                        <img src="<?php echo !empty($player['image_url']) ? htmlspecialchars($player['image_url']) : 'assets/img/default_player.png'; ?>" alt="<?php echo htmlspecialchars($player['ign']); ?>">
                        <div class="ign"><?php echo htmlspecialchars($player['ign']); ?></div>
                        <div class="role"><?php echo htmlspecialchars($player['role']); ?></div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p>ยังไม่มีผู้เล่นในทีมนี้</p>
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