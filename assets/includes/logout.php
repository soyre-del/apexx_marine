<?php
// 1. Initialize the session to find the active one
session_start();

// 2. Unset all active session variables (user_id, role, etc.)
$_SESSION = array();

// 3. Destroy the session cookie on the user's browser for maximum security
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Completely destroy the session on the server
session_destroy();

// 5. Strict No-Cache Headers (Prevents back-button exploits)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// 6. Redirect the user back to the login terminal
header("Location: login.php");
exit();
?>