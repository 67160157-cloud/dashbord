<?php
// logout.php
// ล้าง session และ redirect ไปหน้า login

session_start();

// ลบตัวแปร session ทั้งหมด
$_SESSION = array();

// ลบ session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ทำลาย session
session_destroy();

// redirect ไปหน้า login หรือ index
header("Location: login.php");
exit;
?>