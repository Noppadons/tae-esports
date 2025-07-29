<?php require_once 'includes/header.php'; ?>

<style>
    .matches-container {
        background-color: #1f1f1f;
        padding: 20px;
        border-radius: 8px;
    }
    .match-row {
        display: grid;
        grid-template-columns: 1fr 2fr 1fr;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #333;
    }
    .match-row:last-child {
        border-bottom: none;
    }
    .match-info {
        text-align: center;
    }
    .match-info .game {
        font-size: 1.2rem;
        font-weight: bold;
        color: #007bff;
    }
    .match-details {
        text-align: center;
        font-size: 1.3rem;
    }
    .match-status {
        text-align: center;
    }
    .status-upcoming { color: #ffc107; font-weight: bold; }
    .status-finished { color: #28a745; font-weight: bold; }
</style>
<div class="container">
    <h2 class="section-title">ตารางการแข่งขัน</h2>
    <div class="matches-container">
        <?php
        $sql = "SELECT * FROM matches ORDER BY match_datetime DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
        ?>
        <div class="match-row">
            <div class="match-details">
                <a href="match_detail.php?id=<?php echo $row['id']; ?>">
                    <strong>TAE Esport</strong> vs <strong><?php echo htmlspecialchars($row['opponent_team']); ?></strong>
                </a>
            </div>
            </div>
        <?php } } ?>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>