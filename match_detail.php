<?php require_once 'includes/header.php'; ?>

<?php
// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว (มาจาก includes/header.php -> db_connect.php)
if (!isset($conn) || !$conn instanceof PDO) {
    echo "<p style='text-align:center; color:red;'>ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง</p>";
    exit;
}

if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("Location: matches.php");
    exit;
}

$match_id = trim($_GET['id']);

// --- Get match details (PDO) ---
$match = null; // กำหนดค่าเริ่มต้น
$match_sql = "SELECT * FROM matches WHERE id = :id";
try {
    $stmt_match = $conn->prepare($match_sql);
    $stmt_match->bindParam(':id', $match_id, PDO::PARAM_INT);
    $stmt_match->execute();
    $match = $stmt_match->fetch(PDO::FETCH_ASSOC);
    if (!$match) {
        header("Location: matches.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error fetching match details for public page: " . $e->getMessage());
    die("<p style='text-align:center; color:red;'>ไม่สามารถโหลดรายละเอียดแมตช์ได้</p>");
}

// --- Get roster for this match using a JOIN (PDO) ---
$roster_players = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$roster_sql = "
    SELECT p.ign, p.role, p.image_url, p.id as player_id
    FROM players p
    JOIN match_rosters mr ON p.id = mr.player_id
    WHERE mr.match_id = :match_id
    ORDER BY p.ign ASC -- เพิ่ม ORDER BY เพื่อให้เรียงลำดับเสมอ
";
try {
    $stmt_roster = $conn->prepare($roster_sql);
    $stmt_roster->bindParam(':match_id', $match_id, PDO::PARAM_INT);
    $stmt_roster->execute();
    $roster_players = $stmt_roster->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมด
} catch (PDOException $e) {
    error_log("Database error fetching match roster for public page: " . $e->getMessage());
    // ไม่ถึงกับ Fatal Error แต่แสดงข้อความแจ้งแทน
    echo "<p style='text-align:center; color:red;'>ไม่สามารถโหลดรายชื่อผู้เล่นได้ในขณะนี้</p>";
}
?>

<style>
    .match-header { text-align: center; padding: 40px 20px; background-color: #1f1f1f; }
    .match-header h1 { font-size: 3rem; margin: 0; }
    .match-header p { font-size: 1.2rem; color: #aaa; }
    .roster-title { text-align: center; font-size: 2rem; margin-top: 40px; }
    /* Re-use player card styles from players.php */
    .player-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px; max-width: 1000px; margin: 20px auto; }
    .player-card { background-color: #1f1f1f; border-radius: 8px; text-align: center; padding: 20px; text-decoration: none; display: block; color: inherit; }
    .player-card img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #007bff; }
    .player-card .ign { font-size: 1.3rem; font-weight: bold; margin: 10px 0 5px 0; color: #fff; }
    .player-card .role { color: #aaa; }
</style>

<div class="match-header">
    <h1>TAE Esport <span style="color:#aaa;">vs</span> <?php echo htmlspecialchars($match['opponent_team'] ?? ''); ?></h1>
    <p><?php echo htmlspecialchars($match['game'] ?? ''); ?> - <?php echo htmlspecialchars(date('d F Y, H:i', strtotime($match['match_datetime'] ?? ''))); ?></p>
    <?php if (($match['status'] ?? '') == 'Finished'): ?>
        <h2>ผลการแข่งขัน: <?php echo htmlspecialchars($match['result'] ?? ''); ?></h2>
    <?php endif; ?>
</div>

<div class="container">
    <h2 class="roster-title">Active player</h2>
    <div class="player-grid">
    <?php if (!empty($roster_players)): // ตรวจสอบว่ามีผู้เล่นใน roster หรือไม่ ?>
        <?php foreach($roster_players as $player): // ใช้ foreach loop ?>
            <a href="player_detail.php?id=<?php echo htmlspecialchars($player['player_id'] ?? ''); ?>" class="player-card">
                <img src="../<?php echo !empty($player['image_url']) ? htmlspecialchars($player['image_url']) : 'assets/img/default_player.png'; ?>" alt="<?php echo htmlspecialchars($player['ign'] ?? ''); ?>">
                <div class="ign"><?php echo htmlspecialchars($player['ign'] ?? ''); ?></div>
                <div class="role"><?php echo htmlspecialchars($player['role'] ?? ''); ?></div>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align:center; grid-column: 1 / -1;">ยังไม่มีการประกาศรายชื่อผู้เล่นสำหรับแมตช์นี้</p>
    <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>