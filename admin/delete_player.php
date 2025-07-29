<?php
// /admin/delete_player.php
session_start();
// ตรวจสอบเส้นทางของ db_connect.php ว่าถูกต้อง
require_once '../includes/db_connect.php';

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    // จัดการข้อผิดพลาดถ้าการเชื่อมต่อ DB ล้มเหลว
    die("Database connection failed. Please check ../includes/db_connect.php");
}

// Security check (ตรวจสอบสิทธิ์การเข้าถึง Admin)
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}

// Check if ID is provided
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $player_id = trim($_GET['id']); // เก็บค่าลงในตัวแปรเพื่อความชัดเจน

    $sql = "DELETE FROM players WHERE id = :id"; // ใช้ named placeholder :id
    
    try {
        $stmt = $conn->prepare($sql);
        // ผูกค่าแบบ integer (PDO::PARAM_INT)
        $stmt->bindParam(':id', $player_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            // Redirect to manage players page after successful deletion
            header("location: manage_players.php");
            exit();
        } else {
            // การจัดการข้อผิดพลาดใน PDO จะใช้ try-catch Block แทนการตรวจสอบค่าคืนของ execute()
            // ถ้ามาถึง else ตรงนี้ แสดงว่า execute() คืนค่า false ซึ่งไม่ค่อยเกิดขึ้นใน PDO เมื่อตั้งค่า ERRMODE_EXCEPTION
            // เพราะถ้ามี error มันจะโยน Exception ออกมาทันที
            echo "มีบางอย่างผิดพลาด กรุณาลองใหม่อีกครั้ง";
        }
        
        // PDO ไม่จำเป็นต้องมี $stmt->close();
        
    } catch (PDOException $e) {
        // จัดการข้อผิดพลาดที่อาจเกิดขึ้นระหว่างการลบข้อมูล
        error_log("Error deleting player from database: " . $e->getMessage());
        echo "มีบางอย่างผิดพลาดในการลบข้อมูล: " . $e->getMessage(); // แสดงข้อความ error สำหรับ debug
        // คุณสามารถเพิ่มการแจ้งเตือนผู้ใช้ หรือ redirect ไปหน้า error ได้
    }
} else {
    // Redirect if ID is not provided
    header("location: manage_players.php");
    exit();
}

// PDO ไม่จำเป็นต้องมี $conn->close();
// ถ้าโค้ดมาถึงตรงนี้โดยที่ไม่มี exit() แสดงว่ามี error ที่จัดการแล้ว หรือไม่มี id
// แต่เนื่องจากมี header("location: manage_players.php") ในทั้ง if และ else
// โค้ดด้านล่างนี้จึงไม่น่าจะถูกเรียกใช้
?>