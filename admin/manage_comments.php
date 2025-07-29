<?php
$active_page = 'comments';
$page_title = 'จัดการคอมเมนต์';
require_once 'includes/admin_header.php';

// --- จัดการ Action ต่างๆ (Approve, Unapprove, Delete) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $comment_id = $_GET['id'];

    if ($action == 'approve') {
        $sql = "UPDATE comments SET status = 'approved' WHERE id = ?";
    } elseif ($action == 'unapprove') {
        $sql = "UPDATE comments SET status = 'pending' WHERE id = ?";
    } elseif ($action == 'delete') {
        $sql = "DELETE FROM comments WHERE id = ?";
    }

    if (isset($sql)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        header("Location: manage_comments.php"); // Redirect เพื่อล้างค่า GET
        exit();
    }
}

// --- ดึงคอมเมนต์ทั้งหมดมาแสดง ---
$comments_sql = "
    SELECT c.id, c.content, c.status, c.created_at, u.username, n.title as news_title, n.id as news_id
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN news n ON c.news_id = n.id
    ORDER BY c.created_at DESC
";
$comments_result = $conn->query($comments_sql);
?>
<style>
    .status-approved { color: #28a745; font-weight: bold; }
    .status-pending { color: #ffc107; font-weight: bold; }
    .comment-content { max-width: 400px; }
    .action-links a { margin-right: 10px; }
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
        <?php if ($comments_result->num_rows > 0): ?>
            <?php while($comment = $comments_result->fetch_assoc()): ?>
            <tr>
                <td class="comment-content"><?php echo htmlspecialchars($comment['content']); ?></td>
                <td><?php echo htmlspecialchars($comment['username']); ?></td>
                <td><a href="../news_detail.php?id=<?php echo $comment['news_id']; ?>" target="_blank"><?php echo htmlspecialchars($comment['news_title']); ?></a></td>
                <td>
                    <?php if ($comment['status'] == 'approved'): ?>
                        <span class="status-approved">อนุมัติแล้ว</span>
                    <?php else: ?>
                        <span class="status-pending">รออนุมัติ</span>
                    <?php endif; ?>
                </td>
                <td><?php echo date('d M Y', strtotime($comment['created_at'])); ?></td>
                <td class="action-links">
                    <?php if ($comment['status'] == 'pending'): ?>
                        <a href="manage_comments.php?action=approve&id=<?php echo $comment['id']; ?>">อนุมัติ</a>
                    <?php else: ?>
                        <a href="manage_comments.php?action=unapprove&id=<?php echo $comment['id']; ?>">ซ่อน</a>
                    <?php endif; ?>
                    <a href="manage_comments.php?action=delete&id=<?php echo $comment['id']; ?>" onclick="return confirm('ยืนยันการลบ?');" style="color: #dc3545;">ลบ</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align: center;">ยังไม่มีคอมเมนต์ในระบบ</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once 'includes/admin_footer.php'; ?>