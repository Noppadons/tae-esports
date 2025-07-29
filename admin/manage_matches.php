<?php
$active_page = 'matches';
$page_title = 'จัดการตารางแข่ง';
require_once 'includes/admin_header.php';

// Handle Add Match Form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_match'])) {
    $match_datetime = $_POST['match_datetime'];
    $game = $_POST['game'];
    $opponent_team = $_POST['opponent_team'];

    $sql = "INSERT INTO matches (match_datetime, game, opponent_team, status) VALUES (?, ?, ?, 'Upcoming')";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sss", $match_datetime, $game, $opponent_team);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_matches.php");
    exit();
}

$matches_result = $conn->query("SELECT * FROM matches ORDER BY match_datetime DESC");
?>
<style>
    input[type=datetime-local] { width: 100%; padding: 8px; margin: 6px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
</style>

<h1>จัดการตารางแข่ง</h1>
<div class="form-container">
    <h2>เพิ่มแมตช์ใหม่</h2>
    <form action="manage_matches.php" method="post">
        <label for="match_datetime">วัน-เวลาที่แข่ง</label>
        <input type="datetime-local" id="match_datetime" name="match_datetime" required>
        <label for="game">เกม</label>
        <select id="game" name="game" required>
            <option value="DOTA2">DOTA2</option>
            <option value="PUBG">PUBG</option>
            <option value="ROV">ROV</option>
            <option value="Other">Other</option>
        </select>
        <label for="opponent_team">ทีมคู่แข่ง</label>
        <input type="text" id="opponent_team" name="opponent_team" required>
        <button type="submit" name="add_match">เพิ่มแมตช์</button>
    </form>
</div>

<h2>รายการแข่งขันทั้งหมด</h2>
<table>
    <thead>
        <tr>
            <th>วัน-เวลา</th>
            <th>เกม</th>
            <th>ทีมคู่แข่ง</th>
            <th>สถานะ</th>
            <th>ผลการแข่ง</th>
            <th>จัดการทีม</th>
            <th>จัดการแมตช์</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($matches_result->num_rows > 0): ?>
            <?php while($row = $matches_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo date('d M Y, H:i', strtotime($row['match_datetime'])); ?></td>
                <td><?php echo htmlspecialchars($row['game']); ?></td>
                <td><?php echo htmlspecialchars($row['opponent_team']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td><?php echo htmlspecialchars($row['result'] ?? '-'); ?></td>
                <td>
                    <a href="manage_roster.php?match_id=<?php echo $row['id']; ?>">จัดตัวผู้เล่น</a>
                </td>
                <td>
                    <a href="edit_match.php?id=<?php echo $row['id']; ?>">แก้ไข</a> |
                    <a href="delete_match.php?id=<?php echo $row['id']; ?>" onclick="return confirm('ยืนยันการลบ?');">ลบ</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center;">ยังไม่มีข้อมูลการแข่งขันในระบบ</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once 'includes/admin_footer.php'; ?>