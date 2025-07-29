<?php
$active_page = 'matches';
$page_title = 'จัดการตารางแข่ง';
require_once 'includes/admin_header.php'; // admin_header.php ควรจะมีการเรียก db_connect.php อยู่แล้ว

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    die("Database connection failed. Please check includes/db_connect.php");
}

// Security check (ตรวจสอบสิทธิ์ Admin) - ควรมีในทุกหน้า admin
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}

// Handle Add Match Form (PDO)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_match'])) {
    $match_datetime = $_POST['match_datetime'] ?? '';
    $game = $_POST['game'] ?? '';
    $opponent_team = $_POST['opponent_team'] ?? '';

    $sql_insert = "INSERT INTO matches (match_datetime, game, opponent_team, status) VALUES (:match_datetime, :game, :opponent_team, 'Upcoming')";
    
    try {
        $stmt_insert = $conn->prepare($sql_insert);
        // ผูกค่า Parameters
        $stmt_insert->bindParam(':match_datetime', $match_datetime, PDO::PARAM_STR);
        $stmt_insert->bindParam(':game', $game, PDO::PARAM_STR);
        $stmt_insert->bindParam(':opponent_team', $opponent_team, PDO::PARAM_STR);
        $stmt_insert->execute();
        
        header("Location: manage_matches.php"); // Redirect เพื่อ Refresh หน้า
        exit();

    } catch (PDOException $e) {
        error_log("Database error adding new match: " . $e->getMessage());
        // คุณอาจจะแจ้งผู้ใช้ว่ามีข้อผิดพลาดในการเพิ่มข้อมูล
        echo "มีข้อผิดพลาดในการเพิ่มแมตช์ใหม่: " . $e->getMessage();
    }
}

// --- ดึงรายการแข่งขันทั้งหมดมาแสดง (PDO) ---
$matches = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$matches_sql = "SELECT * FROM matches ORDER BY match_datetime DESC";
try {
    $stmt_matches = $conn->query($matches_sql); // ใช้ query() สำหรับ SELECT ทั้งหมดที่ไม่มี Parameters
    $matches = $stmt_matches->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array
} catch (PDOException $e) {
    error_log("Database error fetching matches: " . $e->getMessage());
    // คุณอาจจะแสดงข้อความ error หรือแสดง array ว่างเปล่า
}

?>
<style>
    /* ... (CSS styles remains unchanged) ... */
    input[type=datetime-local], select { width: 100%; padding: 8px; margin: 6px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    /* เพิ่ม style สำหรับ form, container และ table ถ้ายังไม่มีจาก admin_header */
    .form-container { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); margin-bottom: 30px; }
    .form-container h2 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    button[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 10px; }
    button[type="submit"]:hover { background-color: #0056b3; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
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
            <th>จัดการผู้เล่น</th>
            <th>จัดการแมตช์</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($matches)): // ตรวจสอบว่า $matches ไม่ว่างเปล่า ?>
            <?php foreach($matches as $row): // ใช้ foreach loop ?>
            <tr>
                <td><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($row['match_datetime'] ?? ''))); ?></td>
                <td><?php echo htmlspecialchars($row['game'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['opponent_team'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['status'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['result'] ?? '-'); ?></td>
                <td>
                    <a href="manage_roster.php?match_id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>">จัดตัวผู้เล่น</a>
                </td>
                <td>
                    <a href="edit_match.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>">แก้ไข</a> |
                    <a href="delete_match.php?id=<?php echo htmlspecialchars($row['id'] ?? ''); ?>" onclick="return confirm('ยืนยันการลบแมตช์นี้?');">ลบ</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center;">ยังไม่มีข้อมูลการแข่งขันในระบบ</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once 'includes/admin_footer.php'; ?>