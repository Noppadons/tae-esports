<?php
$active_page = 'matches';
$page_title = 'แก้ไขตารางแข่ง';
require_once 'includes/admin_header.php';

$match_id = $_GET['id'] ?? 0;
if ($match_id == 0) { header("location: manage_matches.php"); exit(); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $match_id = $_POST['id'];
    $match_datetime = $_POST['match_datetime'];
    $game = $_POST['game'];
    $opponent_team = $_POST['opponent_team'];
    $status = $_POST['status'];
    $result = $_POST['result'];

    $sql = "UPDATE matches SET match_datetime = ?, game = ?, opponent_team = ?, status = ?, result = ? WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssssi", $match_datetime, $game, $opponent_team, $status, $result, $match_id);
        $stmt->execute();
        header("location: manage_matches.php");
        exit();
    }
}

$sql = "SELECT * FROM matches WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $match_id);
$stmt->execute();
$db_result = $stmt->get_result();
$match = $db_result->fetch_assoc();
if (!$match) { header("location: manage_matches.php"); exit(); }

// Format datetime for the input field
$match_datetime_formatted = date('Y-m-d\TH:i', strtotime($match['match_datetime']));
?>
<style>
    input[type=datetime-local], select { width: 100%; padding: 8px; margin: 6px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .back-link { display: inline-block; margin-top: 15px; text-decoration: none; color: #007bff; }
</style>

<h1>แก้ไขตารางแข่ง</h1>
<div class="form-container">
    <form action="edit_match.php?id=<?php echo $match_id; ?>" method="post">
        <input type="hidden" name="id" value="<?php echo $match_id; ?>"/>
        <label for="match_datetime">วัน-เวลาที่แข่ง</label>
        <input type="datetime-local" id="match_datetime" name="match_datetime" value="<?php echo $match_datetime_formatted; ?>" required>
        
        <label for="game">เกม</label>
        <select id="game" name="game" required>
            <option value="DOTA2" <?php if($match['game'] == 'DOTA2') echo 'selected'; ?>>DOTA2</option>
            <option value="PUBG" <?php if($match['game'] == 'PUBG') echo 'selected'; ?>>PUBG</option>
            <option value="ROV" <?php if($match['game'] == 'ROV') echo 'selected'; ?>>ROV</option>
            <option value="Other" <?php if($match['game'] == 'Other') echo 'selected'; ?>>Other</option>
        </select>

        <label for="opponent_team">ทีมคู่แข่ง</label>
        <input type="text" id="opponent_team" name="opponent_team" value="<?php echo htmlspecialchars($match['opponent_team']); ?>" required>

        <label for="status">สถานะ</label>
        <select id="status" name="status" required>
            <option value="Upcoming" <?php if($match['status'] == 'Upcoming') echo 'selected'; ?>>Upcoming</option>
            <option value="Finished" <?php if($match['status'] == 'Finished') echo 'selected'; ?>>Finished</option>
        </select>

        <label for="result">ผลการแข่งขัน (เช่น 2-1, 0-2)</label>
        <input type="text" id="result" name="result" value="<?php echo htmlspecialchars($match['result']); ?>">

        <button type="submit" style="background-color: #007bff;">บันทึกการเปลี่ยนแปลง</button>
    </form>
    <a href="manage_matches.php" class="back-link"> &laquo; กลับไปหน้าจัดการตารางแข่ง</a>
</div>

<?php require_once 'includes/admin_footer.php'; ?>