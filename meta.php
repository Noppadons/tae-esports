<?php
require_once 'includes/header.php';

// ดึงไกด์ทั้งหมดมาแสดง
$sql = "SELECT * FROM meta_guides ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<style>
    .guide-list-item {
        background-color: #1f1f1f;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #007bff;
    }
    .guide-list-item h3 {
        margin-top: 0;
    }
    .guide-list-item h3 a {
        color: #fff;
        text-decoration: none;
    }
    .guide-meta {
        font-size: 0.9rem;
        color: #aaa;
    }
    .game-tag {
        background-color: #007bff;
        color: white;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
        margin-left: 10px;
    }
</style>

<div class="container">
    <h1 class="section-title">แนะนำ Meta</h1>
    
    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="guide-list-item">
                <h3>
                    <a href="meta_detail.php?id=<?php echo $row['id']; ?>">
                        <?php echo htmlspecialchars($row['title']); ?>
                    </a>
                    <span class="game-tag"><?php echo htmlspecialchars($row['game_name']); ?></span>
                </h3>
                <div class="guide-meta">
                    อัปเดตล่าสุด: <?php echo date('d F Y', strtotime($row['updated_at'])); ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center;">ยังไม่มีไกด์แนะนำ Meta ในขณะนี้</p>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>