<?php
require_once 'includes/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: news.php");
    exit;
}
$news_id = $_GET['id'];

// --- โค้ดสำหรับรับคอมเมนต์ใหม่ (POST Logic) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_comment'])) {
    if (isset($_SESSION['user_id'])) {
        $comment_content = trim($_POST['content']);
        $user_id = $_SESSION['user_id'];
        if (!empty($comment_content)) {
            $sql_insert = "INSERT INTO comments (news_id, user_id, content) VALUES (?, ?, ?)";
            if ($stmt_insert = $conn->prepare($sql_insert)) {
                $stmt_insert->bind_param("iis", $news_id, $user_id, $comment_content);
                $stmt_insert->execute();
                header("Location: news_detail.php?id=" . $news_id . "#comments-section");
                exit();
            }
        }
    }
}

// --- ดึงข้อมูลข่าว ---
$sql = "SELECT title, content, image_url, created_at FROM news WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $news_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) { header("Location: news.php"); exit; }
$news = $result->fetch_assoc();

// --- ดึงคอมเมนต์มาแสดง ---
$comments_sql = "
    SELECT c.content, c.created_at, u.username, u.avatar_url 
    FROM comments c JOIN users u ON c.user_id = u.id
    WHERE c.news_id = ? AND c.status = 'approved'
    ORDER BY c.created_at DESC";
$stmt_comments = $conn->prepare($comments_sql);
$stmt_comments->bind_param("i", $news_id);
$stmt_comments->execute();
$comments_result = $stmt_comments->get_result();
?>
<style>
    /* CSS เดิมของ news_detail_container */
    .news-detail-container { max-width: 800px; margin: 40px auto; background-color: #1f1f1f; padding: 30px; border-radius: 8px; }
    .news-detail-container h1 { margin-top: 0; }
    .news-detail-meta { color: #aaa; margin-bottom: 20px; }
    .news-detail-image { width: 100%; max-height: 400px; object-fit: cover; border-radius: 8px; margin-bottom: 20px; }
    .news-detail-content { line-height: 1.8; }
    .news-detail-content h1, .news-detail-content h2, .news-detail-content h3 { color: #fff; }
    .news-detail-content a { color: #00aaff; }
    .news-detail-content ul, .news-detail-content ol { padding-left: 20px; }

    /* --- CSS ใหม่สำหรับระบบคอมเมนต์ (ปรับปรุง) --- */
    .comments-section {
        margin-top: 50px; /* เพิ่มระยะห่างจากเนื้อหาข่าว */
        border-top: 1px solid #444; /* เพิ่มเส้นคั่น */
        padding-top: 30px;
    }
    .comment { display: flex; gap: 15px; margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid #333; }
    .comment:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .comment-avatar img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
    .comment-body { flex: 1; }
    .comment-author { font-weight: bold; color: #00aaff; margin-bottom: 5px; }
    .comment-date { font-size: 0.8rem; color: #888; }
    .comment-content { margin-top: 10px; line-height: 1.7; }
    .comment-form textarea { width: 100%; height: 100px; background-color: #2c2c2c; border: 1px solid #555; border-radius: 4px; color: #fff; padding: 10px; box-sizing: border-box; }
    .comment-form button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 10px; }
    .login-prompt { text-align: center; padding: 20px; background-color: #2c2c2c; border-radius: 4px; }
</style>

<div class="container">
    <div class="news-detail-container">
        <h1><?php echo htmlspecialchars($news['title']); ?></h1>
        <div class="news-detail-meta">
            เผยแพร่เมื่อ: <?php echo date('d F Y', strtotime($news['created_at'])); ?>
        </div>
        <?php if (!empty($news['image_url'])): ?>
            <img src="<?php echo htmlspecialchars($news['image_url']); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" class="news-detail-image">
        <?php endif; ?>
        
        <div class="news-detail-content">
            <?php echo $news['content']; ?>
        </div>

        <div id="comments-section" class="comments-section">
            <h2>ความคิดเห็น (<?php echo $comments_result->num_rows; ?>)</h2>
            
            <div class="comment-form">
                <?php if (isset($_SESSION["user_id"])): ?>
                    <form action="news_detail.php?id=<?php echo $news_id; ?>" method="post">
                        <textarea name="content" placeholder="แสดงความคิดเห็นของคุณ..." required></textarea>
                        <button type="submit" name="submit_comment">ส่งความคิดเห็น</button>
                    </form>
                <?php else: ?>
                    <div class="login-prompt">
                        กรุณา <a href="login.php">เข้าสู่ระบบ</a> หรือ <a href="register.php">สมัครสมาชิก</a> เพื่อแสดงความคิดเห็น
                    </div>
                <?php endif; ?>
            </div>
            <hr style="border-color: #333; margin-top: 30px; margin-bottom: 30px;">

            <?php if ($comments_result->num_rows > 0): ?>
                <?php while($comment = $comments_result->fetch_assoc()): ?>
                <div class="comment">
                    <div class="comment-avatar">
                        <img src="<?php echo !empty($comment['avatar_url']) ? htmlspecialchars($comment['avatar_url']) : 'assets/img/default_avatar.png'; ?>" alt="avatar">
                    </div>
                    <div class="comment-body">
                        <div class="comment-author"><?php echo htmlspecialchars($comment['username']); ?></div>
                        <div class="comment-date"><?php echo date('d F Y, H:i', strtotime($comment['created_at'])); ?></div>
                        <div class="comment-content">
                            <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center;">ยังไม่มีความคิดเห็น</p>
            <?php endif; ?>
        </div>
        </div> </div>

<?php require_once 'includes/footer.php'; ?>