<?php
// Use the same session name as other files
session_name('website_session');
session_start();

// Clear all session variables
$_SESSION = array();

// Clear any session cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear any additional cookies you might have set
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Ensure no output before header redirect
if (ob_get_length()) {
    ob_end_clean();
}

// Redirect to login page with logout success message
header("Location: login.php?logout=success");
exit();
?>