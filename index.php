<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use unique session name for admin
session_name('admin_session');
session_start();

// Enhanced session debugging (remove in production)
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// Safely get username with fallback - UPDATED FOR ADMIN SESSION
$username = 'Admin'; // Default fallback

// Check admin session variables for username
if (isset($_SESSION['admin_username']) && !empty($_SESSION['admin_username'])) {
    $username = $_SESSION['admin_username'];
} elseif (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    $username = $_SESSION['username'];
} elseif (isset($_SESSION['admin_id'])) {
    // If we have admin_id but no username, fetch from database
    $admin_id = $_SESSION['admin_id'];
    $stmt = $conn->prepare("SELECT username, name FROM users WHERE id = ? AND role = 'admin'");
    if ($stmt) {
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            if (!empty($user_data['username'])) {
                $username = $user_data['username'];
                $_SESSION['admin_username'] = $user_data['username']; // Set it for future use
            } elseif (!empty($user_data['name'])) {
                $username = $user_data['name'];
                $_SESSION['admin_username'] = $user_data['name'];
            }
        }
        $stmt->close();
    }
}

// FIXED: Get dashboard statistics using the same logic as sales.php
function getCount($conn, $query, $field) {
    $res = $conn->query($query);
    if ($res && $row = $res->fetch_assoc()) {
        return $row[$field];
    }
    return 0;
}

// FIXED: Get today's sales from sales table (completed sales only) - USING SALES.PHP LOGIC
$todaySales = getCount($conn, "SELECT IFNULL(SUM(total), 0) AS total FROM sales WHERE DATE(created_at) = CURDATE()", "total");
$totalActiveProducts = getCount($conn, "SELECT COUNT(*) AS total FROM stock WHERE status='active'", "total");

// FIXED: Get pending orders from orders table (not sales)
$pendingOrders = getCount($conn, "SELECT COUNT(*) AS cnt FROM orders WHERE status='Pending'", "cnt");
$pendingAppointments = getCount($conn, "SELECT COUNT(*) AS cnt FROM appointments WHERE status='Pending'", "cnt");
$upcomingAppointments = getCount($conn, "SELECT COUNT(*) AS cnt FROM appointments WHERE date >= CURDATE() AND status='Approved'", "cnt");

// FIXED: Total sales should be today's sales (for the card)
$totalSales = $todaySales;

// FIXED: Get recent orders using EXACT same logic as sales.php
$recentOrders = [];
$orders_query = $conn->query("
    SELECT o.*, 
           COALESCE(o.customer_name, 'Unknown') as display_name,
           COALESCE(o.customer_email, 'No email') as customer_email,
           COALESCE(o.order_number, CONCAT('ORD-', o.id, '-', DATE_FORMAT(o.created_at, '%Y%m%d'))) as reference_id
    FROM orders o 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

if ($orders_query === false) {
    // Fallback if the main query fails
    $orders_query = $conn->query("SELECT *, CONCAT('ORD-', id, '-', DATE_FORMAT(created_at, '%Y%m%d')) as reference_id, COALESCE(customer_name, 'Unknown') as display_name FROM orders ORDER BY created_at DESC LIMIT 5");
}

if ($orders_query && $orders_query->num_rows > 0) {
    $recentOrders = $orders_query->fetch_all(MYSQLI_ASSOC);
} else {
    // If no orders found, show empty state
    $recentOrders = [];
}

// FIXED: Get sales data using EXACT same logic as sales.php - DAILY DATA
$salesData = [];
$chartFilter = 'day'; // Use daily data for dashboard
$orderTypeFilter = 'all'; // Show all sales types

// Build the chart query EXACTLY like in sales.php for daily data
$chartQuery = "SELECT DATE(sale_date) as period, SUM(total) as total FROM sales WHERE 1=1";

if ($orderTypeFilter !== 'all') {
    $chartQuery .= " AND order_type = '$orderTypeFilter'";
}

$chartQuery .= " GROUP BY DATE(sale_date) ORDER BY period DESC LIMIT 30";

$result = $conn->query($chartQuery);
if ($result && $result->num_rows > 0) {
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $data = array_reverse($data);
    
    foreach($data as $row) {
        $salesData[] = [
            'sale_date' => $row['period'],
            'daily_total' => $row['total'],
            'transaction_count' => 1 // Default count
        ];
    }
} else {
    // If no data found, create realistic sample data for demonstration
    $baseDate = date('Y-m-d', strtotime('-29 days'));
    for ($i = 0; $i < 30; $i++) {
        $currentDate = date('Y-m-d', strtotime($baseDate . " +$i days"));
        // Simulate realistic sales patterns (higher on weekends, lower on weekdays)
        $dayOfWeek = date('w', strtotime($currentDate));
        $baseAmount = $dayOfWeek == 0 || $dayOfWeek == 6 ? rand(3000, 8000) : rand(1000, 4000);
        $salesData[] = [
            'sale_date' => $currentDate,
            'daily_total' => $baseAmount,
            'transaction_count' => rand(8, 25)
        ];
    }
}

// FIXED: Get top selling products using EXACT same logic as sales.php
$topProducts = [];
$result = $conn->query("
    SELECT product, SUM(quantity) as total_qty, SUM(total) as total_sales 
    FROM sales 
    GROUP BY product 
    ORDER BY total_sales DESC 
    LIMIT 5
");

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $topProducts[] = [
            'product' => $row['product'],
            'total_sold' => $row['total_qty'],
            'total_revenue' => $row['total_sales']
        ];
    }
} else {
    // Final fallback with sample products
    $sampleProducts = [
        ['product' => 'Professional Camera', 'total_sold' => 45, 'total_revenue' => 67500],
        ['product' => 'Camera Lens', 'total_sold' => 32, 'total_revenue' => 48000],
        ['product' => 'Tripod Stand', 'total_sold' => 28, 'total_revenue' => 14000],
        ['product' => 'Camera Bag', 'total_sold' => 25, 'total_revenue' => 12500],
        ['product' => 'Memory Card', 'total_sold' => 50, 'total_revenue' => 15000]
    ];
    $topProducts = $sampleProducts;
}

// Get available photographers for booking modal
$photographers_result = $conn->query("
    SELECT id, username 
    FROM users 
    WHERE role = 'photographer' 
    AND status = 'active'
");

$photographers = [];
if ($photographers_result && $photographers_result->num_rows > 0) {
    while($photographer = $photographers_result->fetch_assoc()) {
        $photographers[] = $photographer;
    }
}

// Prepare data for sales trend chart - USING EXACT SAME PROCESSING AS SALES.PHP
$salesDates = [];
$salesAmounts = [];
$transactionCounts = [];

foreach ($salesData as $sale) {
    $salesDates[] = date('M j', strtotime($sale['sale_date']));
    $salesAmounts[] = floatval($sale['daily_total']);
    $transactionCounts[] = intval($sale['transaction_count']);
}

// Prepare data for top products chart - FIXED with proper data
$productNames = [];
$productRevenues = [];
$productColors = ['#4361ee', '#7209b7', '#4cc9f0', '#f72585', '#4895ef'];

foreach ($topProducts as $index => $product) {
    $productNames[] = substr($product['product'], 0, 15) . (strlen($product['product']) > 15 ? '...' : '');
    $productRevenues[] = floatval($product['total_revenue']);
}

// FIXED: Calculate sales metrics based on actual sales data USING SALES.PHP LOGIC
$todaySales = 0;
$yesterdaySales = 0;
$weekSales = 0;
$lastWeekSales = 0;

$result = $conn->query("SELECT IFNULL(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = CURDATE()");
if ($result && $row = $result->fetch_assoc()) {
    $todaySales = $row['total'];
}

$result = $conn->query("SELECT IFNULL(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
if ($result && $row = $result->fetch_assoc()) {
    $yesterdaySales = $row['total'];
}

$result = $conn->query("SELECT IFNULL(SUM(total), 0) as total FROM sales WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
if ($result && $row = $result->fetch_assoc()) {
    $weekSales = $row['total'];
}

$result = $conn->query("SELECT IFNULL(SUM(total), 0) as total FROM sales WHERE YEARWEEK(created_at, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)");
if ($result && $row = $result->fetch_assoc()) {
    $lastWeekSales = $row['total'];
}

// Calculate growth percentages
$dailyGrowth = $yesterdaySales > 0 ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100 : ($todaySales > 0 ? 100 : 0);
$weeklyGrowth = $lastWeekSales > 0 ? (($weekSales - $lastWeekSales) / $lastWeekSales) * 100 : ($weekSales > 0 ? 100 : 0);

// Get current time for greeting
$hour = date('H');
if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 18) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}

/* -------------------- ROLE-BASED ACCESS CONTROL -------------------- */

// Function to check user role and permissions - UPDATED FOR ADMIN SESSION
function checkAccess($required_role = null) {
    // Use admin session
    if (session_name() !== 'admin_session') {
        session_name('admin_session');
        session_start();
    }
    
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
    
    // Get user role from session or database
    if (!isset($_SESSION['admin_role'])) {
        global $conn;
        $admin_id = $_SESSION['admin_id'];
        $stmt = $conn->prepare("SELECT role, username FROM users WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            $_SESSION['admin_role'] = $user_data['role'];
            $_SESSION['admin_username'] = $user_data['username']; // Ensure username is set
        } else {
            header('Location: logout.php');
            exit;
        }
    }
    
    // Check if specific role is required
    if ($required_role && $_SESSION['admin_role'] !== $required_role) {
        if ($_SESSION['admin_role'] === 'photographer') {
            header('Location: appointment.php');
        } else {
            header('Location: access_denied.php');
        }
        exit;
    }
    
    return $_SESSION['admin_role'];
}

// Check access for current page and get current role
$current_role = checkAccess();

// Specific page restrictions - add this to each protected page
$current_page = basename($_SERVER['PHP_SELF']);

if ($current_role === 'photographer' && $current_page !== 'appointment.php' && $current_page !== 'logout.php') {
    header('Location: appointment.php');
    exit;
}

// Check for booking message from appointment.php
$booking_message = '';
if (isset($_SESSION['booking_message'])) {
    $booking_message = $_SESSION['booking_message'];
    unset($_SESSION['booking_message']);
}

// Function to get live counts for auto-refresh - FIXED with accurate queries
function getLiveCounts($conn) {
    $pendingOrders = getCount($conn, "SELECT COUNT(*) AS cnt FROM orders WHERE status='Pending'", "cnt");
    $pendingAppointments = getCount($conn, "SELECT COUNT(*) AS cnt FROM appointments WHERE status='Pending'", "cnt");
    $todaySales = getCount($conn, "SELECT IFNULL(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = CURDATE()", "total");
    
    return [
        'pendingOrders' => $pendingOrders,
        'pendingAppointments' => $pendingAppointments,
        'todaySales' => $todaySales
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Marcomedia POS</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* Add the toast notification styles from appointment.php */
    .toast-container {
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
    }

    .toast {
        background: var(--card-bg);
        border-radius: var(--card-radius);
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: var(--shadow);
        border-left: 6px solid;
        display: flex;
        align-items: flex-start;
        gap: 15px;
        transform: translateX(400px);
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        max-width: 380px;
        position: relative;
        overflow: hidden;
    }

    .toast.show {
        transform: translateX(0);
        opacity: 1;
    }

    .toast.hiding {
        transform: translateX(400px);
        opacity: 0;
    }

    .toast::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: currentColor;
        opacity: 0.3;
    }

    .toast-success {
        border-left-color: var(--success);
        color: var(--success);
    }

    .toast-error {
        border-left-color: var(--danger);
        color: var(--danger);
    }

    .toast-warning {
        border-left-color: var(--warning);
        color: var(--warning);
    }

    .toast-info {
        border-left-color: var(--primary);
        color: var(--primary);
    }

    .toast-icon {
        font-size: 1.5rem;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .toast-content {
        flex: 1;
        text-align: left;
    }

    .toast-title {
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 5px;
        color: var(--text);
    }

    .toast-message {
        color: var(--text);
        font-size: 0.95rem;
        line-height: 1.4;
    }

    .toast-close {
        background: none;
        border: none;
        font-size: 1.2rem;
        color: var(--text-muted);
        cursor: pointer;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: var(--transition);
        flex-shrink: 0;
    }

    .toast-close:hover {
        background: rgba(0, 0, 0, 0.1);
        color: var(--text);
    }

    .toast-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        background: currentColor;
        opacity: 0.5;
        width: 100%;
        transform: scaleX(1);
        transform-origin: left;
        transition: transform 5s linear;
    }

    .toast-progress.hiding {
        transform: scaleX(0);
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: var(--card-bg);
        border-radius: var(--card-radius);
        padding: 28px;
        width: 500px;
        max-width: 90%;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .modal-body {
        margin-bottom: 20px;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    /* Form Styles */
    .form-group {
        display: flex;
        flex-direction: column;
        margin-bottom: 20px;
    }

    .form-group label {
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--text);
        font-size: 14px;
    }

    .form-control {
        padding: 12px 16px;
        border: 2px solid var(--border);
        border-radius: 10px;
        background: var(--card-bg);
        color: var(--text);
        font-size: 14px;
        transition: var(--transition);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }

    textarea.form-control {
        min-height: 80px;
        resize: vertical;
    }

    /* Button Styles */
    .btn {
        padding: 12px 20px;
        border-radius: 10px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: var(--shadow);
        font-size: 14px;
    }

    .btn:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-hover);
    }

    .btn-primary {
        background: linear-gradient(to right, var(--primary), var(--primary-dark));
        color: white;
    }

    .btn-primary:hover {
        background: linear-gradient(to right, var(--primary-dark), var(--primary));
    }

    .btn-success {
        background: var(--success);
        color: white;
    }

    .btn-success:hover {
        background: #34a853;
    }

    .btn-danger {
        background: var(--danger);
        color: white;
    }

    .btn-danger:hover {
        background: #e55a5a;
    }

    .btn-warning {
        background: var(--warning);
        color: white;
    }

    .btn-warning:hover {
        background: #e6ac00;
    }

    /* Count update animation */
    .count-update {
        animation: countPulse 1s ease-in-out;
    }

    @keyframes countPulse {
        0% {
            transform: scale(1);
            color: var(--text);
        }
        50% {
            transform: scale(1.1);
            color: var(--primary);
        }
        100% {
            transform: scale(1);
            color: var(--text);
        }
    }

    /* Refresh button animation */
    .action-btn.refreshing .action-icon {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Last refresh indicator */
    .last-refresh {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: var(--card-bg);
        padding: 8px 12px;
        border-radius: 20px;
        font-size: 12px;
        color: var(--text-muted);
        box-shadow: var(--shadow);
        z-index: 1000;
    }

    /* Existing styles remain the same */
    :root {
      --primary: #4361ee;
      --primary-dark: #3a56d4;
      --primary-light: #4cc9f0;
      --secondary: #7209b7;
      --success: #10b981;
      --warning: #f59e0b;
      --danger: #e63946;
      --info: #4895ef;
      --light: #f8f9fa;
      --dark: #212529;
      --gray: #6c757d;
      --gray-light: #adb5bd;
      --sidebar-width: 260px;
      --header-height: 70px;
      --card-radius: 16px;
      --transition: all 0.3s ease;
      --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      --shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.12);
    }

    .dark-mode {
      --bg: #0f1419;
      --card-bg: #1a222d;
      --text: #f1f5f9;
      --text-muted: #94a3b8;
      --sidebar-bg: #0a0f14;
      --border: #2a3341;
      --shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      --shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.3);
    }

    .light-mode {
      --bg: #f1f5f9;
      --card-bg: #ffffff;
      --text: #1e293b;
      --text-muted: #64748b;
      --sidebar-bg: #1e293b;
      --border: #e2e8f0;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: var(--bg);
      color: var(--text);
      transition: var(--transition);
      line-height: 1.6;
      overflow-x: hidden;
    }

    .container {
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar Styles */
    .sidebar {
      width: var(--sidebar-width);
      background: linear-gradient(180deg, var(--sidebar-bg) 0%, #151f2e 100%);
      color: white;
      height: 100vh;
      position: fixed;
      overflow-y: auto;
      transition: var(--transition);
      z-index: 1000;
      box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
    }

    .sidebar-header {
      padding: 24px 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      display: flex;
      align-items: center;
      gap: 12px;
      background: rgba(0, 0, 0, 0.2);
    }

    .logo {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 20px;
      box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    }

    .sidebar-title {
      font-size: 18px;
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .sidebar-menu {
      padding: 20px 0;
    }

    .menu-item {
      display: flex;
      align-items: center;
      padding: 14px 20px;
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      transition: var(--transition);
      gap: 14px;
      margin: 4px 12px;
      border-radius: 10px;
      position: relative;
      overflow: hidden;
    }

    .menu-item:before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
      transition: left 0.5s;
    }

    .menu-item:hover:before {
      left: 100%;
    }

    .menu-item:hover, .menu-item.active {
      background: rgba(255, 255, 255, 0.1);
      color: white;
      transform: translateX(5px);
    }

    .menu-item.active {
      background: linear-gradient(90deg, rgba(67, 97, 238, 0.3) 0%, rgba(67, 97, 238, 0.1) 100%);
      border-left: 4px solid var(--primary);
    }

    .menu-item i {
      width: 20px;
      text-align: center;
      font-size: 18px;
    }

    .sidebar-footer {
      padding: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      margin-top: auto;
    }

    .logout-btn {
      background: linear-gradient(135deg, var(--danger) 0%, #c53030 100%);
      color: white;
      border: none;
      padding: 12px;
      width: 100%;
      border-radius: 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: var(--transition);
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(230, 57, 70, 0.3);
    }

    .logout-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(230, 57, 70, 0.4);
    }

    /* Main Content */
    .main-content {
      flex: 1;
      margin-left: var(--sidebar-width);
      padding: 25px;
      transition: var(--transition);
      background: var(--bg);
    }

    /* Header */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 0 25px 0;
      margin-bottom: 30px;
      position: relative;
    }

    .header:after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--border), transparent);
    }

    .header-title h1 {
      font-size: 32px;
      font-weight: 800;
      margin-bottom: 8px;
      background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      letter-spacing: -0.5px;
    }

    .header-title p {
      color: var(--text-muted);
      font-size: 16px;
      font-weight: 500;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .theme-toggle, .notification-btn, .user-menu, .menu-toggle {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--card-bg);
      color: var(--text);
      cursor: pointer;
      transition: var(--transition);
      border: 1px solid var(--border);
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }

    .theme-toggle:before, .notification-btn:before, .user-menu:before, .menu-toggle:before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(67, 97, 238, 0.1), transparent);
      transition: left 0.5s;
    }

    .theme-toggle:hover:before, .notification-btn:hover:before, .user-menu:hover:before, .menu-toggle:hover:before {
      left: 100%;
    }

    .theme-toggle:hover, .notification-btn:hover, .user-menu:hover, .menu-toggle:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-hover);
    }

    .notification-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background: linear-gradient(135deg, var(--danger) 0%, #c53030 100%);
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      font-size: 11px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      box-shadow: 0 2px 8px rgba(230, 57, 70, 0.4);
    }

    .notification-wrapper {
      position: relative;
    }

    /* Stats Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 24px;
      margin-bottom: 40px;
    }

    .stat-card {
      background: var(--card-bg);
      border-radius: var(--card-radius);
      padding: 28px;
      box-shadow: var(--shadow);
      transition: var(--transition);
      border-left: 4px solid var(--primary);
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }

    .stat-card:before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.5s ease;
    }

    .stat-card:hover:before {
      transform: scaleX(1);
    }

    .stat-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-hover);
    }

    .stat-card.warning {
      border-left-color: var(--warning);
    }

    .stat-card.warning:before {
      background: linear-gradient(90deg, var(--warning) 0%, #fbbf24 100%);
    }

    .stat-card.danger {
      border-left-color: var(--danger);
    }

    .stat-card.danger:before {
      background: linear-gradient(90deg, var(--danger) 0%, #c53030 100%);
    }

    .stat-card.success {
      border-left-color: var(--success);
    }

    .stat-card.success:before {
      background: linear-gradient(90deg, var(--success) 0%, #34d399 100%);
    }

    .stat-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 20px;
    }

    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 26px;
      background: linear-gradient(135deg, rgba(67, 97, 238, 0.1) 0%, rgba(67, 97, 238, 0.05) 100%);
      color: var(--primary);
      box-shadow: 0 4px 12px rgba(67, 97, 238, 0.15);
    }

    .stat-card.warning .stat-icon {
      background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);
      color: var(--warning);
      box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);
    }

    .stat-card.danger .stat-icon {
      background: linear-gradient(135deg, rgba(230, 57, 70, 0.1) 0%, rgba(230, 57, 70, 0.05) 100%);
      color: var(--danger);
      box-shadow: 0 4px 12px rgba(230, 57, 70, 0.15);
    }

    .stat-card.success .stat-icon {
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
      color: var(--success);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
    }

    .stat-value {
      font-size: 32px;
      font-weight: 800;
      margin-bottom: 8px;
      background: linear-gradient(90deg, var(--text) 0%, var(--text-muted) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .stat-label {
      color: var(--text-muted);
      font-size: 15px;
      font-weight: 600;
      letter-spacing: 0.3px;
    }

    .stat-change {
      display: flex;
      align-items: center;
      font-size: 13px;
      margin-top: 12px;
      font-weight: 600;
    }

    .stat-change.positive {
      color: var(--success);
    }

    .stat-change.negative {
      color: var(--danger);
    }

    /* Charts and Tables */
    .content-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 30px;
      margin-bottom: 40px;
    }

    @media (max-width: 1200px) {
      .content-grid {
        grid-template-columns: 1fr;
      }
    }

    .chart-container, .recent-orders, .top-products {
      background: var(--card-bg);
      border-radius: var(--card-radius);
      padding: 28px;
      box-shadow: var(--shadow);
      transition: var(--transition);
    }

    .chart-container:hover, .recent-orders:hover, .top-products:hover {
      box-shadow: var(--shadow-hover);
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }

    .section-title {
      font-size: 20px;
      font-weight: 700;
      position: relative;
      padding-left: 12px;
    }

    .section-title:before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 4px;
      background: linear-gradient(180deg, var(--primary) 0%, var(--primary-light) 100%);
      border-radius: 4px;
    }

    .view-all {
      color: var(--primary);
      text-decoration: none;
      font-size: 14px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: var(--transition);
      padding: 6px 12px;
      border-radius: 8px;
    }

    .view-all:hover {
      background: rgba(67, 97, 238, 0.1);
      transform: translateX(3px);
    }

    .chart-placeholder {
      height: 320px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      margin-top: 15px;
    }

    .recent-orders table, .top-products table {
      width: 100%;
      border-collapse: collapse;
    }

    .recent-orders th, .recent-orders td, .top-products th, .top-products td {
      padding: 14px 16px;
      text-align: left;
      border-bottom: 1px solid var(--border);
    }

    .recent-orders th, .top-products th {
      color: var(--text-muted);
      font-weight: 600;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .recent-orders tr, .top-products tr {
      transition: var(--transition);
    }

    .recent-orders tr:hover, .top-products tr:hover {
      background: rgba(67, 97, 238, 0.03);
    }

    .status-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.3px;
    }

    .status-pending {
      background: rgba(245, 158, 11, 0.1);
      color: var(--warning);
      border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .status-completed {
      background: rgba(16, 185, 129, 0.1);
      color: var(--success);
      border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .status-processing {
      background: rgba(67, 97, 238, 0.1);
      color: var(--primary);
      border: 1px solid rgba(67, 97, 238, 0.2);
    }

    .status-confirmed {
      background: rgba(76, 201, 240, 0.1);
      color: var(--success);
      border: 1px solid rgba(76, 201, 240, 0.2);
    }

    .status-received {
      background: rgba(16, 185, 129, 0.1);
      color: #10b981;
      border: 1px solid rgba(16, 185, 129, 0.2);
    }

    /* Footer */
    .footer {
      text-align: center;
      padding: 24px;
      color: var(--text-muted);
      font-size: 14px;
      border-top: 1px solid var(--border);
      margin-top: 40px;
      font-weight: 500;
    }

    /* Toggle Switch */
    .toggle-switch {
      position: relative;
      display: inline-block;
      width: 54px;
      height: 28px;
    }

    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, var(--gray-light) 0%, var(--gray) 100%);
      transition: .4s;
      border-radius: 28px;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 20px;
      width: 20px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    input:checked + .slider {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    }

    input:checked + .slider:before {
      transform: translateX(26px);
    }

    /* Welcome message with date */
    .welcome-container {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 30px;
    }

    .welcome-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 24px;
      font-weight: 700;
      box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    }

    .welcome-text h2 {
      font-size: 22px;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .welcome-text p {
      color: var(--text-muted);
      font-size: 15px;
    }

    /* Quick Actions */
    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 16px;
      margin-bottom: 30px;
    }

    .action-btn {
      background: var(--card-bg);
      border: none;
      border-radius: 12px;
      padding: 18px 16px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 10px;
      cursor: pointer;
      transition: var(--transition);
      box-shadow: var(--shadow);
      text-decoration: none;
      color: var(--text);
    }

    .action-btn:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-hover);
    }

    .action-icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      color: white;
      box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    }

    .action-text {
      font-weight: 600;
      font-size: 14px;
      text-align: center;
    }

    /* Chart Tabs */
    .chart-tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      border-bottom: 1px solid var(--border);
      padding-bottom: 15px;
    }

    .chart-tab {
      padding: 8px 16px;
      border-radius: 8px;
      background: transparent;
      border: none;
      color: var(--text-muted);
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
    }

    .chart-tab.active {
      background: rgba(67, 97, 238, 0.1);
      color: var(--primary);
    }

    .chart-tab:hover:not(.active) {
      background: rgba(67, 97, 238, 0.05);
    }


    /* Mobile Responsive */
    .menu-toggle {
      display: none;
    }

    @media (max-width: 992px) {
      .sidebar {
        transform: translateX(-100%);
        width: 280px;
      }
      
      .sidebar.active {
        transform: translateX(0);
      }
      
      .main-content {
        margin-left: 0;
        padding: 20px;
      }
      
      .menu-toggle {
        display: flex;
      }
      
      .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
      }
      
      .stat-card {
        padding: 20px;
      }
      
      .stat-value {
        font-size: 24px;
      }
      
      .header-title h1 {
        font-size: 26px;
      }
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 15px;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .quick-actions {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .welcome-container {
        flex-direction: column;
        text-align: center;
        gap: 10px;
      }
      
      .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .header-actions {
        width: 100%;
        justify-content: space-between;
      }
      
      .recent-orders {
        overflow-x: auto;
      }
      
      .recent-orders table {
        min-width: 500px;
      }
    }

    @media (max-width: 480px) {
      .quick-actions {
        grid-template-columns: 1fr;
      }
      
      .chart-container, .recent-orders {
        padding: 18px;
      }
      
      .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
      
      .view-all {
        align-self: flex-end;
      }
    }

    /* Overlay for mobile sidebar */
    .sidebar-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 999;
      display: none;
    }

    .sidebar-overlay.active {
      display: block;
    }
  </style>
</head>
<body class="light-mode">
  <!-- Mobile Sidebar Overlay -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">M</div>
        <div class="sidebar-title">Marcomedia POS</div>
    </div>
    <div class="sidebar-menu">
        <?php if ($current_role === 'admin'): ?>
            <!-- Admin sees all menu items -->
            <a href="index.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span class="menu-text">Dashboard</span>
            </a>
            <a href="sales.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'sales.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span class="menu-text">Sales & Orders</span>
            </a>
            <a href="stock.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'stock.php' ? 'active' : ''; ?>">
                <i class="fas fa-boxes"></i>
                <span class="menu-text">Inventory</span>
            </a>
            <a href="physical_orders.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'physical_orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-store"></i>
                <span class="menu-text">Physical Orders</span>
            </a>
            <a href="appointment.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'appointment.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span class="menu-text">Appointments</span>
            </a>
            <a href="user_management.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'user_management.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span class="menu-text">Staff Management</span>
            </a>
        <?php else: ?>
            <!-- Photographer sees only appointments -->
            <a href="appointment.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'appointment.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span class="menu-text">Appointments</span>
            </a>
        <?php endif; ?>
    </div>
    <div class="sidebar-footer">
        <form action="logout.php" method="POST">
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
    </div>
</div>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Header -->
      <div class="header">
        <div class="header-title">
          <h1>Dashboard Overview</h1>
          <p>Your business at a glance</p>
        </div>
        <div class="header-actions">
          <div class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
          </div>
          <label class="theme-toggle" for="theme-toggle">
            <i class="fas fa-moon"></i>
          </label>
          <input type="checkbox" id="theme-toggle" style="display: none;">
        </div>
      </div>

      <!-- Welcome Section -->
      <div class="welcome-container">
        <div class="welcome-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
        <div class="welcome-text">
          <h2><?php echo $greeting; ?>, <?php echo $username; ?>!</h2>
          <p id="current-date"><?php echo date('l, F j, Y'); ?></p>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <a href="sales.php" class="action-btn">
          <div class="action-icon">
            <i class="fas fa-chart-bar"></i>
          </div>
          <div class="action-text">Sales Report</div>
        </a>
        <a href="stock.php" class="action-btn">
          <div class="action-icon" style="background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);">
            <i class="fas fa-boxes"></i>
          </div>
          <div class="action-text">Manage Inventory</div>
        </a>
        <a href="sales.php" class="action-btn">
          <div class="action-icon" style="background: linear-gradient(135deg, var(--warning) 0%, #fbbf24 100%);">
            <i class="fas fa-clipboard-list"></i>
          </div>
          <div class="action-text">View Orders</div>
        </a>
        <button class="action-btn" onclick="openBookingModal()">
          <div class="action-icon" style="background: linear-gradient(135deg, var(--info) 0%, #3a7bd5 100%);">
            <i class="fas fa-camera"></i>
          </div>
          <div class="action-text">Book Photographer</div>
        </button>
        <!-- Add this refresh button -->
        <button class="action-btn" onclick="manualRefresh()" id="refreshBtn">
          <div class="action-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);">
            <i class="fas fa-sync-alt"></i>
          </div>
          <div class="action-text">Refresh Data</div>
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid">
        <!-- FIXED: Total Sales now shows today's profit -->
        <div class="stat-card" onclick="window.location.href='sales.php'">
          <div class="stat-header">
            <div>
              <div class="stat-value">₱<?php echo number_format($todaySales, 2); ?></div>
              <div class="stat-label">Today's Sales</div>
            </div>
            <div class="stat-icon">
              <i class="fas fa-dollar-sign"></i>
            </div>
          </div>
          <div class="stat-change <?php echo $dailyGrowth >= 0 ? 'positive' : 'negative'; ?>">
            <i class="fas fa-arrow-<?php echo $dailyGrowth >= 0 ? 'up' : 'down'; ?>"></i> 
            <?php echo number_format(abs($dailyGrowth), 1); ?>% from yesterday
          </div>
        </div>

        <div class="stat-card" onclick="window.location.href='stock.php'">
          <div class="stat-header">
            <div>
              <div class="stat-value"><?php echo $totalActiveProducts; ?></div>
              <div class="stat-label">Active Products</div>
            </div>
            <div class="stat-icon">
              <i class="fas fa-box-open"></i>
            </div>
          </div>
          <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> From Inventory
          </div>
        </div>

        <!-- FIXED: Pending Orders now goes to purchase orders tab in sales.php -->
        <div class="stat-card warning" onclick="window.location.href='sales.php?tab=orders&filter=Pending'">
          <div class="stat-header">
            <div>
              <div class="stat-value" id="pendingOrdersCount"><?php echo $pendingOrders; ?></div>
              <div class="stat-label">Pending Orders</div>
            </div>
            <div class="stat-icon">
              <i class="fas fa-clock"></i>
            </div>
          </div>
          <div class="stat-change negative">
            <i class="fas fa-exclamation-circle"></i> Needs attention
          </div>
        </div>

        <!-- FIXED: Pending Appointments has accurate count -->
        <div class="stat-card danger" onclick="window.location.href='appointment.php'">
          <div class="stat-header">
            <div>
              <div class="stat-value" id="pendingAppointmentsCount"><?php echo $pendingAppointments; ?></div>
              <div class="stat-label">Pending Appointments</div>
            </div>
            <div class="stat-icon">
              <i class="fas fa-calendar-times"></i>
            </div>
          </div>
          <div class="stat-change negative">
            <i class="fas fa-exclamation-circle"></i> Needs attention
          </div>
        </div>
      </div>

      <!-- Charts and Recent Orders -->
      <div class="content-grid">
        <div class="chart-container">
          <div class="section-header">
            <div class="section-title">Sales Performance</div>
            <a href="sales.php" class="view-all">View Report <i class="fas fa-arrow-right"></i></a>
          </div>
          <div class="chart-tabs">
            <button class="chart-tab active" data-chart="sales">Sales Trend</button>
            <button class="chart-tab" data-chart="products">Top Products</button>
          </div>
          <div class="chart-placeholder">
            <canvas id="salesChart"></canvas>
          </div>
        </div>

        <!-- FIXED: Recent Orders now uses EXACT same logic as sales.php -->
        <div class="recent-orders">
          <div class="section-header">
            <div class="section-title">Recent Orders</div>
            <a href="sales.php?tab=orders" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
          </div>
          <table>
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($recentOrders)): ?>
                <?php foreach ($recentOrders as $order): ?>
                  <tr>
                    <td>#<?php echo $order['reference_id']; ?></td>
                    <td><?php echo htmlspecialchars($order['display_name']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                    <td>
                      <span class="status-badge 
                        <?php 
                          if ($order['status'] == 'Pending') echo 'status-pending';
                          elseif ($order['status'] == 'Confirmed') echo 'status-confirmed';
                          elseif ($order['status'] == 'Received') echo 'status-received';
                          else echo 'status-processing';
                        ?>">
                        <?php echo $order['status']; ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4" style="text-align: center; padding: 40px;">
                    <i class="fas fa-inbox" style="font-size: 48px; color: var(--text-muted); margin-bottom: 15px; display: block;"></i>
                    <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">No orders found</div>
                    <div style="color: var(--text-muted);">Orders created through checkout will appear here.</div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top Products -->
      <div class="top-products">
        <div class="section-header">
          <div class="section-title">Top Performing Products</div>
          <a href="stock.php" class="view-all">View Inventory <i class="fas fa-arrow-right"></i></a>
        </div>
        <table>
          <thead>
            <tr>
              <th>Product</th>
              <th>Units Sold</th>
              <th>Revenue</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($topProducts)): ?>
              <?php foreach ($topProducts as $index => $product): ?>
                <tr>
                  <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                      <div style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $productColors[$index]; ?>"></div>
                      <?php echo htmlspecialchars($product['product']); ?>
                    </div>
                  </td>
                  <td><?php echo $product['total_sold']; ?></td>
                  <td>₱<?php echo number_format($product['total_revenue'], 2); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="3" style="text-align: center;">No product data available</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Footer -->
      <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> Marcomedia POS. All rights reserved. | Last updated: <span id="last-updated"><?php echo date('H:i:s'); ?></span></p>
      </div>
    </div>
  </div>

  <!-- Photographer Booking Modal -->
  <div id="bookingModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Book a Photographer</h3>
        <button class="btn btn-warning" onclick="closeBookingModal()">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form method="POST" action="appointment.php" id="bookingForm">
        <div class="modal-body">
          <input type="hidden" name="book_photographer" value="1">
          <div class="form-group">
            <label for="customer_name">Customer Name *</label>
            <input type="text" id="customer_name" name="customer_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="event_date">Event Date *</label>
            <input type="date" id="event_date" name="event_date" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="start_time">Start Time *</label>
            <input type="time" id="start_time" name="start_time" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="end_time">End Time *</label>
            <input type="time" id="end_time" name="end_time" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="location">Location *</label>
            <input type="text" id="location" name="location" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="event_type">Event Type</label>
            <select id="event_type" name="event_type" class="form-control">
              <option value="Wedding">Wedding</option>
              <option value="Birthday">Birthday</option>
              <option value="Corporate">Corporate</option>
              <option value="Portrait">Portrait</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Any special requirements or notes..."></textarea>
          </div>
          <div class="form-group">
            <label for="photographer_id">Preferred Photographer (Optional)</label>
            <select id="photographer_id" name="photographer_id" class="form-control">
              <option value="">Auto-assign (Recommended)</option>
              <?php 
              if (!empty($photographers)) {
                  foreach($photographers as $photographer): 
              ?>
                <option value="<?= $photographer['id'] ?>"><?= htmlspecialchars($photographer['username']) ?></option>
              <?php 
                  endforeach;
              } else {
                  echo '<option value="">No photographers available</option>';
              }
              ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-warning" onclick="closeBookingModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-check"></i> Book Photographer
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Toast Notifications Container -->
  <div class="toast-container" id="toastContainer"></div>

  <script>
    // Toast Notification System (from appointment.php)
    function showToast(message, title = '', type = 'info', duration = 5000) {
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) return;
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="${icons[type] || icons.info}"></i>
            </div>
            <div class="toast-content">
                ${title ? `<div class="toast-title">${title}</div>` : ''}
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
            <div class="toast-progress"></div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.classList.add('show');
            const progressBar = toast.querySelector('.toast-progress');
            if (progressBar) {
                progressBar.style.transition = `transform ${duration}ms linear`;
                progressBar.classList.add('hiding');
            }
        }, 100);
        
        // Auto remove after duration
        const autoRemove = setTimeout(() => {
            hideToast(toast);
        }, duration);
        
        // Pause on hover
        toast.addEventListener('mouseenter', () => {
            const progressBar = toast.querySelector('.toast-progress');
            if (progressBar) {
                progressBar.style.transition = 'none';
            }
            clearTimeout(autoRemove);
        });
        
        toast.addEventListener('mouseleave', () => {
            const progressBar = toast.querySelector('.toast-progress');
            const remainingTime = duration - 100; // Approximate remaining time
            
            if (progressBar) {
                progressBar.style.transition = `transform ${remainingTime}ms linear`;
                progressBar.classList.add('hiding');
            }
            
            setTimeout(() => {
                if (toast.parentElement) {
                    hideToast(toast);
                }
            }, remainingTime);
        });
    }

    function hideToast(toast) {
        toast.classList.remove('show');
        toast.classList.add('hiding');
        
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 400);
    }

    // Quick toast functions
    const Toast = {
        success: (message, title = 'Success!') => showToast(message, title, 'success'),
        error: (message, title = 'Error!') => showToast(message, title, 'error'),
        warning: (message, title = 'Warning!') => showToast(message, title, 'warning'),
        info: (message, title = 'Info') => showToast(message, title, 'info')
    };

    // Fixed Theme Toggle Functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Set initial theme
        const savedTheme = localStorage.getItem('theme');
        const themeToggle = document.getElementById('theme-toggle');
        
        if (savedTheme === 'dark') {
            document.body.classList.remove('light-mode');
            document.body.classList.add('dark-mode');
            if (themeToggle) themeToggle.checked = true;
        } else {
            document.body.classList.remove('dark-mode');
            document.body.classList.add('light-mode');
            if (themeToggle) themeToggle.checked = false;
        }

        // Theme toggle event - FIXED VERSION
        if (themeToggle) {
            themeToggle.addEventListener('change', function() {
                if (this.checked) {
                    document.body.classList.remove('light-mode');
                    document.body.classList.add('dark-mode');
                    localStorage.setItem('theme', 'dark');
                    Toast.info('Dark mode enabled', 'Theme');
                } else {
                    document.body.classList.remove('dark-mode');
                    document.body.classList.add('light-mode');
                    localStorage.setItem('theme', 'light');
                    Toast.info('Light mode enabled', 'Theme');
                }
                
                // Reinitialize charts with new theme
                setTimeout(() => {
                    if (window.salesChartInstance) {
                        window.salesChartInstance.destroy();
                    }
                    initializeCharts();
                }, 300);
            });
        }

        // Initialize other components
        initializeCharts();
        updateDateTime();
        setInterval(updateDateTime, 60000);
        setupChartTabs();

        // Show booking message if exists
        <?php if (!empty($booking_message)): ?>
            setTimeout(() => {
                Toast.success(`<?php echo addslashes(strip_tags($booking_message)); ?>`, 'Booking Status');
            }, 1000);
        <?php endif; ?>
    });

    // FIXED: Manual refresh function - now reloads the page for accurate data
    function manualRefresh() {
        const refreshBtn = document.getElementById('refreshBtn');
        const icon = refreshBtn.querySelector('i');
        
        // Add refreshing animation
        refreshBtn.classList.add('refreshing');
        icon.className = 'fas fa-spinner';
        
        Toast.info('Refreshing dashboard data...', 'Refresh');
        
        // Reload the page to get fresh data from server
        setTimeout(() => {
            location.reload();
        }, 1000);
        
        // Remove animation after 2 seconds
        setTimeout(() => {
            refreshBtn.classList.remove('refreshing');
            icon.className = 'fas fa-sync-alt';
        }, 2000);
    }

    // Booking Modal Functions
    function openBookingModal() {
        document.getElementById('bookingModal').style.display = 'flex';
        
        // Set default values
        const now = new Date();
        document.getElementById('event_date').value = now.toISOString().split('T')[0];
        
        // Set start time to next hour
        const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
        document.getElementById('start_time').value = nextHour.toTimeString().substring(0, 5);
        
        // Set end time to 2 hours after start
        const endTime = new Date(nextHour.getTime() + 2 * 60 * 60 * 1000);
        document.getElementById('end_time').value = endTime.toTimeString().substring(0, 5);
        
        Toast.info('Booking form opened', 'Book Photographer');
    }

    function closeBookingModal() {
        document.getElementById('bookingModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('bookingModal');
        if (event.target === modal) {
            closeBookingModal();
        }
    }

    // Mobile sidebar toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (menuToggle && sidebar) {
      menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
      });
      
      sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
      });
    }

    // Update date and time
    function updateDateTime() {
      const now = new Date();
      const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
      const dateString = now.toLocaleDateString('en-US', options);
      document.getElementById('current-date').textContent = dateString;
    }

    // Chart tab functionality
    function setupChartTabs() {
      const tabs = document.querySelectorAll('.chart-tab');
      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          // Remove active class from all tabs
          tabs.forEach(t => t.classList.remove('active'));
          // Add active class to clicked tab
          this.classList.add('active');
          
          // Show the corresponding chart
          const chartType = this.getAttribute('data-chart');
          if (chartType === 'sales') {
            showSalesChart();
          } else if (chartType === 'products') {
            showProductsChart();
          }
        });
      });
    }

    // Show sales chart
    function showSalesChart() {
      if (window.salesChartInstance) {
        window.salesChartInstance.destroy();
      }
      initializeSalesChart();
    }

    // Show products chart
    function showProductsChart() {
      if (window.salesChartInstance) {
        window.salesChartInstance.destroy();
      }
      initializeProductsChart();
    }

    // Initialize Charts - FIXED with proper top products data
    function initializeCharts() {
      // Default to sales chart
      initializeSalesChart();
    }

    // Initialize Sales Chart - FIXED with EXACT same logic as sales.php
    function initializeSalesChart() {
      const ctx = document.getElementById('salesChart').getContext('2d');
      
      // Destroy existing chart if it exists
      if (window.salesChartInstance) {
        window.salesChartInstance.destroy();
      }
      
      const isDarkMode = document.body.classList.contains('dark-mode');
      const textColor = isDarkMode ? '#f1f5f9' : '#1e293b';
      const gridColor = isDarkMode ? '#334155' : '#e2e8f0';
      const primaryColor = '#4361ee';
      
      // Create gradient for better visual appeal
      const gradient = ctx.createLinearGradient(0, 0, 0, 400);
      gradient.addColorStop(0, isDarkMode ? 'rgba(67, 97, 238, 0.4)' : 'rgba(67, 97, 238, 0.3)');
      gradient.addColorStop(1, isDarkMode ? 'rgba(67, 97, 238, 0.1)' : 'rgba(67, 97, 238, 0.05)');
      
      window.salesChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
          labels: <?php echo json_encode($salesDates); ?>,
          datasets: [{
            label: 'Daily Sales (₱)',
            data: <?php echo json_encode($salesAmounts); ?>,
            borderColor: primaryColor,
            backgroundColor: gradient,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: primaryColor,
            pointBorderColor: isDarkMode ? '#1a222d' : '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 8,
            pointHoverBackgroundColor: primaryColor,
            pointHoverBorderColor: '#ffffff',
            pointHoverBorderWidth: 3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: isDarkMode ? '#1e293b' : '#ffffff',
              titleColor: textColor,
              bodyColor: textColor,
              borderColor: isDarkMode ? '#334155' : '#e2e8f0',
              borderWidth: 1,
              padding: 12,
              boxPadding: 6,
              usePointStyle: true,
              callbacks: {
                label: function(context) {
                  return `Sales: ₱${context.raw.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                },
                title: function(tooltipItems) {
                  return `Date: ${tooltipItems[0].label}`;
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: gridColor,
                drawBorder: false,
                drawTicks: false
              },
              ticks: {
                color: textColor,
                padding: 10,
                callback: function(value) {
                  if (value >= 1000) {
                    return '₱' + (value / 1000).toFixed(0) + 'K';
                  }
                  return '₱' + value;
                }
              },
              border: {
                display: false
              }
            },
            x: {
              grid: {
                display: false,
                drawBorder: false
              },
              ticks: {
                color: textColor,
                padding: 10,
                maxRotation: 45
              },
              border: {
                display: false
              }
            }
          },
          interaction: {
            intersect: false,
            mode: 'index'
          },
          animations: {
            tension: {
              duration: 1000,
              easing: 'linear'
            }
          }
        }
      });
    }

    // Initialize Products Chart - FIXED with accurate top products data
    function initializeProductsChart() {
      const ctx = document.getElementById('salesChart').getContext('2d');
      
      if (window.salesChartInstance) {
        window.salesChartInstance.destroy();
      }
      
      const isDarkMode = document.body.classList.contains('dark-mode');
      const textColor = isDarkMode ? '#f1f5f9' : '#1e293b';
      const gridColor = isDarkMode ? '#334155' : '#e2e8f0';
      
      window.salesChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: <?php echo json_encode($productNames); ?>,
          datasets: [{
            label: 'Revenue (₱)',
            data: <?php echo json_encode($productRevenues); ?>,
            backgroundColor: <?php echo json_encode($productColors); ?>,
            borderColor: <?php echo json_encode($productColors); ?>,
            borderWidth: 1,
            borderRadius: 8,
            borderSkipped: false,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: isDarkMode ? '#1e293b' : '#ffffff',
              titleColor: textColor,
              bodyColor: textColor,
              borderColor: isDarkMode ? '#334155' : '#e2e8f0',
              borderWidth: 1,
              callbacks: {
                label: function(context) {
                  return `Revenue: ₱${context.raw.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: gridColor,
                drawBorder: false
              },
              ticks: {
                color: textColor,
                callback: function(value) {
                  return '₱' + value.toLocaleString('en-PH');
                }
              }
            },
            x: {
              grid: {
                display: false,
                drawBorder: false
              },
              ticks: {
                color: textColor,
                maxRotation: 45,
                minRotation: 45
              }
            }
          }
        }
      });
    }
  </script>
</body>
</html>