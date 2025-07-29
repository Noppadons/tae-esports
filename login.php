<?php
// ย้าย header.php มาไว้บนสุดเพื่อเริ่ม session ก่อน (และรวม db_connect.php)
require_once 'includes/header.php';

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    die("Database connection failed. Please check includes/db_connect.php");
}

$email_err = $password_err = $login_err = "";

// ถ้าล็อกอินแล้ว ให้ไปหน้าโปรไฟล์
if (isset($_SESSION["user_id"])) {
    header("location: profile.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? '');
    $password = trim($_POST["password"] ?? '');

    if (empty($email)) { $email_err = "กรุณากรอก Email"; }
    if (empty($password)) { $password_err = "กรุณากรอกรหัสผ่าน"; }

    if (empty($email_err) && empty($password_err)) {
        $sql = "SELECT id, username, password FROM users WHERE email = :email"; // ใช้ named placeholder
        
        try {
            $stmt = $conn->prepare($sql);
            // ผูกค่า Parameter
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC); // ดึงข้อมูลผู้ใช้งานออกมา 1 แถว

            if ($user) { // ถ้าพบ Email
                $hashed_password = $user['password'];
                if (password_verify($password, $hashed_password)) {
                    // Password ถูกต้อง, เก็บข้อมูลลง session variables
                    $_SESSION["user_id"] = $user['id'];
                    $_SESSION["username"] = $user['username'];
                    
                    header("location: profile.php");
                    exit();
                } else {
                    // Password ไม่ถูกต้อง
                    $login_err = "Email หรือรหัสผ่านไม่ถูกต้อง";
                }
            } else {
                // ไม่พบผู้ใช้งานด้วย Email นี้
                $login_err = "ไม่พบผู้ใช้งานด้วย Email นี้";
            }
            
        } catch (PDOException $e) {
            // จัดการข้อผิดพลาดจาก Database
            error_log("Database error during user login: " . $e->getMessage());
            $login_err = "มีบางอย่างผิดพลาด กรุณาลองใหม่";
        }
    }
}
?>
<style>
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
    .login-error-box { background-color: rgba(220, 53, 69, 0.2); border: 1px solid #dc3545; color: #ff8a8a; padding: 15px; border-radius: 4px; margin-bottom:1rem; text-align: center; }
</style>

<div class="container form-page-container">
    <div class="form-wrapper">
        <h2>เข้าสู่ระบบ</h2>
        <?php if(!empty($login_err)){ echo '<div class="login-error-box">' . htmlspecialchars($login_err) . '</div>'; } ?>
        <form action="login.php" method="post" novalidate>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                <span class="error-text"><?php echo htmlspecialchars($email_err); ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password">
                <span class="error-text"><?php echo htmlspecialchars($password_err); ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn-submit" value="ล็อกอิน">
            </div>
            <p class="form-footer-link">ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิกที่นี่</a></p>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>