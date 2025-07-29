<?php require_once 'includes/header.php'; ?>

<style>
.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 40px;
    align-items: start;
}
.contact-info, .contact-form {
    background-color: #1f1f1f;
    padding: 30px;
    border-radius: 8px;
}
.contact-info h3, .contact-form h3 {
    margin-top: 0;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}
.contact-info p {
    font-size: 1.1rem;
    line-height: 1.8;
}
.contact-info a {
    color: #00aaff;
    text-decoration: none;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
}
.form-group input, .form-group textarea {
    width: 100%;
    padding: 10px;
    background-color: #333;
    border: 1px solid #555;
    border-radius: 4px;
    color: #fff;
    box-sizing: border-box;
}
.form-group button {
    background-color: #007bff;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
}
.success-message {
    background-color: #28a745;
    color: white;
    padding: 15px;
    border-radius: 5px;
    text-align: center;
}
</style>

<div class="container">
    <h2 class="section-title">ติดต่อเรา</h2>
    <div class="contact-grid">
        <div class="contact-info">
            <h3>ข้อมูลการติดต่อ</h3>
            <p><strong>Email:</strong> <a href="mailto:contact@taeesport.com">contact@taeesport.com</a></p>
            <p><strong>Phone:</strong> <a href="tel:+66812345678">081-234-5678</a></p>
            <p><strong>Discord:</strong> <a href="#" target="_blank">เข้าร่วม Discord Server</a></p>
            <p>สำหรับเรื่อง Sponsorship หรือการร่วมงาน กรุณาติดต่อผ่านทางอีเมล</p>
        </div>
        <div class="contact-form">
            <h3>ส่งข้อความหาเรา</h3>
            <?php
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // PHP สำหรับส่งอีเมลจะอยู่ตรงนี้
                // ในตัวอย่างนี้ เราจะแค่แสดงข้อความขอบคุณ
                echo "<div class='success-message'>ขอบคุณสำหรับข้อความ! เราจะติดต่อกลับโดยเร็วที่สุด</div>";
            } else {
            ?>
            <form action="contact.php" method="post">
                <div class="form-group">
                    <label for="name">ชื่อของคุณ</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">อีเมล</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="subject">หัวข้อ</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="message">ข้อความ</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <button type="submit">ส่งข้อความ</button>
                </div>
            </form>
            <?php } ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>