<?php
// /admin/login.php
session_start();
require_once '../includes/db_connect.php';

// ถ้า login แล้ว ให้ redirect ไป dashboard เลย
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["username"])) || empty(trim($_POST["password"]))) {
        $error_message = "กรุณากรอก Username และ Password";
    } else {
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);

        $sql = "SELECT id, username, password FROM admins WHERE username = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $username);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $username, $hashed_password);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["admin_logged_in"] = true;
                            $_SESSION["admin_id"] = $id;
                            $_SESSION["admin_username"] = $username;                            
                            
                            // Redirect user to dashboard page
                            header("location: dashboard.php");
                        } else {
                            // Display an error message if password is not valid
                            $error_message = "Password ที่คุณกรอกไม่ถูกต้อง";
                        }
                    }
                } else {
                    // Display an error message if username doesn't exist
                    $error_message = "ไม่พบ Username นี้ในระบบ";
                }
            } else {
                $error_message = "มีบางอย่างผิดพลาด กรุณาลองใหม่อีกครั้ง";
            }
            $stmt->close();
        }
    }
    $conn->close();
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
                <p class="error"><?php echo $error_message; ?></p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>