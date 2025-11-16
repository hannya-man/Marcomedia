<?php
session_name('website_session');
session_start();
require __DIR__ . '/../db.php';

// Handle update cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['qty'] as $product_id => $new_qty) {
        $new_qty = max(0, intval($new_qty));
        if ($new_qty == 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            $_SESSION['cart'][$product_id] = $new_qty;
        }
    }
}

// Fetch cart items
$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $result = $conn->query("SELECT * FROM stock WHERE id IN ($ids)");
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $row['quantity'] = $_SESSION['cart'][$id];
        $row['subtotal'] = $row['quantity'] * $row['price'];
        $total += $row['subtotal'];
        $cart_items[$id] = $row;
    }
}

// Remove item
if (isset($_GET['remove'])) {
    $id = intval($_GET['remove']);
    unset($_SESSION['cart'][$id]);
    header("Location: my_cart.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Cart - Marcomedia POS</title>
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

/* Cart Grid */
.cart-grid {
    display: flex;
    flex-direction: column;
    gap: 25px;
    margin-bottom: 40px;
}

.cart-item {
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
}

.cart-item:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-lg);
}

.cart-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    background: var(--gradient-primary);
}

.cart-item img {
    width: 140px;
    height: 140px;
    object-fit: cover;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.cart-item:hover img {
    transform: scale(1.05);
}

.cart-item-details {
    flex: 1;
}

.cart-item-details h3 {
    margin: 0 0 12px;
    color: var(--secondary-color);
    font-size: 1.5rem;
    font-weight: 600;
}

.cart-item-details p {
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

.qty-input {
    width: 90px;
    padding: 12px 15px;
    border: 2px solid #e2e8f0;
    border-radius: var(--border-radius);
    font-size: 1.1rem;
    font-weight: 600;
    text-align: center;
    transition: var(--transition);
    background: white;
}

.qty-input:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    transform: translateY(-2px);
}

.cart-item-actions {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.remove-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: var(--gradient-secondary);
    color: #fff;
    border: none;
    padding: 15px 20px;
    border-radius: var(--border-radius);
    cursor: pointer;
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
}

.remove-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: 0.5s;
}

.remove-btn:hover::before {
    left: 100%;
}

.remove-btn:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

/* Cart Summary */
.cart-summary {
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

.cart-total {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--secondary-color);
}

.cart-total strong {
    font-size: 2.2rem;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.cart-actions {
    display: flex;
    gap: 20px;
}

.update-btn, .checkout-btn {
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

.update-btn::before, .checkout-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: 0.5s;
}

.update-btn:hover::before, .checkout-btn:hover::before {
    left: 100%;
}

.update-btn {
    background: #e9ecef;
    color: var(--dark-text-color);
    box-shadow: var(--shadow);
}

.update-btn:hover {
    background: #dee2e6;
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

.checkout-btn {
    background: var(--gradient-primary);
    color: #fff;
    box-shadow: var(--shadow);
}

.checkout-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 40px rgba(37, 99, 235, 0.4);
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

.shop-btn {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: var(--gradient-primary);
    color: #fff;
    text-decoration: none;
    padding: 18px 35px;
    border-radius: var(--border-radius);
    font-weight: 700;
    transition: var(--transition);
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
}

.shop-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: 0.5s;
}

.shop-btn:hover::before {
    left: 100%;
}

.shop-btn:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

/* Responsive Design */
@media (max-width: 992px) {
    .header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .cart-summary {
        flex-direction: column;
        gap: 25px;
        text-align: center;
    }
    
    .cart-actions {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 768px) {
    body {
        padding: 15px;
    }
    
    .cart-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
        padding: 25px;
    }
    
    .cart-item img {
        width: 100%;
        height: 200px;
    }
    
    .cart-item-actions {
        flex-direction: row;
        width: 100%;
    }
    
    .remove-btn {
        flex: 1;
        text-align: center;
    }
    
    .cart-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .update-btn, .checkout-btn {
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
}

@media (max-width: 480px) {
    .header {
        padding: 20px;
    }
    
    .cart-item {
        padding: 20px;
    }
    
    .cart-summary {
        padding: 25px 20px;
    }
    
    .cart-total {
        font-size: 1.4rem;
    }
    
    .cart-total strong {
        font-size: 1.8rem;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2><i class="fas fa-shopping-cart"></i> My Shopping Cart</h2>
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
    </div>
    
    <?php if(empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <p>Your cart is empty. Start adding some amazing products!</p>
            <a href="index.php" class="shop-btn"><i class="fas fa-store"></i> Start Shopping</a>
        </div>
    <?php else: ?>
        <form method="post" action="my_cart.php">
            <div class="cart-grid">
                <?php foreach($cart_items as $item): ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="cart-item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="price">Price: ₱<?php echo number_format($item['price'],2); ?></p>
                            <p>
                                Quantity: 
                                <input type="number" name="qty[<?php echo $item['id']; ?>]" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="0" class="qty-input" 
                                       data-price="<?php echo $item['price']; ?>">
                            </p>
                            <p class="subtotal-display">Subtotal: ₱<span class="subtotal"><?php echo number_format($item['subtotal'],2); ?></span></p>
                        </div>
                        <div class="cart-item-actions">
                            <a href="?remove=<?php echo $item['id']; ?>" class="remove-btn">
                                <i class="fas fa-trash"></i> Remove
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cart-summary">
                <div class="cart-total">
                    <p>Total: <strong>₱<span id="grand-total"><?php echo number_format($total,2); ?></span></strong></p>
                </div>
                    <a href="checkout.php" class="checkout-btn">
                        <i class="fas fa-credit-card"></i> Proceed to Checkout
                    </a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('input', function() {
        let price = parseFloat(this.dataset.price);
        let qty = parseInt(this.value) || 0;
        let subtotalCell = this.closest('.cart-item-details').querySelector('.subtotal');
        let newSubtotal = price * qty;
        subtotalCell.textContent = newSubtotal.toFixed(2);

        // update grand total
        let total = 0;
        document.querySelectorAll('.subtotal').forEach(cell => {
            total += parseFloat(cell.textContent);
        });
        document.getElementById('grand-total').textContent = total.toFixed(2);
    });
});
</script>

</body>
</html>