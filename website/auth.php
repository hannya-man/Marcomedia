<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define public pages that don't require authentication
$public_pages = ['login.php', 'register.php', 'index.php', 'customize.php', 'product.php', 'stock.php'];

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// If user is not logged in and trying to access protected page
if (!$is_logged_in && !in_array($current_page, $public_pages)) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

// If user is logged in and trying to access login/register pages
if ($is_logged_in && in_array($current_page, ['login.php', 'register.php'])) {
    header("Location: index.php");
    exit;
}

// Function to check if user has specific role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Function to require specific role
function requireRole($role) {
    if (!hasRole($role)) {
        header("Location: unauthorized.php");
        exit;
    }
}
?>