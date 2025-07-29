<?php
session_start();
require_once '../includes/db_connect.php';
if (!isset($_SESSION["admin_logged_in"])) { header("location: login.php"); exit; }
if (!isset($_GET['match_id'])) { header("location: manage_matches.php"); exit; }

$match_id = $_GET['match_id'];

// --- Handle Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Clear existing roster for this match
    $delete_sql = "DELETE FROM match_rosters WHERE match_id = ?";
    $stmt_del = $conn->prepare($delete_sql);
    $stmt_del->bind_param("i", $match_id);
    $stmt_del->execute();

    // 2. Insert new roster
    if (!empty($_POST['player_ids'])) {
        $insert_sql = "INSERT INTO match_rosters (match_id, player_id) VALUES (?, ?)";
        $stmt_ins = $conn->prepare($insert_sql);
        foreach ($_POST['player_ids'] as $player_id) {
            $stmt_ins->bind_param("ii", $match_id, $player_id);
            $stmt_ins->execute();
        }
    }
    header("Location: manage_matches.php"); // Redirect back after saving
    exit;
}

// --- Fetch Data for Display ---
// Get Match Details
$match_sql = "SELECT game, opponent_team, match_datetime FROM matches WHERE id = ?";
$stmt_match = $conn->prepare($match_sql);
$stmt_match->bind_param("i", $match_id);
$stmt_match->execute();
$match = $stmt_match->get_result()->fetch_assoc();
if (!$match) { header("location: manage_matches.php"); exit; }

// Get players already in the roster
$roster_sql = "SELECT player_id FROM match_rosters WHERE match_id = ?";
$stmt_roster = $conn->prepare($roster_sql);
$stmt_roster->bind_param("i", $match_id);
$stmt_roster->execute();
$roster_result = $stmt_roster->get_result();
$roster_player_ids = [];
while ($row = $roster_result->fetch_assoc()) {
    $roster_player_ids[] = $row['player_id'];
}

// Get all players eligible for this game
$eligible_players_sql = "SELECT id, ign FROM players WHERE game = ?";
$stmt_eligible = $conn->prepare($eligible_players_sql);
$stmt_eligible->bind_param("s", $match['game']);
$stmt_eligible->execute();
$eligible_players = $stmt_eligible->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดตัวผู้เล่น</title>
    <style>
        /* ... ใช้ CSS คล้ายๆกับหน้าอื่นๆ ... */
        body { font-family: sans-serif; }
        .main-content { max-width: 800px; margin: 20px auto; padding: 20px; background-color: #f4f4f4; border-radius: 8px; }
        .player-list { list-style: none; padding: 0; }
        .player-list li { background-color: #fff; margin: 5px 0; padding: 10px; border-radius: 4px; }
        .player-list label { font-size: 1.2rem; cursor: pointer; }
        .player-list input { margin-right: 15px; transform: scale(1.5); }
        button { background-color: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 1rem; }
    </style>
</head>
<body>
<div class="main-content">
    <h1>จัดตัวผู้เล่น</h1>
    <h3>
        แมตช์: <?php echo htmlspecialchars($match['game']); ?> vs <?php echo htmlspecialchars($match['opponent_team']); ?><br>
        วันที่: <?php echo date('d M Y, H:i', strtotime($match['match_datetime'])); ?>
    </h3>
    <hr>
    <form action="manage_roster.php?match_id=<?php echo $match_id; ?>" method="post">
        <h4>เลือกผู้เล่นที่จะลงแข่ง:</h4>
        <ul class="player-list">
            <?php while ($player = $eligible_players->fetch_assoc()): ?>
                <li>
                    <label>
                        <input 
                            type="checkbox" 
                            name="player_ids[]" 
                            value="<?php echo $player['id']; ?>"
                            <?php if (in_array($player['id'], $roster_player_ids)) echo 'checked'; ?>
                        >
                        <?php echo htmlspecialchars($player['ign']); ?>
                    </label>
                </li>
            <?php endwhile; ?>
        </ul>
        <br>
        <button type="submit">บันทึกรายชื่อ</button>
        <a href="manage_matches.php" style="margin-left:10px;">ยกเลิก</a>
    </form>
</div>
</body>
</html>