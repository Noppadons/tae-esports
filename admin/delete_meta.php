<?php
session_start();
require_once '../includes/db_connect.php';
if (!isset($_SESSION["admin_logged_in"])) { header("location: login.php"); exit; }

if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $sql = "DELETE FROM meta_guides WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", trim($_GET['id']));
        $stmt->execute();
    }
}
header("location: manage_meta.php");
exit();
?>