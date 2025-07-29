<?php
// ย้าย header.php มาไว้บนสุดเพื่อเริ่ม session ก่อน
require_once 'includes/header.php';

$username_err = $email_err = $password_err = "";

if (isset($_SESSION["user_id"])) {
    header("location: profile.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) { $username_err = "กรุณากรอก Username";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("s", $_POST["username"]);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 1) { $username_err = "Username นี้มีผู้ใช้งานแล้ว"; }
        }
    }
    // Validate email
    if (empty(trim($_POST["email"]))) { $email_err = "กรุณากรอก Email";
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("s", $_POST["email"]);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 1) { $email_err = "Email นี้มีผู้ใช้งานแล้ว"; }
        }
    }
    // Validate password
    if (empty(trim($_POST["password"]))) { $password_err = "กรุณากรอกรหัสผ่าน";
    } elseif (strlen(trim($_POST["password"])) < 6) { $password_err = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร"; }

    // Check input errors before inserting
    if (empty($username_err) && empty($email_err) && empty($password_err)) {
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $hashed_password = password_hash($_POST["password"], PASSWORD_DEFAULT);
            $stmt->bind_param("sss", $_POST["username"], $_POST["email"], $hashed_password);
            if ($stmt->execute()) {
                $_SESSION["user_id"] = $stmt->insert_id;
                $_SESSION["username"] = $_POST["username"];
                header("location: profile.php");
            } else { echo "มีบางอย่างผิดพลาด กรุณาลองใหม่"; }
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
                <input type="text" name="username" value="<?php echo !empty($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <span class="error-text"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo !empty($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <span class="error-text"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <label>Password (อย่างน้อย 6 ตัวอักษร)</label>
                <input type="password" name="password">
                <span class="error-text"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn-submit" value="สมัครสมาชิก">
            </div>
            <p class="form-footer-link">มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบที่นี่</a></p>
        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>