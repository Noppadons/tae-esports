<?php
// /admin/edit_match.php
session_start();
require_once 'includes/admin_header.php'; // admin_header.php ควรจะมีการเรียก db_connect.php อยู่แล้ว

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    die("Database connection failed. Please check includes/db_connect.php");
}

// Security check (ตรวจสอบสิทธิ์ Admin)
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}

// ดึง ID ของแมตช์จาก URL
$match_id = $_GET['id'] ?? 0;
if ($match_id == 0) {
    header("location: manage_matches.php");
    exit();
}

// --- ส่วนจัดการ POST Request (เมื่อมีการส่งฟอร์มเพื่อบันทึกการเปลี่ยนแปลง) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $match_id_post = $_POST['id'];
    $match_datetime = $_POST['match_datetime'];
    $game = $_POST['game'];
    $opponent_team = $_POST['opponent_team'];
    $status = $_POST['status'];
    $result = $_POST['result'];

    $sql = "UPDATE matches SET match_datetime = :match_datetime, game = :game, opponent_team = :opponent_team, status = :status, result = :result WHERE id = :id";
    
    try {
        $stmt = $conn->prepare($sql);
        // ผูกค่า Parameters
        $stmt->bindParam(':match_datetime', $match_datetime, PDO::PARAM_STR);
        $stmt->bindParam(':game', $game, PDO::PARAM_STR);
        $stmt->bindParam(':opponent_team', $opponent_team, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':result', $result, PDO::PARAM_STR);
        $stmt->bindParam(':id', $match_id_post, PDO::PARAM_INT);

        $stmt->execute();
        
        header("location: manage_matches.php"); // Redirect เมื่ออัปเดตสำเร็จ
        exit();

    } catch (PDOException $e) {
        error_log("Database error updating match: " . $e->getMessage());
        echo "มีข้อผิดพลาดในการบันทึกข้อมูลตารางแข่ง: " . $e->getMessage();
        // คุณสามารถจัดการข้อผิดพลาดตามความเหมาะสม เช่น แสดงข้อความ error
    }
}

// --- ส่วนดึงข้อมูลแมตช์เพื่อแสดงผลในฟอร์ม (GET Request) ---
$match = null; // กำหนดค่าเริ่มต้นเป็น null
$sql_select = "SELECT * FROM matches WHERE id = :id";
try {
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bindParam(':id', $match_id, PDO::PARAM_INT);
    $stmt_select->execute();
    $match = $stmt_select->fetch(PDO::FETCH_ASSOC); // ดึงข้อมูลแถวเดียว

    // ถ้าไม่พบแมตช์ตาม ID ที่ระบุ
    if (!$match) {
        header("location: manage_matches.php");
        exit();
    }

} catch (PDOException $e) {
    error_log("Database error fetching match details: " . $e->getMessage());
    die("ไม่สามารถดึงข้อมูลตารางแข่งได้: " . $e->getMessage());
}

// Format datetime for the input field (PHP's date function works with timestamps)
// ตรวจสอบว่า $match['match_datetime'] มีค่า และเป็นวันที่ที่ถูกต้องก่อน strtotime
$match_datetime_formatted = '';
if (isset($match['match_datetime']) && !empty($match['match_datetime'])) {
    $timestamp = strtotime($match['match_datetime']);
    if ($timestamp !== false) {
        $match_datetime_formatted = date('Y-m-d\TH:i', $timestamp);
    }
}

?>
<style>
    /* ... (CSS styles remains unchanged) ... */
    input[type=datetime-local], select { width: 100%; padding: 8px; margin: 6px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .back-link { display: inline-block; margin-top: 15px; text-decoration: none; color: #007bff; }
</style>

<h1>แก้ไขตารางแข่ง</h1>
<div class="form-container">
    <form action="edit_match.php?id=<?php echo htmlspecialchars($match_id); ?>" method="post">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($match_id); ?>"/>
        <label for="match_datetime">วัน-เวลาที่แข่ง</label>
        <input type="datetime-local" id="match_datetime" name="match_datetime" value="<?php echo htmlspecialchars($match_datetime_formatted); ?>" required>
        
        <label for="game">เกม</label>
        <select id="game" name="game" required>
            <option value="DOTA2" <?php if(isset($match['game']) && $match['game'] == 'DOTA2') echo 'selected'; ?>>DOTA2</option>
            <option value="PUBG" <?php if(isset($match['game']) && $match['game'] == 'PUBG') echo 'selected'; ?>>PUBG</option>
            <option value="ROV" <?php if(isset($match['game']) && $match['game'] == 'ROV') echo 'selected'; ?>>ROV</option>
            <option value="Other" <?php if(isset($match['game']) && $match['game'] == 'Other') echo 'selected'; ?>>Other</option>
        </select>

        <label for="opponent_team">ทีมคู่แข่ง</label>
        <input type="text" id="opponent_team" name="opponent_team" value="<?php echo htmlspecialchars($match['opponent_team'] ?? ''); ?>" required>

        <label for="status">สถานะ</label>
        <select id="status" name="status" required>
            <option value="Upcoming" <?php if(isset($match['status']) && $match['status'] == 'Upcoming') echo 'selected'; ?>>Upcoming</option>
            <option value="Finished" <?php if(isset($match['status']) && $match['status'] == 'Finished') echo 'selected'; ?>>Finished</option>
        </select>

        <label for="result">ผลการแข่งขัน (เช่น 2-1, 0-2)</label>
        <input type="text" id="result" name="result" value="<?php echo htmlspecialchars($match['result'] ?? ''); ?>">

        <button type="submit" style="background-color: #007bff;">บันทึกการเปลี่ยนแปลง</button>
    </form>
    <a href="manage_matches.php" class="back-link"> &laquo; กลับไปหน้าจัดการตารางแข่ง</a>
</div>

<?php require_once 'includes/admin_footer.php'; ?>