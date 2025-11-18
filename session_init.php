<?php
// session_init.php
if (session_status() === PHP_SESSION_NONE) {
    // Session configuration for security
    session_name('admin_session');
    
    // Secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); // Use only with HTTPS
    ini_set('session.use_strict_mode', 1);
    
    session_start();
    
    // Regenerate session ID to prevent fixation attacks
    if (empty($_SESSION['created'])) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}
?>