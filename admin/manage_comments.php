<?php
$active_page = 'comments';
$page_title = 'จัดการคอมเมนต์';
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

// --- จัดการ Action ต่างๆ (Approve, Unapprove, Delete) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $comment_id = $_GET['id'];
    $sql = ''; // กำหนดค่าเริ่มต้นของ $sql

    try {
        if ($action == 'approve') {
            $sql = "UPDATE comments SET status = 'approved' WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $comment_id, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($action == 'unapprove') {
            $sql = "UPDATE comments SET status = 'pending' WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $comment_id, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($action == 'delete') {
            $sql = "DELETE FROM comments WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $comment_id, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // หลังจากทำ action สำเร็จ Redirect เพื่อล้างค่า GET และป้องกันการซ้ำซ้อน
        if (!empty($sql)) { // ตรวจสอบว่ามี SQL query ถูกรันหรือไม่
            header("Location: manage_comments.php");
            exit();
        }

    } catch (PDOException $e) {
        error_log("Database error during comment action (" . $action . "): " . $e->getMessage());
        // คุณอาจจะเพิ่มข้อความ error ให้ผู้ใช้เห็น
        // echo "มีข้อผิดพลาดในการดำเนินการ: " . $e->getMessage();
    }
}

// --- ดึงคอมเมนต์ทั้งหมดมาแสดง (PDO) ---
$comments = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$comments_sql = "
    SELECT c.id, c.content, c.status, c.created_at, u.username, n.title as news_title, n.id as news_id
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN news n ON c.news_id = n.id
    ORDER BY c.created_at DESC
";
try {
    $stmt_comments = $conn->query($comments_sql); // ใช้ query() สำหรับ SELECT ทั้งหมดที่ไม่มี Parameters
    $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมดเป็น array ของ associative array
} catch (PDOException $e) {
    error_log("Database error fetching comments: " . $e->getMessage());
    // คุณอาจจะแสดงข้อความ error หรือแสดง array ว่างเปล่า
}

?>
<style>
    .status-approved { color: #28a745; font-weight: bold; }
    .status-pending { color: #ffc107; font-weight: bold; }
    .comment-content { max-width: 400px; }
    .action-links a { margin-right: 10px; }
    /* เพิ่ม style สำหรับตาราง ถ้ายังไม่มีจาก admin_header */
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>

<h1>จัดการคอมเมนต์</h1>

<table>
    <thead>
        <tr>
            <th>คอมเมนต์</th>
            <th>ผู้เขียน</th>
            <th>ในข่าว:</th>
            <th>สถานะ</th>
            <th>วันที่</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($comments)): // ตรวจสอบว่า $comments ไม่ว่างเปล่า ?>
            <?php foreach($comments as $comment): // ใช้ foreach loop ?>
            <tr>
                <td class="comment-content"><?php echo htmlspecialchars($comment['content'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($comment['username'] ?? ''); ?></td>
                <td><a href="../news_detail.php?id=<?php echo htmlspecialchars($comment['news_id'] ?? ''); ?>" target="_blank"><?php echo htmlspecialchars($comment['news_title'] ?? ''); ?></a></td>
                <td>
                    <?php if (($comment['status'] ?? '') == 'approved'): ?>
                        <span class="status-approved">อนุมัติแล้ว</span>
                    <?php else: ?>
                        <span class="status-pending">รออนุมัติ</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars(date('d M Y', strtotime($comment['created_at'] ?? ''))); ?></td>
                <td class="action-links">
                    <?php if (($comment['status'] ?? '') == 'pending'): ?>
                        <a href="manage_comments.php?action=approve&id=<?php echo htmlspecialchars($comment['id'] ?? ''); ?>">อนุมัติ</a>
                    <?php else: ?>
                        <a href="manage_comments.php?action=unapprove&id=<?php echo htmlspecialchars($comment['id'] ?? ''); ?>">ซ่อน</a>
                    <?php endif; ?>
                    <a href="manage_comments.php?action=delete&id=<?php echo htmlspecialchars($comment['id'] ?? ''); ?>" onclick="return confirm('ยืนยันการลบคอมเมนต์นี้หรือไม่?');" style="color: #dc3545;">ลบ</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align: center;">ยังไม่มีคอมเมนต์ในระบบ</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once 'includes/admin_footer.php'; ?>