<?php require_once 'includes/header.php'; ?>

<style>
    .news-list-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
    }
    .news-list-card {
        background-color: #1f1f1f;
        border-radius: 8px;
        display: flex;
        overflow: hidden;
    }
    .news-list-card img {
        width: 150px;
        height: 100%;
        object-fit: cover;
    }
    .news-list-content {
        padding: 15px;
    }
    .news-list-content h3 {
        margin-top: 0;
    }
    .news-list-content a {
        color: #00aaff;
        text-decoration: none;
    }
    .news-list-content .date {
        font-size: 0.9rem;
        color: #888;
        margin-top: 10px;
    }
</style>

<div class="container">
    <h2 class="section-title">ข่าวสารและประกาศ</h2>
    <div class="news-list-grid">
        <?php
        $sql = "SELECT id, title, image_url, LEFT(content, 100) as summary, created_at FROM news ORDER BY created_at DESC";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $image = !empty($row['image_url']) ? $row['image_url'] : 'https://via.placeholder.com/150.png?text=News';
        ?>
        <div class="news-list-card">
            <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
            <div class="news-list-content">
                <h3><a href="news_detail.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></h3>
                <p><?php echo htmlspecialchars($row['summary']); ?>...</p>
                <div class="date"><?php echo date('d F Y', strtotime($row['created_at'])); ?></div>
            </div>
        </div>
        <?php
            }
        } else {
            echo "<p style='text-align:center;'>ยังไม่มีข่าวสาร</p>";
        }
        ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>