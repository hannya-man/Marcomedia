<?php
session_start();

// Check if user is logged in and redirect based on role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // If admin is on client pages, redirect to admin dashboard
    if ($_SESSION['role'] === 'admin' && $current_page === 'index.php' && !isset($_GET['admin'])) {
        header("Location: admin/index.php");
        exit();
    }
    
    // If regular user is on admin pages, redirect to client index
    if ($_SESSION['role'] !== 'admin' && strpos($_SERVER['PHP_SELF'], 'admin/') !== false) {
        header("Location: ../index.php");
        exit();
    }
}
?>