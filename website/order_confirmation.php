<?php
session_start();
require __DIR__ . '/../db.php';

// Initialize variables
$order = null;
$order_items = [];
$total = 0;
$order_number = '';

// Debug: Check what's available
error_log("SESSION last_order_id: " . ($_SESSION['last_order_id'] ?? 'NOT SET'));
error_log("GET order_id: " . ($_GET['order_id'] ?? 'NOT SET'));
error_log("GET order_number: " . ($_GET['order_number'] ?? 'NOT SET'));

// Check if we have an order ID from session or URL
if (isset($_SESSION['last_order_id']) && !empty($_SESSION['last_order_id'])) {
    $order_id = intval($_SESSION['last_order_id']);
    error_log("Trying to fetch order by ID from session: " . $order_id);
    
    // Fetch order details
    $order_stmt = $conn->prepare("
        SELECT o.*, u.fullname, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    
    if ($order_stmt) {
        $order_stmt->bind_param("i", $order_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        $order = $order_result->fetch_assoc();
        $order_stmt->close();
        
        if ($order) {
            error_log("Order found in database: " . $order['order_number']);
            $order_number = $order['order_number'] ?? '';
            
            // Fetch order items
            $items_stmt = $conn->prepare("
                SELECT oi.*, s.name, s.price, s.image_path 
                FROM order_items oi 
                LEFT JOIN stock s ON oi.product_id = s.id 
                WHERE oi.order_id = ?
            ");
            
            if ($items_stmt) {
                $items_stmt->bind_param("i", $order_id);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();
                
                while ($item = $items_result->fetch_assoc()) {
                    $order_items[] = $item;
                    $total += ($item['price'] * $item['quantity']);
                }
                $items_stmt->close();
                error_log("Found " . count($order_items) . " order items");
            } else {
                error_log("Failed to prepare items statement");
            }
        } else {
            error_log("No order found with ID: " . $order_id);
        }
    } else {
        error_log("Failed to prepare order statement");
    }
} elseif (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    error_log("Trying to fetch order by ID from URL: " . $order_id);
    
    // Same logic as above to fetch by order_id from URL
    $order_stmt = $conn->prepare("
        SELECT o.*, u.fullname, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    
    if ($order_stmt) {
        $order_stmt->bind_param("i", $order_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        $order = $order_result->fetch_assoc();
        $order_stmt->close();
        
        if ($order) {
            $order_number = $order['order_number'] ?? '';
            
            // Fetch order items
            $items_stmt = $conn->prepare("
                SELECT oi.*, s.name, s.price, s.image_path 
                FROM order_items oi 
                LEFT JOIN stock s ON oi.product_id = s.id 
                WHERE oi.order_id = ?
            ");
            
            if ($items_stmt) {
                $items_stmt->bind_param("i", $order_id);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();
                
                while ($item = $items_result->fetch_assoc()) {
                    $order_items[] = $item;
                    $total += ($item['price'] * $item['quantity']);
                }
                $items_stmt->close();
            }
        }
    }
}

// If we still don't have an order but have order_number in URL, try to fetch by order_number
if (!$order && isset($_GET['order_number']) && !empty($_GET['order_number'])) {
    $order_number = $_GET['order_number'];
    error_log("Trying to fetch order by order_number: " . $order_number);
    
    $order_stmt = $conn->prepare("
        SELECT o.*, u.fullname, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.order_number = ?
    ");
    
    if ($order_stmt) {
        $order_stmt->bind_param("s", $order_number);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        $order = $order_result->fetch_assoc();
        $order_stmt->close();
        
        if ($order) {
            // Fetch order items
            $items_stmt = $conn->prepare("
                SELECT oi.*, s.name, s.price, s.image_path 
                FROM order_items oi 
                LEFT JOIN stock s ON oi.product_id = s.id 
                WHERE oi.order_id = ?
            ");
            
            if ($items_stmt) {
                $items_stmt->bind_param("i", $order['id']);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();
                
                while ($item = $items_result->fetch_assoc()) {
                    $order_items[] = $item;
                    $total += ($item['price'] * $item['quantity']);
                }
                $items_stmt->close();
            }
        }
    }
}

// Check if order_number is set but order is null (for display purposes)
if (isset($_GET['order_number']) && empty($order)) {
    $order_number = htmlspecialchars($_GET['order_number']);
}

error_log("Final result - Order: " . ($order ? 'FOUND' : 'NOT FOUND') . ", Order Number: " . $order_number);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Marcomedia POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles here */
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --dark-text-color: #1f2937;
            --light-text-color: #6b7280;
            --background-light: #f8fafc;
            --card-bg: #ffffff;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-accent: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --border-radius: 16px;
            --border-radius-lg: 24px;
            --shadow: 0 20px 40px rgba(0,0,0,0.1);
            --shadow-lg: 0 30px 60px rgba(0,0,0,0.15);
            --transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.7;
            color: var(--dark-text-color);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Header Styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 25px 30px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 700;
            font-size: 1.8rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo i {
            font-size: 2rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: var(--gradient-primary);
            color: #fff;
            text-decoration: none;
            padding: 15px 25px;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        /* Confirmation Card */
        .confirmation-card {
            background: var(--card-bg);
            border-radius: var(--border-radius-lg);
            padding: 40px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(20px);
        }

        .confirmation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--gradient-primary);
        }

        .success-header {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 30px;
        }

        .success-icon {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: var(--gradient-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.8rem;
            box-shadow: 0 10px 25px rgba(79, 172, 254, 0.4);
            transition: var(--transition);
        }

        .success-text h1 {
            font-size: 2.2rem;
            margin-bottom: 8px;
            color: var(--secondary-color);
            font-weight: 700;
        }

        .success-text p {
            color: var(--light-text-color);
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .order-number {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.4rem;
        }

        /* Order Grid */
        .order-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        @media (max-width: 768px) {
            .order-grid {
                grid-template-columns: 1fr;
            }
        }

        .order-details, .customer-info {
            background: var(--background-light);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: var(--primary-color);
        }

        /* Order Items */
        .order-items {
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: var(--transition);
        }

        .order-item:hover {
            transform: translateX(5px);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .item-image {
            width: 70px;
            height: 70px;
            border-radius: var(--border-radius);
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details h4 {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--secondary-color);
        }

        .item-details p {
            color: var(--light-text-color);
            font-size: 0.95rem;
        }

        .item-price {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        /* Total Section */
        .total-section {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-top: 20px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-weight: 500;
        }

        .total-row.final {
            font-weight: 700;
            font-size: 1.3rem;
            border-top: 2px solid var(--border-color);
            margin-top: 15px;
            padding-top: 15px;
            color: var(--primary-color);
        }

        /* Customer Info */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--light-text-color);
            font-weight: 500;
        }

        .info-value {
            font-weight: 600;
            color: var(--dark-text-color);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .status-processing {
            background: #fef3c7;
            color: #92400e;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        /* Buttons */
        .btn-group {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 18px 30px;
            border-radius: var(--border-radius);
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: #fff;
            box-shadow: var(--shadow);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            box-shadow: var(--shadow);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
        }

        .btn-light {
            background: #e9ecef;
            color: var(--dark-text-color);
            box-shadow: var(--shadow);
        }

        .btn-light:hover {
            background: #dee2e6;
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        /* Alert Messages */
        .alert {
            padding: 20px 25px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: var(--shadow);
            border-left: 6px solid;
            animation: slideInDown 0.5s ease;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #ecfdf5);
            color: #065f46;
            border-left-color: #10b981;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fef2f2);
            color: #991b1b;
            border-left-color: #ef4444;
        }

        /* Footer Note */
        .footer-note {
            text-align: center;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid rgba(0,0,0,0.1);
            color: var(--light-text-color);
            font-size: 1rem;
        }

        /* Print Styles */
        .print-only {
            display: none;
        }

        @media print {
            .no-print {
                display: none;
            }
            
            .print-only {
                display: block;
            }
            
            body {
                background: white;
            }
            
            .confirmation-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .btn-group {
                display: none;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .confirmation-card {
                padding: 25px;
            }
            
            .success-header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 20px;
            }
            
            .confirmation-card {
                padding: 20px;
            }
            
            .success-icon {
                width: 70px;
                height: 70px;
                font-size: 2.2rem;
            }
            
            .success-text h1 {
                font-size: 1.8rem;
            }
            
            .order-details, .customer-info {
                padding: 20px;
            }
        }

        /* Animation for Success Icon */
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }

        .success-icon {
            animation: bounceIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header no-print">
            <div class="logo">
                <i class="fas fa-store"></i>
                <span>Marcomedia</span>
            </div>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>

        <?php 
        // Display success message if exists
        if(!empty($_SESSION['order_success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['order_success']; unset($_SESSION['order_success']); ?>
            </div>
        <?php endif; ?>

        <?php if($order && !empty($order)): ?>
            <div class="confirmation-card">
                <div class="success-header">
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="success-text">
                        <h1>Order Confirmed!</h1>
                        <p>Thank you for your purchase. Your order has been received and is being processed.</p>
                        <p class="order-number">Order #<?= htmlspecialchars($order['order_number'] ?? '') ?></p>
                    </div>
                </div>

                <div class="order-grid">
                    <div class="order-details">
                        <h3 class="section-title">
                            <i class="fas fa-shopping-bag"></i> Order Details
                        </h3>
                        
                        <div class="order-items">
                            <?php foreach($order_items as $item): ?>
                                <div class="order-item">
                                    <div class="item-info">
                                        <div class="item-image">
                                            <?php if(isset($item['image_path']) && !empty($item['image_path'])): ?>
                                                <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                            <?php else: ?>
                                                <i class="fas fa-box"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="item-details">
                                            <h4><?= htmlspecialchars($item['name'] ?? '') ?></h4>
                                            <p>Quantity: <?= $item['quantity'] ?? 0 ?> × ₱<?= number_format($item['price'] ?? 0, 2) ?></p>
                                        </div>
                                    </div>
                                    <div class="item-price">
                                        ₱<?= number_format(($item['quantity'] ?? 0) * ($item['price'] ?? 0), 2) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="total-section">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span>₱<?= number_format($total, 2) ?></span>
                            </div>
                            <div class="total-row">
                                <span>Shipping:</span>
                                <span>₱<?= number_format(0, 2) ?></span>
                            </div>
                            <div class="total-row">
                                <span>Tax:</span>
                                <span>₱<?= number_format(0, 2) ?></span>
                            </div>
                            <div class="total-row final">
                                <span>Total:</span>
                                <span>₱<?= number_format($total, 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="customer-info">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i> Customer Information
                        </h3>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Order Date:</span>
                                <span class="info-value"><?= date('F j, Y g:i A', strtotime($order['created_at'] ?? 'now')) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status:</span>
                                <span class="info-value status-badge status-processing">
                                    <i class="fas fa-clock"></i> <?= htmlspecialchars($order['status'] ?? 'Pending') ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Customer:</span>
                                <span class="info-value"><?= htmlspecialchars($order['fullname'] ?? $order['customer_name'] ?? 'Unknown') ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?= htmlspecialchars($order['email'] ?? 'Unknown') ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Order Type:</span>
                                <span class="info-value"><?= htmlspecialchars($order['order_type'] ?? 'Online') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-group no-print">
                    <a href="index.php#items" class="btn btn-primary">
                        <i class="fas fa-store"></i> Continue Shopping
                    </a>
                    <button onclick="window.print()" class="btn btn-outline">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                    <a href="orders.php" class="btn btn-light">
                        <i class="fas fa-list"></i> View All Orders
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="confirmation-card">
                <div class="success-header">
                    <div style="width: 90px; height: 90px; border-radius: 50%; background: var(--gradient-secondary); display: flex; align-items: center; justify-content: center; color: white; font-size: 2.8rem; box-shadow: 0 10px 25px rgba(245, 101, 101, 0.4);">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="success-text">
                        <h1>Order Not Found</h1>
                        <p>We couldn't find the order you're looking for.</p>
                        <?php if(!empty($order_number)): ?>
                            <p style="color: var(--light-text-color); font-size: 1rem;">
                                Order Number: <?= htmlspecialchars($order_number) ?>
                            </p>
                            <p style="color: var(--light-text-color); font-size: 0.9rem; margin-top: 10px;">
                                <i class="fas fa-info-circle"></i> This usually happens when:
                                <ul style="text-align: left; margin-top: 10px; color: var(--light-text-color);">
                                    <li>The order wasn't saved to the database</li>
                                    <li>You've refreshed the page after ordering</li>
                                    <li>There was an error during checkout</li>
                                </ul>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="btn-group no-print">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Return to Home
                    </a>
                    <a href="my_cart.php" class="btn btn-outline">
                        <i class="fas fa-shopping-cart"></i> Back to Cart
                    </a>
                    <a href="orders.php" class="btn btn-light">
                        <i class="fas fa-list"></i> View Your Orders
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="footer-note no-print">
            <p>You will receive an email confirmation shortly. For questions, contact us at support@marcomedia.com</p>
            <p style="margin-top: 10px;">Thank you for shopping with Marcomedia!</p>
        </div>
    </div>

    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Print functionality
            const printButton = document.querySelector('button[onclick="window.print()"]');
            if (printButton) {
                printButton.addEventListener('click', function() {
                    window.print();
                });
            }
            
            // Add hover effects to order items
            const orderItems = document.querySelectorAll('.order-item');
            orderItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(8px)';
                });
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
        });
    </script>
</body>
</html>