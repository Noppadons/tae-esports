<?php require_once 'includes/header.php'; ?>
<style>
    .sponsor-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 40px;
        align-items: center;
        justify-content: center;
    }
    .sponsor-item {
        background-color: #fff; /* ทำให้โลโก้เด่นบนพื้นหลังสีขาว */
        padding: 20px;
        border-radius: 8px;
        transition: transform 0.3s, box-shadow 0.3s;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 120px;
    }
    .sponsor-item:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.4);
    }
    .sponsor-item img {
        max-width: 100%;
        max-height: 80px;
        object-fit: contain;
        filter: grayscale(100%); /* ทำให้เป็นสีเทา */
        transition: filter 0.3s;
    }
    .sponsor-item:hover img {
        filter: grayscale(0%); /* เมื่อ hover ให้กลับเป็นสีปกติ */
    }
</style>

<div class="container">
    <h1 class="section-title">ผู้สนับสนุนของเรา</h1>
    <p style="text-align:center; max-width:600px; margin: 0 auto 40px auto; color:#aaa;">
        เราขอขอบคุณผู้สนับสนุนทุกท่านที่เชื่อมั่นและร่วมเดินทางไปกับเราสู่ความสำเร็จ
    </p>

    <div class="sponsor-grid">
        <?php
        $sql = "SELECT * FROM sponsors ORDER BY display_order ASC, name ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
        ?>
        <a href="<?php echo htmlspecialchars($row['website_url']); ?>" target="_blank" class="sponsor-item">
            <img src="<?php echo htmlspecialchars($row['logo_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
        </a>
        <?php
            }
        } else {
            echo "<p style='text-align:center; grid-column: 1 / -1;'>ยังไม่มีผู้สนับสนุนอย่างเป็นทางการ</p>";
        }
        ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>