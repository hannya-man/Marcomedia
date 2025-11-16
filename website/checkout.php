<?php
session_name('website_session');
session_start();
require __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'] ?? 'Guest';
$user_name = $_SESSION['user_name'] ?? $username;
$role      = $_SESSION['role'] ?? 'customer';
$cart      = $_SESSION['cart'] ?? [];

// Initialize messages
$success_message = '';
$error_message   = '';

// Fetch user's full name and details from database
$user_fullname = $user_name;
$user_email = $username;
$user_phone = '';

$user_stmt = $conn->prepare("SELECT fullname, email, phone FROM users WHERE id = ?");
if ($user_stmt) {
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $user_fullname = $user_data['fullname'] ?? $user_name;
        $user_email = $user_data['email'] ?? $username;
        $user_phone = $user_data['phone'] ?? '';
    }
    $user_stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cart)) {
    $conn->begin_transaction();
    try {
        // Calculate total quantity and amount
        $total_qty = array_sum($cart);
        $total_amount = 0;

        // First calculate total amount
        $ids = array_keys($cart);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $sql = "SELECT id, price, name FROM stock WHERE id IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed for stock selection: " . $conn->error);
            }
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $total_amount += $row['price'] * $cart[$row['id']];
            }
            $stmt->close();
        }

        // Generate order number for online orders
        $order_number = 'CO-' . date('YmdHis') . rand(100, 999);

        // Check what columns actually exist in the orders table
        $debug_columns = $conn->query("SHOW COLUMNS FROM orders");
        $existing_columns = [];
        while ($col = $debug_columns->fetch_assoc()) {
            $existing_columns[$col['Field']] = $col['Type'];
        }
        
        error_log("DEBUG - Existing columns: " . implode(', ', array_keys($existing_columns)));

        // Build query based on actual table structure
        $columns = [];
        $placeholders = [];
        $bind_types = "";
        $bind_values = [];

        // Always include these basic columns
        $basic_columns = [
            'order_number' => ['value' => $order_number, 'type' => 's'],
            'user_id' => ['value' => $user_id, 'type' => 'i'],
            'customer_name' => ['value' => $user_fullname, 'type' => 's'],
            'status' => ['value' => 'Pending', 'type' => 's'],
            'order_type' => ['value' => 'online', 'type' => 's'],
            'role' => ['value' => $role, 'type' => 's']
        ];

        foreach ($basic_columns as $col_name => $col_data) {
            if (array_key_exists($col_name, $existing_columns)) {
                $columns[] = $col_name;
                $placeholders[] = '?';
                $bind_types .= $col_data['type'];
                $bind_values[] = $col_data['value'];
            }
        }

        // Add optional columns if they exist
        $optional_columns = [
            'quantity' => ['value' => $total_qty, 'type' => 'i'],
            'total_amount' => ['value' => $total_amount, 'type' => 'd'],
            'customer_email' => ['value' => $user_email, 'type' => 's'],
            'customer_phone' => ['value' => $user_phone, 'type' => 's'],
            'payment_method' => ['value' => 'cash', 'type' => 's'],
            'created_by' => ['value' => 'Online Store', 'type' => 's']
        ];

        foreach ($optional_columns as $col_name => $col_data) {
            if (array_key_exists($col_name, $existing_columns)) {
                $columns[] = $col_name;
                $placeholders[] = '?';
                $bind_types .= $col_data['type'];
                $bind_values[] = $col_data['value'];
            }
        }

        // Add created_at if it exists
        if (array_key_exists('created_at', $existing_columns)) {
            $columns[] = 'created_at';
            $placeholders[] = 'NOW()';
        }

        // Build the final SQL query
        $columns_str = implode(', ', $columns);
        $placeholders_str = implode(', ', $placeholders);
        
        $sql = "INSERT INTO orders ($columns_str) VALUES ($placeholders_str)";
        error_log("DEBUG - Final SQL: $sql");
        error_log("DEBUG - Bind types: $bind_types");
        error_log("DEBUG - Bind values count: " . count($bind_values));
        error_log("DEBUG - Bind values: " . print_r($bind_values, true));

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed for orders: " . $conn->error);
        }

        // Bind parameters only if we have values to bind
        if (!empty($bind_values)) {
            $stmt->bind_param($bind_types, ...$bind_values);
        }

        $stmt->execute();
        
        if ($stmt->error) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $order_id = $conn->insert_id;
        $stmt->close();

        // Insert order items
        $order_items_columns = $conn->query("SHOW COLUMNS FROM order_items");
        $order_items_cols = [];
        while ($col = $order_items_columns->fetch_assoc()) {
            $order_items_cols[] = $col['Field'];
        }

        $has_unit_price = in_array('unit_price', $order_items_cols);
        $has_subtotal = in_array('subtotal', $order_items_cols);

        if ($has_unit_price && $has_subtotal) {
            $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        } else {
            $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
        }

        if (!$item_stmt) {
            throw new Exception("Prepare failed for order_items: " . $conn->error);
        }

        // Insert each cart item
        foreach ($cart as $product_id => $qty) {
            if ($qty > 0) {
                // Get product price and name
                $price_stmt = $conn->prepare("SELECT price, name FROM stock WHERE id = ?");
                if (!$price_stmt) {
                    throw new Exception("Prepare failed for product price: " . $conn->error);
                }
                $price_stmt->bind_param("i", $product_id);
                $price_stmt->execute();
                $price_result = $price_stmt->get_result();
                $product = $price_result->fetch_assoc();
                $price_stmt->close();

                if (!$product) {
                    throw new Exception("Product with ID $product_id not found in stock.");
                }

                $unit_price = $product['price'];
                $subtotal = $unit_price * $qty;

                if ($has_unit_price && $has_subtotal) {
                    $item_stmt->bind_param("iiidd", $order_id, $product_id, $qty, $unit_price, $subtotal);
                } else {
                    $item_stmt->bind_param("iii", $order_id, $product_id, $qty);
                }
                $item_stmt->execute();

                // Update stock quantity
                $update_stmt = $conn->prepare("UPDATE stock SET qty = qty - ? WHERE id = ?");
                if (!$update_stmt) {
                    throw new Exception("Prepare failed for stock update: " . $conn->error);
                }
                $update_stmt->bind_param("ii", $qty, $product_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        $item_stmt->close();

        $conn->commit();

        // FIXED DEBUG: Only select columns that actually exist
        $debug_columns_to_select = [];
        $possible_debug_columns = ['id', 'order_number', 'customer_name', 'customer_email', 'status', 'order_type'];
        
        foreach ($possible_debug_columns as $col) {
            if (array_key_exists($col, $existing_columns)) {
                $debug_columns_to_select[] = $col;
            }
        }
        
        if (!empty($debug_columns_to_select)) {
            $debug_columns_str = implode(', ', $debug_columns_to_select);
            $debug_sql = "SELECT $debug_columns_str FROM orders WHERE order_number = ?";
            
            $debug_stmt = $conn->prepare($debug_sql);
            if ($debug_stmt) {
                $debug_stmt->bind_param("s", $order_number);
                $debug_stmt->execute();
                $debug_result = $debug_stmt->get_result();
                $debug_order = $debug_result->fetch_assoc();
                $debug_stmt->close();
                
                error_log("DEBUG - Order created: " . print_r($debug_order, true));
            }
        }

        error_log("DEBUG - Order number: " . $order_number);
        error_log("DEBUG - User ID: " . $user_id);
        error_log("DEBUG - Customer Name: " . $user_fullname);
        error_log("DEBUG - Customer Email: " . $user_email);

        // Set success message in session
        $_SESSION['order_success'] = "Order #$order_number created successfully! Total: ₱" . number_format($total_amount, 2);

        // Clear cart
        unset($_SESSION['cart']);
        unset($_SESSION['cart_design']);

        // Redirect to confirmation page
        header("Location: order_confirmation.php?order_number=" . urlencode($order_number));
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error creating order: " . $e->getMessage();
        
        // Log detailed error
        error_log("Order creation error: " . $e->getMessage());
        error_log("Cart: " . print_r($cart, true));
        error_log("User ID: " . $user_id);
        error_log("User Fullname: " . $user_fullname);
    }
}

// Fetch cart items from stock table
$cart_items = [];
$total = 0;

if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $sql = "SELECT * FROM stock WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Prepare failed (stock): " . $conn->error);
    }

    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $row['quantity'] = $cart[$id];
        $row['subtotal'] = $row['quantity'] * $row['price'];
        $total += $row['subtotal'];
        $cart_items[$id] = $row;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - Marcomedia POS</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
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
    max-width: 1200px;
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

h2 {
    font-size: 2.5rem;
    font-weight: 700;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: flex;
    align-items: center;
    gap: 15px;
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

/* Customer Info */
.customer-info {
    background: var(--card-bg);
    border-radius: var(--border-radius-lg);
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: var(--shadow);
    border: 1px solid rgba(255,255,255,0.2);
    backdrop-filter: blur(20px);
}

.customer-info h3 {
    margin: 0 0 20px;
    color: var(--secondary-color);
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.customer-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.customer-detail {
    display: flex;
    flex-direction: column;
}

.customer-detail label {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.customer-detail span {
    font-size: 1.1rem;
    color: var(--dark-text-color);
    font-weight: 500;
}

/* Checkout Grid */
.checkout-grid {
    display: flex;
    flex-direction: column;
    gap: 25px;
    margin-bottom: 40px;
}

.checkout-item {
    display: flex;
    align-items: center;
    background: var(--card-bg);
    border-radius: var(--border-radius-lg);
    padding: 30px;
    box-shadow: var(--shadow);
    gap: 30px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.2);
    backdrop-filter: blur(20px);
}

.checkout-item:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-lg);
}

.checkout-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    background: var(--gradient-primary);
}

.checkout-item img {
    width: 140px;
    height: 140px;
    object-fit: cover;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.checkout-item:hover img {
    transform: scale(1.05);
}

.checkout-item-details {
    flex: 1;
}

.checkout-item-details h3 {
    margin: 0 0 12px;
    color: var(--secondary-color);
    font-size: 1.5rem;
    font-weight: 600;
}

.checkout-item-details p {
    margin: 8px 0;
    font-weight: 500;
}

.price {
    font-size: 1.3rem;
    color: var(--primary-color);
    font-weight: 700;
}

.subtotal-display {
    font-size: 1.4rem;
    color: var(--secondary-color);
    font-weight: 800;
}

/* Checkout Summary */
.checkout-summary {
    background: var(--card-bg);
    border-radius: var(--border-radius-lg);
    padding: 35px 40px;
    box-shadow: var(--shadow-lg);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid rgba(255,255,255,0.2);
    backdrop-filter: blur(20px);
}

.total-display {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--secondary-color);
}

.total-display strong {
    font-size: 2.2rem;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.checkout-actions {
    display: flex;
    gap: 20px;
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

.confirm-btn {
    background: var(--gradient-primary);
    color: #fff;
    box-shadow: var(--shadow);
}

.confirm-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 40px rgba(37, 99, 235, 0.4);
}

.print-btn {
    background: var(--gradient-accent);
    color: #fff;
    box-shadow: var(--shadow);
}

.print-btn:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

.shop-btn {
    background: #e9ecef;
    color: var(--dark-text-color);
    box-shadow: var(--shadow);
}

.shop-btn:hover {
    background: #dee2e6;
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

/* Empty Cart */
.empty-cart {
    text-align: center;
    padding: 100px 40px;
    background: var(--card-bg);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-lg);
    border: 1px solid rgba(255,255,255,0.2);
    backdrop-filter: blur(20px);
}

.empty-cart i {
    font-size: 6rem;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 30px;
}

.empty-cart p {
    font-size: 1.5rem;
    color: var(--dark-text-color);
    margin-bottom: 35px;
    font-weight: 500;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.checkout-item {
    animation: fadeIn 0.5s ease forwards;
}

.checkout-item:nth-child(1) { animation-delay: 0.1s; }
.checkout-item:nth-child(2) { animation-delay: 0.2s; }
.checkout-item:nth-child(3) { animation-delay: 0.3s; }
.checkout-item:nth-child(4) { animation-delay: 0.4s; }

/* Responsive Design */
@media (max-width: 992px) {
    .header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .checkout-summary {
        flex-direction: column;
        gap: 25px;
        text-align: center;
    }
    
    .checkout-actions {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 768px) {
    body {
        padding: 15px;
    }
    
    .checkout-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
        padding: 25px;
    }
    
    .checkout-item img {
        width: 100%;
        height: 200px;
    }
    
    .checkout-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .btn {
        width: 100%;
        text-align: center;
    }
    
    h2 {
        font-size: 2rem;
    }
    
    .empty-cart {
        padding: 60px 20px;
    }
    
    .empty-cart i {
        font-size: 4rem;
    }
    
    .empty-cart p {
        font-size: 1.3rem;
    }
    
    .customer-details {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .header {
        padding: 20px;
    }
    
    .checkout-item {
        padding: 20px;
    }
    
    .checkout-summary {
        padding: 25px 20px;
    }
    
    .total-display {
        font-size: 1.4rem;
    }
    
    .total-display strong {
        font-size: 1.8rem;
    }
    
    .customer-info {
        padding: 20px;
    }
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2><i class="fas fa-shopping-bag"></i> Checkout</h2>
        <a href="my_cart.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Cart</a>
    </div>

    <?php if($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if(empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <p>Your cart is empty. Nothing to checkout!</p>
            <a href="index.php#items" class="btn shop-btn"><i class="fas fa-store"></i> Continue Shopping</a>
        </div>
    <?php else: ?>
        <!-- Customer Information -->
        <div class="customer-info">
            <h3><i class="fas fa-user"></i> Customer Information</h3>
            <div class="customer-details">
                <div class="customer-detail">
                    <label>Customer Name</label>
                    <span><?php echo htmlspecialchars($user_fullname); ?></span>
                </div>
                <div class="customer-detail">
                    <label>Email</label>
                    <span><?php echo htmlspecialchars($user_email); ?></span>
                </div>
                <?php if(!empty($user_phone)): ?>
                <div class="customer-detail">
                    <label>Phone</label>
                    <span><?php echo htmlspecialchars($user_phone); ?></span>
                </div>
                <?php endif; ?>
                <div class="customer-detail">
                    <label>Account Type</label>
                    <span><?php echo ucfirst($role); ?></span>
                </div>
                <div class="customer-detail">
                    <label>Order Type</label>
                    <span>Online Order</span>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="checkout-grid" id="printableArea">
            <?php foreach($cart_items as $item): ?>
                <div class="checkout-item">
                    <?php if(!empty($item['image_path'])): ?>
                        <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    <?php else: ?>
                        <img src="images/placeholder.jpg" alt="No image available">
                    <?php endif; ?>
                    <div class="checkout-item-details">
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="price">Price: ₱<?= number_format($item['price'],2) ?></p>
                        <p>Quantity: <?= $item['quantity'] ?></p>
                        <p class="subtotal-display">Subtotal: ₱<?= number_format($item['subtotal'],2) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Order Summary -->
        <div class="checkout-summary">
            <div class="total-display">
                <p>Total: <strong>₱<?= number_format($total,2) ?></strong></p>
            </div>
            <div class="checkout-actions">
                <form method="POST" style="display:inline;">
                    <button type="submit" class="btn confirm-btn">
                        <i class="fas fa-check-circle"></i> Confirm Order
                    </button>
                </form>
                <button class="btn print-btn" onclick="printReceipt()">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <a href="index.php#items" class="btn shop-btn">
                    <i class="fas fa-store"></i> Continue Shopping
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function printReceipt() {
    const total = document.querySelector('.total-display strong')?.innerText || '₱0.00';

    const receiptHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt - Marcomedia</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    color: #333;
                }
                .receipt-header {
                    text-align: center;
                    margin-bottom: 20px;
                    padding-bottom: 15px;
                    border-bottom: 2px dashed #ccc;
                }
                .receipt-header h2 {
                    margin: 0;
                    font-size: 22px;
                    color: #10627b;
                }
                .receipt-header p {
                    margin: 0;
                    font-size: 13px;
                    color: #666;
                }
                .receipt-info {
                    margin-bottom: 15px;
                    font-size: 14px;
                    color: #444;
                }
                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 15px;
                    font-size: 14px;
                }
                .items-table th {
                    background: #f8f9fa;
                    text-align: left;
                    padding: 8px;
                    border-bottom: 1px solid #ddd;
                }
                .items-table td {
                    padding: 8px;
                    border-bottom: 1px solid #eee;
                }
                .total-section {
                    text-align: right;
                    margin-top: 20px;
                    font-size: 16px;
                    font-weight: bold;
                }
                .receipt-footer {
                    margin-top: 25px;
                    text-align: center;
                    font-size: 13px;
                    color: #555;
                    border-top: 2px dashed #ccc;
                    padding-top: 15px;
                }
                @media print {
                    body { margin: 0; padding: 15px; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="receipt-header">
                <h2>Marcomedia</h2>
                <p>Tangub City, Philippines</p>
            </div>

            <div class="receipt-info">
                <p><strong>Date:</strong> ${new Date().toLocaleDateString()}</p>
                <p><strong>Time:</strong> ${new Date().toLocaleTimeString()}</p>
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($user_fullname); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
                <p><strong>Order Type:</strong> Online</p>
            </div>

            <h3 style="margin-bottom:10px; font-size:16px; color:#2596be;">Items Purchased</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($cart_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>₱<?= number_format($item['price'],2) ?></td>
                        <td style="text-align:right;">₱<?= number_format($item['subtotal'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-section">
                <p>Total: ${total}</p>
            </div>

            <div class="receipt-footer">
                <p>Thank you for shopping with Marcomedia!<br>
                For inquiries, contact us at (XXX) XXX-XXXX</p>
                <p style="margin-top:10px; font-size:12px; color:#aaa;">This is a system-generated receipt.</p>
            </div>
            
            <div class="no-print" style="text-align:center; margin-top:20px;">
                <button onclick="window.close()" style="padding:8px 15px; background:#10627b; color:white; border:none; border-radius:4px; cursor:pointer;">
                    Close Window
                </button>
            </div>
        </body>
        </html>
    `;

    const printWindow = window.open('', '_blank', 'width=650,height=700');
    printWindow.document.write(receiptHTML);
    printWindow.document.close();
    
    // Wait for content to load before printing
    printWindow.onload = function() {
        printWindow.focus();
        printWindow.print();
    };
}
</script>
</body>
</html>