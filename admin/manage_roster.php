<?php
session_start();
require_once '../includes/db_connect.php'; // ตรวจสอบเส้นทางของ db_connect.php ว่าถูกต้อง

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    die("Database connection failed. Please check ../includes/db_connect.php");
}

// Security check (ตรวจสอบสิทธิ์ Admin)
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}

// ตรวจสอบว่ามี match_id ส่งมาหรือไม่
if (!isset($_GET['match_id']) || empty(trim($_GET['match_id']))) {
    header("location: manage_matches.php");
    exit;
}

$match_id = trim($_GET['match_id']);

// --- Handle Form Submission (PDO) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // เริ่ม Transaction เพื่อให้มั่นใจว่าข้อมูลถูกลบและเพิ่มอย่างถูกต้อง หรือไม่ก็ Rollback ทั้งหมด
        $conn->beginTransaction();

        // 1. Clear existing roster for this match
        $delete_sql = "DELETE FROM match_rosters WHERE match_id = :match_id";
        $stmt_del = $conn->prepare($delete_sql);
        $stmt_del->bindParam(':match_id', $match_id, PDO::PARAM_INT);
        $stmt_del->execute();

        // 2. Insert new roster
        if (!empty($_POST['player_ids']) && is_array($_POST['player_ids'])) {
            $insert_sql = "INSERT INTO match_rosters (match_id, player_id) VALUES (:match_id, :player_id)";
            $stmt_ins = $conn->prepare($insert_sql);
            foreach ($_POST['player_ids'] as $player_id) {
                // ตรวจสอบและ cast $player_id เป็น integer เพื่อความปลอดภัยอีกชั้น
                $player_id_int = (int) $player_id; 
                $stmt_ins->bindParam(':match_id', $match_id, PDO::PARAM_INT);
                $stmt_ins->bindParam(':player_id', $player_id_int, PDO::PARAM_INT);
                $stmt_ins->execute();
            }
        }
        
        $conn->commit(); // Commit Transaction ถ้าทุกอย่างสำเร็จ
        header("Location: manage_matches.php"); // Redirect back after saving
        exit;

    } catch (PDOException $e) {
        $conn->rollBack(); // Rollback ถ้ามี Error เกิดขึ้น
        error_log("Database error during roster update: " . $e->getMessage());
        // คุณอาจจะแจ้งผู้ใช้ว่ามีข้อผิดพลาด
        die("มีข้อผิดพลาดในการบันทึกรายชื่อผู้เล่น: " . $e->getMessage());
    }
}

// --- Fetch Data for Display (PDO) ---
$match = null; // กำหนดค่าเริ่มต้น
// Get Match Details
$match_sql = "SELECT game, opponent_team, match_datetime FROM matches WHERE id = :match_id";
try {
    $stmt_match = $conn->prepare($match_sql);
    $stmt_match->bindParam(':match_id', $match_id, PDO::PARAM_INT);
    $stmt_match->execute();
    $match = $stmt_match->fetch(PDO::FETCH_ASSOC);
    if (!$match) {
        header("location: manage_matches.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error fetching match details: " . $e->getMessage());
    die("ไม่สามารถดึงรายละเอียดแมตช์ได้: " . $e->getMessage());
}

// Get players already in the roster
$roster_player_ids = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$roster_sql = "SELECT player_id FROM match_rosters WHERE match_id = :match_id";
try {
    $stmt_roster = $conn->prepare($roster_sql);
    $stmt_roster->bindParam(':match_id', $match_id, PDO::PARAM_INT);
    $stmt_roster->execute();
    $roster_results = $stmt_roster->fetchAll(PDO::FETCH_ASSOC);
    foreach ($roster_results as $row) {
        $roster_player_ids[] = (int)$row['player_id']; // Cast เป็น int
    }
} catch (PDOException $e) {
    error_log("Database error fetching current roster: " . $e->getMessage());
    // ไม่ถึงกับ fatal error แค่ไม่มีผู้เล่นในรายชื่อ
}

// Get all players eligible for this game
$eligible_players = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$eligible_players_sql = "SELECT id, ign FROM players WHERE game = :game ORDER BY ign ASC";
try {
    $stmt_eligible = $conn->prepare($eligible_players_sql);
    $stmt_eligible->bindParam(':game', $match['game'], PDO::PARAM_STR);
    $stmt_eligible->execute();
    $eligible_players = $stmt_eligible->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching eligible players: " . $e->getMessage());
    die("ไม่สามารถดึงรายชื่อผู้เล่นที่เข้าเกณฑ์ได้: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดตัวผู้เล่น</title>
    <style>
        /* ... (CSS styles remains unchanged from your provided code, with minor adjustments for general use) ... */
        body { font-family: sans-serif; margin: 0; background-color: #f8f9fa; color: #343a40;}
        .main-content { max-width: 800px; margin: 20px auto; padding: 25px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h1 { color: #007bff; margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        h3 { color: #343a40; margin-bottom: 15px; }
        hr { border: none; border-top: 1px solid #dee2e6; margin: 25px 0; }
        h4 { color: #495057; margin-bottom: 15px; font-size: 1.2rem; }
        .player-list { list-style: none; padding: 0; border: 1px solid #ced4da; border-radius: 6px; max-height: 400px; overflow-y: auto; background-color: #f1f3f5;}
        .player-list li { background-color: #fff; margin: 0; padding: 10px 15px; border-bottom: 1px solid #e9ecef; display: flex; align-items: center; }
        .player-list li:last-child { border-bottom: none; }
        .player-list label { font-size: 1.1rem; cursor: pointer; display: flex; align-items: center; width: 100%; }
        .player-list input[type="checkbox"] { margin-right: 15px; transform: scale(1.2); cursor: pointer; }
        button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 20px; }
        button:hover { background-color: #0056b3; }
        a { text-decoration: none; color: #007bff; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="main-content">
    <h1>จัดตัวผู้เล่น</h1>
    <h3>
        แมตช์: <?php echo htmlspecialchars($match['game'] ?? ''); ?> vs <?php echo htmlspecialchars($match['opponent_team'] ?? ''); ?><br>
        วันที่: <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($match['match_datetime'] ?? ''))); ?>
    </h3>
    <hr>
    <form action="manage_roster.php?match_id=<?php echo htmlspecialchars($match_id); ?>" method="post">
        <h4>เลือกผู้เล่นที่จะลงแข่ง:</h4>
        <ul class="player-list">
            <?php if (!empty($eligible_players)): ?>
                <?php foreach ($eligible_players as $player): ?>
                    <li>
                        <label>
                            <input 
                                type="checkbox" 
                                name="player_ids[]" 
                                value="<?php echo htmlspecialchars($player['id'] ?? ''); ?>"
                                <?php if (in_array((int)($player['id'] ?? 0), $roster_player_ids)) echo 'checked'; ?>
                            >
                            <?php echo htmlspecialchars($player['ign'] ?? ''); ?>
                        </label>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>ไม่พบผู้เล่นที่สามารถลงแข่งได้ในเกมนี้</li>
            <?php endif; ?>
        </ul>
        <br>
        <button type="submit">บันทึกรายชื่อ</button>
        <a href="manage_matches.php" style="margin-left:10px;">ยกเลิก</a>
    </form>
</div>
</body>
</html>