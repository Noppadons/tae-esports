<?php
// /admin/login.php
session_start();
require_once '../includes/db_connect.php';

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    die("Database connection failed. Please check ../includes/db_connect.php");
}

// ถ้า login แล้ว ให้ redirect ไป dashboard เลย
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? ''); // ใช้ ?? '' เพื่อจัดการกรณีที่ค่าไม่ถูกส่งมา
    $password = trim($_POST["password"] ?? '');

    if (empty($username) || empty($password)) {
        $error_message = "กรุณากรอก Username และ Password";
    } else {
        $sql = "SELECT id, username, password FROM admins WHERE username = :username"; // ใช้ named placeholder
        
        try {
            $stmt = $conn->prepare($sql);
            // ผูกค่า Parameter
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            
            $admin = $stmt->fetch(PDO::FETCH_ASSOC); // ดึงข้อมูลผู้ดูแลระบบออกมา 1 แถว

            if ($admin) { // ถ้าพบ Username
                $hashed_password = $admin['password'];
                if (password_verify($password, $hashed_password)) {
                    // Password ถูกต้อง, เริ่ม session (ถ้ายังไม่ได้เริ่ม)
                    // session_start(); // ถ้ามี session_start() ข้างบนแล้ว ก็ไม่ต้องเรียกซ้ำ
                    
                    // เก็บข้อมูลลง session variables
                    $_SESSION["admin_logged_in"] = true;
                    $_SESSION["admin_id"] = $admin['id'];
                    $_SESSION["admin_username"] = $admin['username']; 
                    
                    // Redirect user to dashboard page
                    header("location: dashboard.php");
                    exit();
                } else {
                    // Password ไม่ถูกต้อง
                    $error_message = "Password ที่คุณกรอกไม่ถูกต้อง";
                }
            } else {
                // ไม่พบ Username
                $error_message = "ไม่พบ Username นี้ในระบบ";
            }
            
        } catch (PDOException $e) {
            // จัดการข้อผิดพลาดจาก Database
            error_log("Database error during login: " . $e->getMessage());
            $error_message = "มีบางอย่างผิดพลาด กรุณาลองใหม่อีกครั้ง";
        }
        // PDO ไม่จำเป็นต้องมี $stmt->close() หรือ $conn->close()
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - TAE Esport</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-container { background-color: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 300px; }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; }
        input[type="text"], input[type="password"] { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 0.7rem; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        button:hover { background-color: #0056b3; }
        .error { color: red; text-align: center; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password">
            </div>
            <button type="submit">Login</button>
            <?php if(!empty($error_message)): ?>
                <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>