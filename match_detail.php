<?php
require_once 'includes/header.php';
if (!isset($_GET['id'])) { header("Location: matches.php"); exit; }

$match_id = $_GET['id'];

// Get match details
$match_sql = "SELECT * FROM matches WHERE id = ?";
$stmt_match = $conn->prepare($match_sql);
$stmt_match->bind_param("i", $match_id);
$stmt_match->execute();
$match = $stmt_match->get_result()->fetch_assoc();
if (!$match) { header("Location: matches.php"); exit; }

// Get roster for this match using a JOIN
$roster_sql = "
    SELECT p.ign, p.role, p.image_url, p.id as player_id
    FROM players p
    JOIN match_rosters mr ON p.id = mr.player_id
    WHERE mr.match_id = ?
";
$stmt_roster = $conn->prepare($roster_sql);
$stmt_roster->bind_param("i", $match_id);
$stmt_roster->execute();
$roster = $stmt_roster->get_result();
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
    <h1>TAE Esport <span style="color:#aaa;">vs</span> <?php echo htmlspecialchars($match['opponent_team']); ?></h1>
    <p><?php echo htmlspecialchars($match['game']); ?> - <?php echo date('d F Y, H:i', strtotime($match['match_datetime'])); ?></p>
    <?php if ($match['status'] == 'Finished'): ?>
        <h2>ผลการแข่งขัน: <?php echo htmlspecialchars($match['result']); ?></h2>
    <?php endif; ?>
</div>

<div class="container">
    <h2 class="roster-title">Active player</h2>
    <div class="player-grid">
    <?php if ($roster->num_rows > 0): ?>
        <?php while($player = $roster->fetch_assoc()): ?>
            <a href="player_detail.php?id=<?php echo $player['player_id']; ?>" class="player-card">
                <img src="<?php echo !empty($player['image_url']) ? htmlspecialchars($player['image_url']) : 'assets/img/default_player.png'; ?>" alt="<?php echo htmlspecialchars($player['ign']); ?>">
                <div class="ign"><?php echo htmlspecialchars($player['ign']); ?></div>
                <div class="role"><?php echo htmlspecialchars($player['role']); ?></div>
            </a>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center; grid-column: 1 / -1;">ยังไม่มีการประกาศรายชื่อผู้เล่นสำหรับแมตช์นี้</p>
    <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>