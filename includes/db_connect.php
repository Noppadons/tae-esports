<?php

// ดึงค่า Environment Variables
// ใช้ getenv() เพื่อดึงค่าจาก Render
// ใช้ค่า default สำหรับการทดสอบบน Local (ถ้าคุณยังรัน Localhost ด้วย PostgreSQL ไม่ได้)
$db_host = getenv('DB_HOST') ?: 'dpg-d242ekruibrs73a5g9bg-a'; // บน Local อาจจะต้องเปลี่ยนเป็น Host ของ PostgreSQL ที่คุณใช้ (ถ้ามี)
$db_port = getenv('DB_PORT') ?: '5432';
$db_name = getenv('DB_NAME') ?: 'tae_esport_db'; // ใช้ชื่อ DB ที่คุณสร้างบน Render
$db_user = getenv('DB_USER') ?: 'tae_esport_db_user'; // ใช้ Username ของ PostgreSQL บน Local (ถ้ามี)
$db_pass = getenv('DB_PASS') ?: 'AFUoegp9lweqjjTjL3w561fzNuFqaUmj'; // ใช้ Password ของ PostgreSQL บน Local (ถ้ามี)

try {
    // สร้าง DSN (Data Source Name) สำหรับ PDO PostgreSQL
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";

    // สร้างการเชื่อมต่อ PDO
    $conn = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // ตั้งค่าให้ PDO โยน Exception เมื่อเกิด Error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // ตั้งค่าให้ดึงข้อมูลเป็น Associative Array โดย Default
    ]);

    // ถ้าเชื่อมต่อสำเร็จ ไม่ต้องแสดงอะไร
    // echo "Connected to PostgreSQL successfully!"; // สำหรับทดสอบเท่านั้น ลบออกเมื่อใช้งานจริง

} catch (PDOException $e) {
    // ถ้าเชื่อมต่อไม่สำเร็จ ให้แสดง Error
    die("Connection failed: " . $e->getMessage());
}

// **สำคัญมาก:** ถ้าโค้ดส่วนอื่นของคุณใช้ $conn->query() หรือ $conn->prepare()
// และใช้ fetch_assoc() หรือ fetch_array()
// คุณอาจจะต้องปรับเปลี่ยนเล็กน้อยเพื่อให้เข้ากับ PDO
// ตัวอย่าง:
// MySQLi: $result = $conn->query("SELECT * FROM users");
//         while($row = $result->fetch_assoc()) { ... }
// PDO:    $stmt = $conn->query("SELECT * FROM users");
//         while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ... }
// หรือ
// PDO:    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
//         $stmt->bindParam(':id', $id);
//         $stmt->execute();
//         $row = $stmt->fetch(PDO::FETCH_ASSOC);
?>