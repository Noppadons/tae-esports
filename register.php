<?php
// ย้าย header.php มาไว้บนสุดเพื่อเริ่ม session ก่อน (และรวม db_connect.php)
require_once 'includes/header.php';

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    die("Database connection failed. Please check includes/db_connect.php");
}

$username_err = $email_err = $password_err = "";

// ถ้าล็อกอินแล้ว ให้ไปหน้าโปรไฟล์
if (isset($_SESSION["user_id"])) {
    header("location: profile.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = trim($_POST["password"] ?? '');

    // Validate username
    if (empty($username)) {
        $username_err = "กรุณากรอก Username";
    } else {
        $sql_check_username = "SELECT id FROM users WHERE username = :username";
        try {
            $stmt_check_username = $conn->prepare($sql_check_username);
            $stmt_check_username->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt_check_username->execute();
            if ($stmt_check_username->rowCount() == 1) { // ใช้ rowCount() สำหรับ PDO
                $username_err = "Username นี้มีผู้ใช้งานแล้ว";
            }
        } catch (PDOException $e) {
            error_log("Database error checking username: " . $e->getMessage());
            $username_err = "มีบางอย่างผิดพลาดในการตรวจสอบ Username";
        }
    }

    // Validate email
    if (empty($email)) {
        $email_err = "กรุณากรอก Email";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // เพิ่มการตรวจสอบรูปแบบ email
        $email_err = "รูปแบบ Email ไม่ถูกต้อง";
    } else {
        $sql_check_email = "SELECT id FROM users WHERE email = :email";
        try {
            $stmt_check_email = $conn->prepare($sql_check_email);
            $stmt_check_email->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt_check_email->execute();
            if ($stmt_check_email->rowCount() == 1) { // ใช้ rowCount() สำหรับ PDO
                $email_err = "Email นี้มีผู้ใช้งานแล้ว";
            }
        } catch (PDOException $e) {
            error_log("Database error checking email: " . $e->getMessage());
            $email_err = "มีบางอย่างผิดพลาดในการตรวจสอบ Email";
        }
    }

    // Validate password
    if (empty($password)) {
        $password_err = "กรุณากรอกรหัสผ่าน";
    } elseif (strlen($password) < 6) { // ไม่ต้อง trim เพราะเช็คใน empty ไปแล้ว
        $password_err = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
    }

    // Check input errors before inserting
    if (empty($username_err) && empty($email_err) && empty($password_err)) {
        $sql_insert_user = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        try {
            $stmt_insert_user = $conn->prepare($sql_insert_user);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT); // ใช้ $password ที่ trim แล้ว
            
            $stmt_insert_user->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt_insert_user->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt_insert_user->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            
            if ($stmt_insert_user->execute()) {
                // ดึง ID ของผู้ใช้ที่เพิ่ง insert เข้าไป
                $_SESSION["user_id"] = $conn->lastInsertId(); // ใช้ lastInsertId() สำหรับ PDO
                $_SESSION["username"] = $username;
                
                header("location: profile.php");
                exit();
            } else {
                // PDO จะโยน Exception ถ้ามี Error, ส่วนนี้อาจไม่ถูกเรียกถ้าตั้งค่า ERRMODE_EXCEPTION
                error_log("Failed to insert new user into database.");
                echo "มีบางอย่างผิดพลาด กรุณาลองใหม่";
            }
        } catch (PDOException $e) {
            error_log("Database error inserting new user: " . $e->getMessage());
            echo "มีบางอย่างผิดพลาดในการสมัครสมาชิก: " . $e->getMessage();
        }
    }
}
?>
<style>
    /* ใช้ CSS ชุดเดียวกับหน้า login */
    .form-page-container { display: flex; justify-content: center; align-items: center; padding: 60px 0; }
    .form-wrapper { width: 100%; max-width: 450px; background-color: #1f1f1f; padding: 40px; border-radius: 8px; border-top: 4px solid #007bff; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    .form-wrapper h2 { text-align: center; font-size: 2rem; margin-top: 0; margin-bottom: 30px; }
    .form-group { margin-bottom: 1.2rem; }
    .form-group label { display: block; margin-bottom: .5rem; color: #aaa; }
    .form-group input { width: 100%; padding: .8rem; background-color: #2c2c2c; border: 1px solid #555; border-radius: 4px; color: #fff; box-sizing: border-box; }
    .form-group .error-text { color: #ff4d4d; font-size: 0.9rem; display: block; margin-top: 5px; }
    .btn-submit { width: 100%; background-color: #007bff; color: white; padding: 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 1.1rem; font-weight: bold; transition: background-color 0.3s; }
    .btn-submit:hover { background-color: #0056b3; }
    .form-footer-link { text-align: center; margin-top: 20px; }
    .form-footer-link a { color: #00aaff; }
</style>

<div class="container form-page-container">
    <div class="form-wrapper">
        <h2 class="section-title">สมัครสมาชิกแฟนคลับ</h2>
        <form action="register.php" method="post" novalidate>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>">
                <span class="error-text"><?php echo htmlspecialchars($username_err); ?></span>
            </div>    
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                <span class="error-text"><?php echo htmlspecialchars($email_err); ?></span>
            </div>
            <div class="form-group">
                <label>Password (อย่างน้อย 6 ตัวอักษร)</label>
                <input type="password" name="password">
                <span class="error-text"><?php echo htmlspecialchars($password_err); ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn-submit" value="สมัครสมาชิก">
            </div>
            <p class="form-footer-link">มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบที่นี่</a></p>
        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>