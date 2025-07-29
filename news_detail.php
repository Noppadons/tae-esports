<?php
require_once 'includes/header.php';

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว (มาจาก includes/header.php -> db_connect.php)
if (!isset($conn) || !$conn instanceof PDO) {
    echo "<p style='text-align:center; color:red;'>ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง</p>";
    exit;
}

if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("Location: news.php");
    exit;
}
$news_id = trim($_GET['id']);

// --- โค้ดสำหรับรับคอมเมนต์ใหม่ (POST Logic) (PDO) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_comment'])) {
    if (isset($_SESSION['user_id'])) {
        $comment_content = trim($_POST['content'] ?? '');
        $user_id = $_SESSION['user_id'];
        
        if (!empty($comment_content)) {
            $sql_insert = "INSERT INTO comments (news_id, user_id, content) VALUES (:news_id, :user_id, :content)";
            try {
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bindParam(':news_id', $news_id, PDO::PARAM_INT);
                $stmt_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_insert->bindParam(':content', $comment_content, PDO::PARAM_STR);
                $stmt_insert->execute();
                
                header("Location: news_detail.php?id=" . htmlspecialchars($news_id) . "#comments-section");
                exit();
            } catch (PDOException $e) {
                error_log("Database error inserting comment: " . $e->getMessage());
                // คุณอาจจะแสดงข้อความ error ให้ผู้ใช้เห็น
                // echo "<p style='color:red;'>มีข้อผิดพลาดในการส่งความคิดเห็น: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        // หากผู้ใช้ไม่ได้ login แต่พยายามส่งคอมเมนต์ (ผ่านการปรับ URL)
        header("Location: login.php?redirect=" . urlencode("news_detail.php?id=" . $news_id));
        exit();
    }
}

// --- ดึงข้อมูลข่าว (PDO) ---
$news = null; // กำหนดค่าเริ่มต้น
$sql_select_news = "SELECT title, content, image_url, created_at FROM news WHERE id = :id";
try {
    $stmt_news = $conn->prepare($sql_select_news);
    $stmt_news->bindParam(':id', $news_id, PDO::PARAM_INT);
    $stmt_news->execute();
    $news = $stmt_news->fetch(PDO::FETCH_ASSOC);

    if (!$news) {
        header("Location: news.php"); // ถ้าไม่พบข่าว
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error fetching news details for public page: " . $e->getMessage());
    die("<p style='text-align:center; color:red;'>ไม่สามารถโหลดข่าวได้ในขณะนี้</p>");
}

// --- ดึงคอมเมนต์มาแสดง (PDO) ---
$comments = []; // กำหนดค่าเริ่มต้นเป็น array ว่าง
$comments_sql = "
    SELECT c.content, c.created_at, u.username, u.avatar_url 
    FROM comments c JOIN users u ON c.user_id = u.id
    WHERE c.news_id = :news_id AND c.status = 'approved'
    ORDER BY c.created_at DESC";
try {
    $stmt_comments = $conn->prepare($comments_sql);
    $stmt_comments->bindParam(':news_id', $news_id, PDO::PARAM_INT);
    $stmt_comments->execute();
    $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลทั้งหมด
} catch (PDOException $e) {
    error_log("Database error fetching comments for news_detail: " . $e->getMessage());
    // ไม่ถึงกับ fatal error แค่ไม่แสดงคอมเมนต์
    echo "<p style='text-align:center; color:red;'>ไม่สามารถโหลดความคิดเห็นได้ในขณะนี้</p>";
}
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
        <h1><?php echo htmlspecialchars($news['title'] ?? 'ไม่พบหัวข้อ'); ?></h1>
        <div class="news-detail-meta">
            เผยแพร่เมื่อ: <?php echo htmlspecialchars(date('d F Y', strtotime($news['created_at'] ?? ''))); ?>
        </div>
        <?php if (!empty($news['image_url'])): ?>
            <img src="../<?php echo htmlspecialchars($news['image_url']); ?>" alt="<?php echo htmlspecialchars($news['title'] ?? ''); ?>" class="news-detail-image">
        <?php endif; ?>
        
        <div class="news-detail-content">
            <?php echo $news['content'] ?? ''; // เนื้อหาจาก TinyMCE ควรแสดงผลเป็น HTML ?>
        </div>

        <div id="comments-section" class="comments-section">
            <h2>ความคิดเห็น (<?php echo count($comments); ?>)</h2>
            
            <div class="comment-form">
                <?php if (isset($_SESSION["user_id"])): ?>
                    <form action="news_detail.php?id=<?php echo htmlspecialchars($news_id); ?>" method="post">
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

            <?php if (!empty($comments)): ?>
                <?php foreach($comments as $comment): ?>
                <div class="comment">
                    <div class="comment-avatar">
                        <img src="<?php echo !empty($comment['avatar_url']) ? htmlspecialchars($comment['avatar_url']) : 'assets/img/default_avatar.png'; ?>" alt="<?php echo htmlspecialchars($comment['username'] ?? 'User'); ?> Avatar">
                    </div>
                    <div class="comment-body">
                        <div class="comment-author"><?php echo htmlspecialchars($comment['username'] ?? 'Unknown User'); ?></div>
                        <div class="comment-date"><?php echo htmlspecialchars(date('d F Y, H:i', strtotime($comment['created_at'] ?? ''))); ?></div>
                        <div class="comment-content">
                            <p><?php echo nl2br(htmlspecialchars($comment['content'] ?? '')); ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center;">ยังไม่มีความคิดเห็น</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>