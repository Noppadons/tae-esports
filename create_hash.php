<?php
// /tae-esport/create_hash.php

// --- ตั้งค่ารหัสผ่านที่คุณต้องการใช้ตรงนี้ ---
$my_password = '12345';
// -----------------------------------------

$hashed_password = password_hash($my_password, PASSWORD_DEFAULT);

echo "<h1>Password Hash Generator</h1>";
echo "<p><strong>Password ที่ต้องการแปลง:</strong> " . htmlspecialchars($my_password) . "</p>";
echo "<p><strong>Hashed Password ที่ได้ (ให้คัดลอกค่านี้ไปใช้):</strong></p>";
echo '<textarea rows="3" style="width: 80%; font-family: monospace;">' . $hashed_password . '</textarea>';

?>