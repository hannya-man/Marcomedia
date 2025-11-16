<?php
session_name('website_session');
session_start();
require __DIR__ . '/../db.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['design_action'])) {
    header('Content-Type: application/json');
    
    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Please log in to save designs']);
        exit;
    }
    
    $item_id = intval($_POST['item_id']);
    
    switch ($_POST['design_action']) {
        case 'save_design':
            $design_name = trim($_POST['design_name']);
            $design_data = $_POST['design_data'];
            
            if (empty($design_name)) {
                echo json_encode(['status' => 'error', 'message' => 'Design name is required']);
                exit;
            }
            
            $check_stmt = $conn->prepare("SELECT id FROM saved_designs WHERE user_id = ? AND item_id = ? AND design_name = ?");
            $check_stmt->bind_param("iis", $user_id, $item_id, $design_name);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'You already have a design with this name']);
                exit;
            }
            
            $save_stmt = $conn->prepare("INSERT INTO saved_designs (user_id, item_id, design_name, design_data) VALUES (?, ?, ?, ?)");
            $save_stmt->bind_param("iiss", $user_id, $item_id, $design_name, $design_data);
            
            if ($save_stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Design saved successfully', 'design_id' => $conn->insert_id]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save design: ' . $conn->error]);
            }
            exit;
            
        case 'load_design':
            $design_id = intval($_POST['design_id']);
            
            $load_stmt = $conn->prepare("SELECT design_data FROM saved_designs WHERE id = ? AND user_id = ?");
            $load_stmt->bind_param("ii", $design_id, $user_id);
            $load_stmt->execute();
            $load_result = $load_stmt->get_result();
            
            if ($load_result->num_rows > 0) {
                $design = $load_result->fetch_assoc();
                echo json_encode(['status' => 'success', 'design_data' => $design['design_data']]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Design not found']);
            }
            exit;
            
        case 'delete_design':
            $design_id = intval($_POST['design_id']);
            
            $delete_stmt = $conn->prepare("DELETE FROM saved_designs WHERE id = ? AND user_id = ?");
            $delete_stmt->bind_param("ii", $design_id, $user_id);
            
            if ($delete_stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Design deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete design']);
            }
            exit;
            
        case 'get_designs':
            $get_stmt = $conn->prepare("SELECT id, design_name, created_at FROM saved_designs WHERE user_id = ? AND item_id = ? ORDER BY created_at DESC");
            $get_stmt->bind_param("ii", $user_id, $item_id);
            $get_stmt->execute();
            $result = $get_stmt->get_result();
            $designs = [];
            
            while ($row = $result->fetch_assoc()) {
                $designs[] = $row;
            }
            
            echo json_encode(['status' => 'success', 'designs' => $designs]);
            exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $item_id = intval($_POST['item_id']);
    $quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id] += $quantity;
    } else {
        $_SESSION['cart'][$item_id] = $quantity;
    }

    if (isset($_POST['design']) && !empty($_POST['design'])) {
        if (!isset($_SESSION['cart_design'])) $_SESSION['cart_design'] = [];
        $_SESSION['cart_design'][$item_id] = $_POST['design'];
    }

    echo json_encode(['status'=>'success','message'=>'Added to cart']);
    exit;
}

$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
$item = null;
if ($item_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM stock WHERE id=? AND status='active'");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) $item = $result->fetch_assoc();
}

if (!$item) { 
    echo "<h2>No product selected or inactive.</h2>"; 
    exit; 
}

$product_type = 'shirt';
$product_name = strtolower($item['name']);
if (strpos($product_name, 'shirt') !== false || strpos($product_name, 't-shirt') !== false || 
    strpos($product_name, 'polo') !== false) {
    $product_type = 'shirt';
} elseif (strpos($product_name, 'mug') !== false) {
    $product_type = 'mug';
} elseif (strpos($product_name, 'tumbler') !== false) {
    $product_type = 'tumbler';
} elseif (strpos($product_name, 'keychain') !== false) {
    $product_type = 'keychain';
} elseif (strpos($product_name, 'pen') !== false) {
    $product_type = 'pen';
} elseif (strpos($product_name, 'notebook') !== false) {
    $product_type = 'notebook';
} elseif (strpos($product_name, 'wallet') !== false) {
    $product_type = 'wallet';
} elseif (strpos($product_name, 'pencil') !== false) {
    $product_type = 'pencil';
} elseif (strpos($product_name, 'handkerchief') !== false) {
    $product_type = 'handkerchief';
} elseif (strpos($product_name, 'umbrella') !== false) {
    $product_type = 'umbrella';
} elseif (strpos($product_name, 'mousepad') !== false) {
    $product_type = 'mousepad';
} elseif (strpos($product_name, 'sweatshirt') !== false || strpos($product_name, 'hoodie') !== false) {
    $product_type = 'sweatshirt';
} elseif (strpos($product_name, 'uniform') !== false || strpos($product_name, 'PE') !== false) {
    $product_type = 'uniform';
} elseif (strpos($product_name, 'apron') !== false) {
    $product_type = 'apron';
} elseif (strpos($product_name, 'sablay') !== false || strpos($product_name, 'sash') !== false) {
    $product_type = 'sash';
} elseif (strpos($product_name, 'brochure') !== false || strpos($product_name, 'magazine') !== false) {
    $product_type = 'brochure';
} elseif (strpos($product_name, 'cap') !== false || strpos($product_name, 'hat') !== false) {
    $product_type = 'cap';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Design <?php echo htmlspecialchars($item['name']); ?> - Marcomedia Customizer</title>
<meta name="description" content="Customize your <?php echo htmlspecialchars($item['name']); ?> with Marcomedia's professional 3D design tool">
<meta name="keywords" content="custom design, merchandise, personalization, 3D design, <?php echo htmlspecialchars($item['name']); ?>">

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
        --header-height: 80px;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        font-family: 'Poppins', sans-serif;
        line-height: 1.7;
        color: var(--dark-text-color);
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        overflow-x: hidden;
        padding-top: var(--header-height);
    }

    h1, h2, h3, h4 {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        color: var(--secondary-color);
        margin-bottom: 0.5em;
    }

    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 5%;
        height: var(--header-height);
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        box-shadow: 0 4px 30px rgba(0,0,0,0.1);
        transition: var(--transition);
    }

    .logo-link {
        text-decoration: none; 
        transition: var(--transition);
    }

    .logo-link:hover {
        transform: translateY(-2px);
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .logo-img {
        width: 45px;
        height: 45px;
        object-fit: contain;
        filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
    }

    .logo h2 {
        font-size: 2rem;
        font-weight: 800;
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -0.5px;
        padding-top: 20px;
    }

    header nav {
        display: flex;
        align-items: center;
        gap: 30px;
    }

    header nav a {
        color: var(--dark-text-color);
        text-decoration: none;
        font-weight: 600;
        padding: 12px 0;
        transition: var(--transition);
        position: relative;
        font-size: 1.1rem;
    }

    header nav a:hover {
        color: var(--primary-color);
        transform: translateY(-2px);
    }

    header nav a:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 3px;
        background: var(--gradient-primary);
        transition: var(--transition);
        border-radius: 2px;
    }

    header nav a:hover:after {
        width: 100%;
    }

    .cart-dropdown { 
        position: relative; 
        cursor: pointer; 
        padding: 12px 20px;
        border-radius: var(--border-radius);
        background: var(--background-light);
        transition: var(--transition);
        font-weight: 600;
    }

    .cart-dropdown:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .cart-items { 
        display: none; 
        position: absolute;
        right: 0;
        top: 100%;
        background: var(--card-bg);
        border-radius: var(--border-radius);
        width: 320px;
        padding: 25px;
        box-shadow: var(--shadow-lg);
        z-index: 1000;
        margin-top: 15px;
        animation: fadeInUp 0.3s ease;
    }
    
    .cart-dropdown:hover .cart-items { 
        display: block; 
    }
    
    .cart-item { 
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f5f9;
        transition: var(--transition);
    }

    .cart-item:hover {
        transform: translateX(5px);
    }
    
    .cart-item img { 
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: var(--shadow);
    }
    
    .view-cart { 
        display: block;
        text-align: center;
        background: var(--gradient-primary);
        color: #fff;
        padding: 15px;
        border-radius: var(--border-radius);
        margin-top: 15px;
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
        box-shadow: var(--shadow);
    }

    .view-cart:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-lg);
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    .page-header {
        text-align: center;
        margin-bottom: 40px;
        padding: 40px 20px;
        background: var(--gradient-primary);
        border-radius: var(--border-radius-lg);
        color: white;
        box-shadow: var(--shadow-lg);
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="%23ffffff" opacity="0.05"><circle cx="50" cy="50" r="2"/></svg>') repeat;
    }

    .page-header h1 {
        font-size: 3.5rem;
        margin-bottom: 15px;
        color: #fff;
        position: relative;
        display: inline-block;
        text-shadow: 2px 2px 10px rgba(0,0,0,0.2);
    }

    .page-header h1:after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 5px;
        background: var(--gradient-accent);
        border-radius: 3px;
    }

    .page-header p {
        font-size: 1.3rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }

    .product-info {
        background: var(--card-bg);
        padding: 30px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin-bottom: 30px;
        text-align: center;
        border-left: 6px solid var(--primary-color);
    }

    .product-info h3 {
        color: var(--secondary-color);
        margin-bottom: 15px;
        font-size: 1.8rem;
    }

    .product-price {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 15px 0;
    }

    .product-info p {
        color: var(--light-text-color);
        font-size: 1.1rem;
        line-height: 1.6;
    }

    .main-content {
        display: flex;
        gap: 30px;
        margin-bottom: 30px;
    }

    .product-preview {
        flex: 1;
        background: var(--card-bg);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: var(--transition);
    }

    .product-preview:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .preview-header {
        padding: 20px 25px;
        border-bottom: 1px solid #e1e5e9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--background-light);
    }

    .preview-header h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--secondary-color);
    }

    .preview-actions {
        display: flex;
        gap: 10px;
    }

    .preview-actions button {
        background: var(--card-bg);
        border: 1px solid #e1e5e9;
        padding: 10px 15px;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-size: 14px;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 5px;
        font-weight: 500;
    }

    .preview-actions button:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .preview-container {
        flex: 1;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        background: #1a1a1a;
        position: relative;
        overflow: hidden;
        min-height: 600px;
    }

    #threejsCanvas {
        width: 100%;
        height: 100%;
        outline: none;
    }

    .view-controls {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 10px;
        background: rgba(255, 255, 255, 0.9);
        padding: 10px 15px;
        border-radius: var(--border-radius);
        backdrop-filter: blur(10px);
        box-shadow: var(--shadow);
    }

    .view-controls button {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .view-controls button:hover {
        background: var(--secondary-color);
        transform: translateY(-2px);
    }

    .customization-panel {
        width: 380px;
        background: var(--card-bg);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: var(--transition);
    }

    .customization-panel:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .panel-tabs {
        display: flex;
        border-bottom: 1px solid #e1e5e9;
        background: var(--background-light);
    }

    .panel-tab {
        flex: 1;
        text-align: center;
        padding: 16px;
        background: none;
        border: none;
        cursor: pointer;
        font-weight: 500;
        transition: var(--transition);
        border-bottom: 3px solid transparent;
        color: var(--dark-text-color);
        font-family: 'Poppins', sans-serif;
    }

    .panel-tab.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
        background: var(--card-bg);
    }

    .panel-tab:hover {
        background: rgba(37, 99, 235, 0.05);
    }

    .panel-content {
        padding: 24px;
        flex: 1;
        overflow-y: auto;
        max-height: 600px;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .control-group {
        margin-bottom: 24px;
    }

    .control-group h3 {
        margin: 0 0 16px 0;
        font-size: 16px;
        font-weight: 600;
        color: var(--secondary-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .control-group h3 i {
        font-size: 18px;
    }

    .control-row {
        display: flex;
        gap: 12px;
        margin-bottom: 12px;
        align-items: center;
    }

    .control-row label {
        font-size: 14px;
        min-width: 80px;
        font-weight: 500;
        color: var(--dark-text-color);
    }

    .control-row input, .control-row select {
        flex: 1;
        padding: 12px 15px;
        border: 1px solid #e1e5e9;
        border-radius: var(--border-radius);
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
        transition: var(--transition);
    }

    .control-row input:focus, .control-row select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }

    .color-preview {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: 1px solid #e1e5e9;
        cursor: pointer;
    }

    .color-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
        margin-top: 10px;
    }

    .color-option {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        cursor: pointer;
        border: 2px solid transparent;
        transition: transform 0.2s;
    }

    .color-option:hover {
        transform: scale(1.1);
    }

    .color-option.selected {
        border-color: var(--dark-text-color);
        transform: scale(1.1);
    }

    .upload-area {
        border: 2px dashed #e1e5e9;
        border-radius: 8px;
        padding: 30px 20px;
        text-align: center;
        cursor: pointer;
        transition: var(--transition);
        margin-bottom: 15px;
    }

    .upload-area:hover {
        border-color: var(--primary-color);
        background: rgba(37, 99, 235, 0.05);
    }

    .upload-area i {
        font-size: 32px;
        color: var(--primary-color);
        margin-bottom: 10px;
    }

    .upload-area p {
        margin: 0;
        font-size: 14px;
    }

    .upload-area span {
        color: var(--primary-color);
        font-weight: 500;
    }

    .btn {
        padding: 15px 20px;
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        font-size: 15px;
        font-family: 'Poppins', sans-serif;
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
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    .btn-secondary {
        background: var(--background-light);
        color: var(--dark-text-color);
        border: 1px solid #e1e5e9;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .btn-danger {
        background: var(--error-color);
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626;
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .design-elements {
        margin-top: 20px;
        max-height: 200px;
        overflow-y: auto;
    }

    .design-element {
        display: flex;
        align-items: center;
        padding: 12px;
        background: var(--background-light);
        border-radius: 8px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: var(--transition);
        border: 2px solid transparent;
    }

    .design-element:hover {
        background: #e2e8f0;
        transform: translateX(5px);
    }

    .design-element.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--secondary-color);
    }

    .element-icon {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 18px;
        box-shadow: var(--shadow);
    }

    .element-info {
        flex: 1;
    }

    .element-info h4 {
        margin: 0 0 4px 0;
        font-size: 14px;
        font-weight: 600;
    }

    .element-info p {
        margin: 0;
        font-size: 12px;
        opacity: 0.8;
    }

    .element-actions {
        display: flex;
        gap: 8px;
    }

    .element-actions button {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
        opacity: 0.7;
        transition: opacity 0.2s;
        padding: 5px;
        border-radius: 4px;
    }

    .element-actions button:hover {
        opacity: 1;
        background: rgba(0,0,0,0.1);
    }

    footer {
        background: linear-gradient(135deg, var(--secondary-color), #1e3a8a);
        color: #e0f2fe;
        padding: 80px 20px 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
        margin-top: 60px;
    }

    footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" fill="%23ffffff" opacity="0.05"><circle cx="100" cy="100" r="2"/><circle cx="300" cy="300" r="3"/><circle cx="500" cy="150" r="2"/><circle cx="700" cy="400" r="4"/><circle cx="900" cy="250" r="2"/></svg>');
    }
    
    footer p {
        margin-bottom: 20px;
        font-size: 1.3rem;
        position: relative;
        z-index: 2;
    }

    footer .social-links {
        display: flex;
        justify-content: center;
        gap: 25px;
        margin: 30px 0 40px;
        position: relative;
        z-index: 2;
    }

    footer .social-links a {
        color: #bae6fd;
        text-decoration: none;
        font-size: 1.4rem;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 20px;
        background: rgba(255,255,255,0.1);
        border-radius: var(--border-radius);
        backdrop-filter: blur(10px);
    }

    footer .social-links a:hover {
        color: #fff;
        transform: translateY(-3px);
        background: rgba(255,255,255,0.2);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    .copyright {
        margin-top: 40px;
        font-size: 1em;
        color: #94a3b8;
        border-top: 1px solid rgba(255,255,255,0.2);
        padding-top: 30px;
        position: relative;
        z-index: 2;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        justify-content: center;
        align-items: center;
        z-index: 2000;
    }

    .modal-content {
        background: var(--card-bg);
        padding: 30px;
        border-radius: var(--border-radius);
        max-width: 500px;
        width: 90%;
        box-shadow: var(--shadow-lg);
        text-align: center;
    }

    .modal h3 {
        margin-top: 0;
        color: var(--secondary-color);
        font-size: 1.5rem;
    }

    .modal-actions {
        display: flex;
        gap: 12px;
        margin-top: 20px;
        justify-content: center;
    }

    .loading {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        justify-content: center;
        align-items: center;
        z-index: 3000;
    }

    .spinner {
        border: 5px solid #f3f3f3;
        border-top: 5px solid var(--primary-color);
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: var(--secondary-color);
        color: white;
        padding: 15px 25px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-lg);
        z-index: 2000;
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s ease;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .toast.show {
        transform: translateY(0);
        opacity: 1;
    }

    .toast.error {
        background: var(--error-color);
    }

    .toast.success {
        background: var(--success-color);
    }

    .text-style-controls {
        display: flex;
        gap: 8px;
        margin-bottom: 12px;
    }

    .text-style-btn {
        padding: 8px 12px;
        border: 1px solid #e1e5e9;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        transition: var(--transition);
        flex: 1;
    }

    .text-style-btn.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    .text-style-btn:hover {
        background: var(--light-bg);
    }

    .advanced-controls {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e1e5e9;
    }

    .control-slider {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .control-slider label {
        min-width: 80px;
        font-size: 14px;
        font-weight: 500;
    }

    .control-slider input[type="range"] {
        flex: 1;
    }

    .control-slider span {
        min-width: 40px;
        text-align: right;
        font-size: 14px;
    }

    .shape-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin-top: 10px;
    }

    .shape-option {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        cursor: pointer;
        border: 2px solid transparent;
        transition: transform 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        box-shadow: var(--shadow);
    }

    .shape-option:hover {
        transform: scale(1.1);
    }

    .shape-option.selected {
        border-color: var(--dark-text-color);
        transform: scale(1.1);
    }

    .layer-controls {
        display: flex;
        gap: 8px;
        margin-top: 10px;
    }

    .layer-btn {
        flex: 1;
        padding: 8px;
        border: 1px solid #e1e5e9;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: var(--transition);
    }

    .layer-btn:hover {
        background: var(--light-bg);
    }

    .element-properties {
        margin-top: 20px;
        padding: 20px;
        background: var(--background-light);
        border-radius: 8px;
        border-left: 4px solid var(--primary-color);
    }

    .element-properties h4 {
        margin: 0 0 12px 0;
        font-size: 14px;
        color: var(--secondary-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .element-properties .control-row {
        margin-bottom: 10px;
    }

    .element-properties .color-grid {
        margin-top: 5px;
    }

    .saved-designs-empty {
        text-align: center;
        padding: 40px 20px;
        color: var(--light-text-color);
    }

    .saved-designs-empty i {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .design-element .element-actions .load-saved-design {
        color: var(--primary-color);
    }

    .design-element .element-actions .delete-saved-design {
        color: var(--error-color);
    }

    .login-prompt {
        text-align: center;
        padding: 40px 20px;
        background: var(--background-light);
        border-radius: var(--border-radius);
        margin-bottom: 20px;
    }

    .login-prompt i {
        font-size: 48px;
        color: var(--primary-color);
        margin-bottom: 16px;
    }

    .login-prompt h3 {
        margin-bottom: 10px;
        color: var(--secondary-color);
    }

    .login-prompt p {
        margin-bottom: 20px;
        color: var(--light-text-color);
    }

    @media (max-width: 1200px) {
        .main-content {
            gap: 20px;
        }
    }

    @media (max-width: 992px) {
        .main-content {
            flex-direction: column;
        }
        
        .customization-panel {
            width: 100%;
        }
        
        .page-header h1 {
            font-size: 2.8rem;
        }
    }

    @media (max-width: 768px) {
        .container {
            padding: 20px 15px;
        }
        
        .page-header {
            padding: 30px 15px;
        }
        
        .page-header h1 {
            font-size: 2.2rem;
        }
        
        .preview-actions {
            flex-direction: column;
            width: 100%;
        }
        
        .preview-actions button {
            width: 100%;
            justify-content: center;
        }
        
        .panel-tabs {
            flex-wrap: wrap;
        }
        
        .panel-tab {
            flex: 1 0 50%;
        }
        
        .color-grid {
            grid-template-columns: repeat(4, 1fr);
        }
        
        .shape-grid {
            grid-template-columns: repeat(3, 1fr);
        }
        
        footer .social-links {
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
    }

    @media (max-width: 480px) {
        .page-header h1 {
            font-size: 1.8rem;
        }
        
        .product-info {
            padding: 20px 15px;
        }
        
        .product-price {
            font-size: 1.5rem;
        }
        
        .panel-tab {
            flex: 1 0 100%;
            padding: 12px;
        }
        
        .color-grid {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .shape-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .modal-actions {
            flex-direction: column;
        }
        
        .modal-actions .btn {
            width: 100%;
        }
    }
</style>
</head>
<body>
<header>
    <a href="index.php#home" class="logo-link">
        <div class="logo">
            <img src="images/rsz_logo.png" alt="Marcomedia Logo" class="logo-img">
            <h2>Marcomedia</h2>
        </div>
    </a>
    
    <nav>
        <a href="index.php#items"><i class="fas fa-box"></i> Products</a>
        <a href="index.php#photographer-booking"><i class="fas fa-camera"></i> Book Photographer</a>

        <div class="cart-dropdown">
            <span><i class="fas fa-shopping-cart"></i> Cart (<?php echo array_sum($_SESSION['cart'] ?? []); ?>)</span>
            <?php if(!empty($_SESSION['cart'])): ?>
                <div class="cart-items">
                    <?php
                    $ids = implode(',', array_keys($_SESSION['cart']));
                    $cartQuery = $conn->query("SELECT * FROM stock WHERE id IN ($ids)");
                    while($cartItem = $cartQuery->fetch_assoc()):
                        $qty = $_SESSION['cart'][$cartItem['id']];
                    ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($cartItem['image_path']); ?>" alt="<?php echo htmlspecialchars($cartItem['name']); ?>">
                        <div>
                            <strong><?php echo htmlspecialchars($cartItem['name']); ?></strong>
                            <div>x <?php echo $qty; ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <a href="my_cart.php" class="view-cart"><i class="fas fa-shopping-bag"></i> View Cart</a>
                </div>
            <?php endif; ?>
        </div>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <?php else: ?>
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-palette"></i> Design Your <?php echo htmlspecialchars($item['name']); ?></h1>
        <p>Create a unique design with our professional 3D customization tool</p>
    </div>

    <div class="product-info">
        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
        <p class="product-price">₱<?php echo number_format($item['price'], 2); ?></p>
        <p><?php echo htmlspecialchars($item['description'] ?? 'Customize this product with your own design in 3D'); ?></p>
    </div>

    <div class="main-content">
        <section class="product-preview">
            <div class="preview-header">
                <h2>3D Design Preview</h2>
                <div class="preview-actions">
                    <button id="resetViewBtn"><i class="fas fa-sync-alt"></i> Reset View</button>
                    <button id="toggleControlsBtn"><i class="fas fa-gamepad"></i> Toggle Controls</button>
                </div>
            </div>
            <div class="preview-container">
                <canvas id="threejsCanvas"></canvas>
                <div class="view-controls">
                    <button id="frontViewBtn"><i class="fas fa-cube"></i> Front</button>
                    <button id="sideViewBtn"><i class="fas fa-undo"></i> Side</button>
                    <button id="topViewBtn"><i class="fas fa-arrow-up"></i> Top</button>
                </div>
            </div>
        </section>

        <section class="customization-panel">
            <div class="panel-tabs">
                <button class="panel-tab active" data-tab="design">Design</button>
                <button class="panel-tab" data-tab="text">Text</button>
                <button class="panel-tab" data-tab="images">Images</button>
                <button class="panel-tab" data-tab="elements">Elements</button>
                <button class="panel-tab" data-tab="saved">Saved Designs</button>
            </div>
            
            <div class="panel-content">
                <div class="tab-content active" id="design-tab">
                    <div class="control-group">
                        <h3><i class="fas fa-fill-drip"></i> Product Colors</h3>
                        <div class="color-grid" id="productColorGrid">
                            <div class="color-option selected" style="background-color: #ffffff;" data-color="#ffffff"></div>
                            <div class="color-option" style="background-color: #000000;" data-color="#000000"></div>
                            <div class="color-option" style="background-color: #2563eb;" data-color="#2563eb"></div>
                            <div class="color-option" style="background-color: #10b981;" data-color="#10b981"></div>
                            <div class="color-option" style="background-color: #ef4444;" data-color="#ef4444"></div>
                            <div class="color-option" style="background-color: #f59e0b;" data-color="#f59e0b"></div>
                            <div class="color-option" style="background-color: #8b5cf6;" data-color="#8b5cf6"></div>
                            <div class="color-option" style="background-color: #f97316;" data-color="#f97316"></div>
                            <div class="color-option" style="background-color: #1f2937;" data-color="#1f2937"></div>
                            <div class="color-option" style="background-color: #9ca3af;" data-color="#9ca3af"></div>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <h3><i class="fas fa-paint-brush"></i> Custom Color</h3>
                        <div class="control-row">
                            <label>Color:</label>
                            <input type="color" id="colorPicker" value="#2563eb">
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <h3><i class="fas fa-cube"></i> 3D View Options</h3>
                        <div class="control-row">
                            <label>Rotation:</label>
                            <input type="range" id="autoRotateSpeed" min="0" max="10" step="1" value="0">
                            <span id="rotateSpeedValue">Off</span>
                        </div>
                        <div class="control-row">
                            <label>Zoom:</label>
                            <input type="range" id="zoomLevel" min="0.5" max="2" step="0.1" value="1">
                            <span id="zoomLevelValue">100%</span>
                        </div>
                    </div>
                    
                    <button class="btn btn-secondary" id="resetDesignBtn">
                        <i class="fas fa-undo"></i> Reset Design
                    </button>
                </div>
                
                <div class="tab-content" id="text-tab">
                    <div class="control-group">
                        <h3><i class="fas fa-font"></i> Add Text</h3>
                        <div class="control-row">
                            <label>Text:</label>
                            <input type="text" id="textInput" placeholder="Enter your text">
                        </div>
                        <div class="control-row">
                            <label>Font:</label>
                            <select id="fontSelect">
                                <option value="Arial">Arial</option>
                                <option value="Verdana">Verdana</option>
                                <option value="Georgia">Georgia</option>
                                <option value="Times New Roman">Times New Roman</option>
                                <option value="Courier New">Courier New</option>
                                <option value="Impact">Impact</option>
                                <option value="Comic Sans MS">Comic Sans MS</option>
                                <option value="Poppins">Poppins</option>
                                <option value="Roboto">Roboto</option>
                                <option value="Montserrat">Montserrat</option>
                                <option value="Open Sans">Open Sans</option>
                                <option value="Lato">Lato</option>
                            </select>
                        </div>
                        <div class="control-row">
                            <label>Size:</label>
                            <input type="range" id="fontSize" min="10" max="72" value="24">
                            <span id="fontSizeValue">24px</span>
                        </div>
                        <div class="control-row">
                            <label>Color:</label>
                            <input type="color" id="textColor" value="#000000">
                        </div>
                        
                        <div class="text-style-controls">
                            <button class="text-style-btn" id="boldBtn" title="Bold"><i class="fas fa-bold"></i></button>
                            <button class="text-style-btn" id="italicBtn" title="Italic"><i class="fas fa-italic"></i></button>
                            <button class="text-style-btn" id="underlineBtn" title="Underline"><i class="fas fa-underline"></i></button>
                        </div>
                        
                        <div class="control-row">
                            <button class="btn btn-secondary" id="addTextBtn">
                                <i class="fas fa-plus"></i> Add Text to Design
                            </button>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <h3><i class="fas fa-text-width"></i> Text Elements</h3>
                        <div class="design-elements" id="textElements">
                        </div>
                    </div>
                </div>
                
                <div class="tab-content" id="images-tab">
                    <div class="control-group">
                        <h3><i class="fas fa-image"></i> Upload Image</h3>
                        <div class="upload-area" id="uploadTrigger">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Drag & drop your image here or <span>click to browse</span></p>
                        </div>
                        <input type="file" id="imageUpload" accept="image/*" style="display: none;">
                        <p style="font-size: 12px; color: var(--light-text-color); text-align: center;">
                            Recommended: PNG with transparent background, max 5MB
                        </p>
                    </div>
                    
                    <div class="control-group">
                        <h3><i class="fas fa-images"></i> Image Elements</h3>
                        <div class="design-elements" id="imageElements">
                        </div>
                    </div>
                </div>
                
                <div class="tab-content" id="elements-tab">
                    <div class="control-group">
                        <h3><i class="fas fa-palette"></i> Element Color</h3>
                        <div class="control-row">
                            <label>Color:</label>
                            <input type="color" id="shapeColorPicker" value="#2563eb">
                        </div>
                        <div class="color-grid" id="shapeColorGrid">
                            <div class="color-option selected" style="background-color: #2563eb;" data-color="#2563eb"></div>
                            <div class="color-option" style="background-color: #ef4444;" data-color="#ef4444"></div>
                            <div class="color-option" style="background-color: #10b981;" data-color="#10b981"></div>
                            <div class="color-option" style="background-color: #f59e0b;" data-color="#f59e0b"></div>
                            <div class="color-option" style="background-color: #8b5cf6;" data-color="#8b5cf6"></div>
                            <div class="color-option" style="background-color: #f97316;" data-color="#f97316"></div>
                            <div class="color-option" style="background-color: #1f2937;" data-color="#1f2937"></div>
                            <div class="color-option" style="background-color: #ffffff;" data-color="#ffffff"></div>
                            <div class="color-option" style="background-color: #000000;" data-color="#000000"></div>
                            <div class="color-option" style="background-color: #9ca3af;" data-color="#9ca3af"></div>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <h3><i class="fas fa-shapes"></i> Design Elements</h3>
                        <div class="shape-grid" id="shapeElementsGrid">
                            <div class="shape-option" data-type="heart" style="background-color: #ef4444;">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="shape-option" data-type="star" style="background-color: #10b981;">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="shape-option" data-type="circle" style="background-color: #2563eb;">
                                <i class="fas fa-circle"></i>
                            </div>
                            <div class="shape-option" data-type="square" style="background-color: #f59e0b;">
                                <i class="fas fa-square"></i>
                            </div>
                            <div class="shape-option" data-type="cloud" style="background-color: #8b5cf6;">
                                <i class="fas fa-cloud"></i>
                            </div>
                            <div class="shape-option" data-type="arrow" style="background-color: #f97316;">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                            <div class="shape-option" data-type="triangle" style="background-color: #1f2937;">
                                <i class="fas fa-play fa-rotate-270"></i>
                            </div>
                            <div class="shape-option" data-type="hexagon" style="background-color: #9ca3af;">
                                <i class="fas fa-hexagon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <h3><i class="fas fa-object-group"></i> Added Elements</h3>
                        <div class="design-elements" id="shapeElements">
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="saved-tab">
                    <?php if (!$user_id): ?>
                    <div class="login-prompt">
                        <i class="fas fa-user-lock"></i>
                        <h3>Login Required</h3>
                        <p>Please log in to save and load your custom designs.</p>
                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login Now
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="control-group">
                        <h3><i class="fas fa-save"></i> Save Current Design</h3>
                        <div class="control-row">
                            <label>Design Name:</label>
                            <input type="text" id="designName" placeholder="Enter design name" maxlength="255">
                        </div>
                        <button class="btn btn-primary" id="saveDesignBtn">
                            <i class="fas fa-save"></i> Save Design
                        </button>
                    </div>
                    
                    <div class="control-group">
                        <h3><i class="fas fa-folder-open"></i> Your Saved Designs</h3>
                        <div id="savedDesignsList">
                            <div style="text-align: center; padding: 20px; color: var(--light-text-color);">
                                <i class="fas fa-spinner fa-spin"></i> Loading your designs...
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="control-group" style="margin-top: 30px;">
                    <button class="btn btn-primary" id="addToCartBtn">
                        <i class="fas fa-shopping-cart"></i> Add to Cart - ₱<?php echo number_format($item['price'], 2); ?>
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>

<div class="modal" id="backConfirmModal">
    <div class="modal-content">
        <h3>Leave Design Editor?</h3>
        <p>Your current design has not been saved. Are you sure you want to leave?</p>
        <div class="modal-actions">
            <button class="btn btn-danger" id="confirmBack">Yes, Leave</button>
            <button class="btn btn-secondary" id="cancelBack">Cancel</button>
        </div>
    </div>
</div>

<div class="loading" id="loadingSpinner">
    <div class="spinner"></div>
</div>

<div class="toast" id="toastNotification"></div>

<footer id="footer">
    <p>Marcomedia POS | <strong>Where Memories Become Masterpieces</strong></p>
    
    <div class="social-links">
        <a href="#"><i class="fab fa-facebook-f"></i> Facebook</a>
        <a href="#"><i class="fab fa-instagram"></i> Instagram</a>
        <a href="#"><i class="fab fa-tiktok"></i> TikTok</a>
        <a href="#"><i class="fab fa-youtube"></i> YouTube</a>
        <a href="mailto:info@marcomedia.com"><i class="fas fa-envelope"></i> Email Us</a>
    </div>
    
    <p class="copyright">&copy; <?php echo date('Y'); ?> Marcomedia POS. All rights reserved. | Crafted with ❤️ for amazing experiences</p>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.min.js"></script>

<script>
// Three.js 3D Scene
let scene, camera, renderer, controls;
let productMesh, currentColor = '#ffffff';
let texts = [], images = [], shapes = [];
let selectedElement = null;

// Initialize Three.js scene
function initThreeJS() {
    // Scene
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x1a1a1a);

    // Camera
    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.z = 5;

    // Renderer
    const canvas = document.getElementById('threejsCanvas');
    renderer = new THREE.WebGLRenderer({ canvas, antialias: true });
    renderer.setSize(canvas.clientWidth, canvas.clientHeight);
    renderer.setPixelRatio(window.devicePixelRatio);

    // Controls
    controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;

    // Lighting
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    scene.add(ambientLight);

    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(10, 10, 5);
    scene.add(directionalLight);

    // Create product based on type
    createProduct('<?php echo $product_type; ?>');

    // Handle window resize
    window.addEventListener('resize', onWindowResize);

    // Start animation loop
    animate();
}

function createProduct(type) {
    // Remove existing product
    if (productMesh) {
        scene.remove(productMesh);
    }

    let geometry, material;

    switch(type) {
        case 'shirt':
            geometry = new THREE.BoxGeometry(3, 4, 0.2);
            break;
        case 'mug':
            geometry = new THREE.CylinderGeometry(1, 1, 3, 32);
            break;
        case 'tumbler':
            geometry = new THREE.CylinderGeometry(0.8, 0.8, 4, 32);
            break;
        case 'cap':
            geometry = new THREE.SphereGeometry(1.5, 32, 32);
            break;
        default:
            geometry = new THREE.BoxGeometry(2, 2, 2);
    }

    material = new THREE.MeshPhongMaterial({ 
        color: currentColor,
        shininess: 30
    });

    productMesh = new THREE.Mesh(geometry, material);
    scene.add(productMesh);
}

function onWindowResize() {
    const canvas = document.getElementById('threejsCanvas');
    camera.aspect = canvas.clientWidth / canvas.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(canvas.clientWidth, canvas.clientHeight);
}

function animate() {
    requestAnimationFrame(animate);
    controls.update();
    renderer.render(scene, camera);
}

// Show toast notification
function showToast(message, isError = false) {
    const toast = document.getElementById('toastNotification');
    toast.textContent = message;
    toast.className = 'toast' + (isError ? ' error' : ' success');
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

function showLoading() {
    document.getElementById('loadingSpinner').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loadingSpinner').style.display = 'none';
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize 3D scene
    initThreeJS();
    
    // Tab switching
    document.querySelectorAll('.panel-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.panel-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            tab.classList.add('active');
            document.getElementById(`${tab.dataset.tab}-tab`).classList.add('active');
            
            if (tab.dataset.tab === 'saved' && <?php echo $user_id ? 'true' : 'false'; ?>) {
                loadSavedDesigns();
            }
        });
    });

    // Product color selection
    document.querySelectorAll('#productColorGrid .color-option').forEach(option => {
        option.addEventListener('click', () => {
            document.querySelectorAll('#productColorGrid .color-option').forEach(o => o.classList.remove('selected'));
            option.classList.add('selected');
            currentColor = option.dataset.color;
            document.getElementById('colorPicker').value = currentColor;
            updateProductColor(currentColor);
        });
    });

    document.getElementById('colorPicker').addEventListener('change', e => {
        currentColor = e.target.value;
        updateProductColor(currentColor);
    });

    function updateProductColor(color) {
        if (productMesh) {
            productMesh.material.color.set(color);
        }
    }

    // Shape color selection
    document.querySelectorAll('#shapeColorGrid .color-option').forEach(option => {
        option.addEventListener('click', () => {
            document.querySelectorAll('#shapeColorGrid .color-option').forEach(o => o.classList.remove('selected'));
            option.classList.add('selected');
            currentColor = option.dataset.color;
            document.getElementById('shapeColorPicker').value = currentColor;
        });
    });

    document.getElementById('shapeColorPicker').addEventListener('change', e => {
        currentColor = e.target.value;
    });

    // 3D View controls
    document.getElementById('autoRotateSpeed').addEventListener('input', e => {
        const speed = parseInt(e.target.value);
        document.getElementById('rotateSpeedValue').textContent = speed === 0 ? 'Off' : `${speed}/10`;
        // Auto-rotation would be implemented here
    });

    document.getElementById('zoomLevel').addEventListener('input', e => {
        const zoom = parseFloat(e.target.value);
        document.getElementById('zoomLevelValue').textContent = `${Math.round(zoom * 100)}%`;
        camera.zoom = zoom;
        camera.updateProjectionMatrix();
    });

    // View control buttons
    document.getElementById('frontViewBtn').addEventListener('click', () => {
        camera.position.set(0, 0, 5);
        controls.update();
    });

    document.getElementById('sideViewBtn').addEventListener('click', () => {
        camera.position.set(5, 0, 0);
        controls.update();
    });

    document.getElementById('topViewBtn').addEventListener('click', () => {
        camera.position.set(0, 5, 0);
        controls.update();
    });

    document.getElementById('resetViewBtn').addEventListener('click', () => {
        camera.position.set(0, 0, 5);
        controls.reset();
        document.getElementById('autoRotateSpeed').value = 0;
        document.getElementById('rotateSpeedValue').textContent = 'Off';
        document.getElementById('zoomLevel').value = 1;
        document.getElementById('zoomLevelValue').textContent = '100%';
        camera.zoom = 1;
        camera.updateProjectionMatrix();
    });

    document.getElementById('toggleControlsBtn').addEventListener('click', () => {
        controls.enabled = !controls.enabled;
        showToast(controls.enabled ? 'Controls enabled' : 'Controls disabled');
    });

    // Text controls
    document.getElementById('fontSize').addEventListener('input', e => {
        document.getElementById('fontSizeValue').textContent = `${e.target.value}px`;
    });

    document.getElementById('boldBtn').addEventListener('click', function() {
        this.classList.toggle('active');
    });

    document.getElementById('italicBtn').addEventListener('click', function() {
        this.classList.toggle('active');
    });

    document.getElementById('underlineBtn').addEventListener('click', function() {
        this.classList.toggle('active');
    });

    document.getElementById('addTextBtn').addEventListener('click', () => {
        const text = document.getElementById('textInput').value.trim();
        if(!text) {
            showToast('Please enter some text', true);
            return;
        }
        
        const textObj = {
            type: 'text',
            text,
            color: document.getElementById('textColor').value,
            font: document.getElementById('fontSelect').value,
            size: parseInt(document.getElementById('fontSize').value),
            bold: document.getElementById('boldBtn').classList.contains('active'),
            italic: document.getElementById('italicBtn').classList.contains('active'),
            underline: document.getElementById('underlineBtn').classList.contains('active'),
            id: Date.now()
        };
        
        texts.push(textObj);
        addTextElement(textObj);
        document.getElementById('textInput').value = '';
        showToast('Text added to design');
        
        // In a real implementation, you would add 3D text to the scene
        addTextTo3DScene(textObj);
    });

    function addTextTo3DScene(textObj) {
        // This is a simplified version - in production you'd use Three.js text geometries
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        canvas.width = 256;
        canvas.height = 128;
        
        context.fillStyle = textObj.color;
        context.font = `${textObj.bold ? 'bold ' : ''}${textObj.size}px ${textObj.font}`;
        context.fillText(textObj.text, 10, 50);
        
        const texture = new THREE.CanvasTexture(canvas);
        const material = new THREE.MeshBasicMaterial({ map: texture, transparent: true });
        const geometry = new THREE.PlaneGeometry(2, 1);
        const textMesh = new THREE.Mesh(geometry, material);
        
        textMesh.position.set(0, 0, 1.1);
        textMesh.userData = { type: 'text', id: textObj.id };
        scene.add(textMesh);
    }

    // Image upload
    document.getElementById('uploadTrigger').addEventListener('click', () => {
        document.getElementById('imageUpload').click();
    });

    document.getElementById('imageUpload').addEventListener('change', function(e) {
        const file = e.target.files[0]; 
        if(!file) return;
        
        if (file.size > 5 * 1024 * 1024) {
            showToast('File size must be less than 5MB', true);
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = () => {
                const imgObj = {
                    type: 'image',
                    base64: e.target.result,
                    width: img.width,
                    height: img.height,
                    id: Date.now()
                };
                
                images.push(imgObj);
                addImageElement(imgObj);
                showToast('Image added to design');
                
                // Add image to 3D scene
                addImageTo3DScene(imgObj);
            };
            img.onerror = () => {
                showToast('Failed to load image', true);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

    function addImageTo3DScene(imgObj) {
        const texture = new THREE.TextureLoader().load(imgObj.base64);
        const material = new THREE.MeshBasicMaterial({ map: texture, transparent: true });
        const geometry = new THREE.PlaneGeometry(2, 2);
        const imageMesh = new THREE.Mesh(geometry, material);
        
        imageMesh.position.set(1, 1, 1.1);
        imageMesh.userData = { type: 'image', id: imgObj.id };
        scene.add(imageMesh);
    }

    // Shape elements
    document.querySelectorAll('#shapeElementsGrid .shape-option').forEach(option => {
        option.addEventListener('click', function() {
            const type = this.dataset.type;
            const shapeObj = {
                type: 'shape',
                shapeType: type,
                color: currentColor,
                id: Date.now()
            };
            
            shapes.push(shapeObj);
            addShapeElement(shapeObj);
            showToast('Shape added to design');
            
            // Add shape to 3D scene
            addShapeTo3DScene(shapeObj);
        });
    });

    function addShapeTo3DScene(shapeObj) {
        let geometry;
        switch(shapeObj.shapeType) {
            case 'heart':
            case 'star':
                geometry = new THREE.SphereGeometry(0.3, 32, 32);
                break;
            case 'circle':
                geometry = new THREE.CircleGeometry(0.3, 32);
                break;
            case 'square':
                geometry = new THREE.BoxGeometry(0.5, 0.5, 0.1);
                break;
            default:
                geometry = new THREE.SphereGeometry(0.3, 32, 32);
        }
        
        const material = new THREE.MeshPhongMaterial({ color: shapeObj.color });
        const shapeMesh = new THREE.Mesh(geometry, material);
        
        shapeMesh.position.set(-1, -1, 1.1);
        shapeMesh.userData = { type: 'shape', id: shapeObj.id };
        scene.add(shapeMesh);
    }

    // Reset design
    document.getElementById('resetDesignBtn').addEventListener('click', () => {
        if(confirm('Are you sure you want to reset your design? This cannot be undone.')) {
            texts = [];
            images = [];
            shapes = [];
            selectedElement = null;
            document.getElementById('textElements').innerHTML = '';
            document.getElementById('imageElements').innerHTML = '';
            document.getElementById('shapeElements').innerHTML = '';
            
            // Remove all user-added objects from scene
            scene.children.forEach(child => {
                if (child.userData && (child.userData.type === 'text' || child.userData.type === 'image' || child.userData.type === 'shape')) {
                    scene.remove(child);
                }
            });
            
            showToast('Design reset successfully');
        }
    });

    // Add to cart
    document.getElementById('addToCartBtn').addEventListener('click', () => {
        showLoading();
        
        // Capture design data
        const designData = getDesignData();
        
        const formData = new FormData();
        formData.append('item_id', <?php echo $item['id']; ?>);
        formData.append('quantity', 1);
        formData.append('design', designData);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            hideLoading();
            if (data.status === 'success') {
                showToast('Design added to cart successfully!');
                setTimeout(() => {
                    window.location.href = 'my_cart.php';
                }, 1500);
            } else {
                showToast('Failed to add to cart: ' + data.message, true);
            }
        })
        .catch(error => {
            hideLoading();
            showToast('Failed to add to cart: Server error', true);
            console.error('Error:', error);
        });
    });

    // Load saved designs if user is logged in
    <?php if ($user_id): ?>
    if (document.querySelector('.panel-tab[data-tab="saved"]').classList.contains('active')) {
        loadSavedDesigns();
    }
    <?php endif; ?>
});

// Helper functions for design elements
function addTextElement(textObj) {
    const textElements = document.getElementById('textElements');
    const element = document.createElement('div');
    element.className = 'design-element';
    element.dataset.id = textObj.id;
    
    element.innerHTML = `
        <div class="element-icon">
            <i class="fas fa-font"></i>
        </div>
        <div class="element-info">
            <h4>${textObj.text.substring(0, 15)}${textObj.text.length > 15 ? '...' : ''}</h4>
            <p>${textObj.font}, ${textObj.size}px</p>
        </div>
        <div class="element-actions">
            <button class="edit-text"><i class="fas fa-edit"></i></button>
            <button class="delete-text"><i class="fas fa-trash"></i></button>
        </div>
    `;
    
    element.addEventListener('click', () => {
        selectedElement = textObj;
        document.querySelectorAll('.design-element').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
    });
    
    element.querySelector('.edit-text').addEventListener('click', (e) => {
        e.stopPropagation();
        document.getElementById('textInput').value = textObj.text;
        document.getElementById('fontSelect').value = textObj.font;
        document.getElementById('fontSize').value = textObj.size;
        document.getElementById('fontSizeValue').textContent = `${textObj.size}px`;
        document.getElementById('textColor').value = textObj.color;
        
        document.getElementById('boldBtn').classList.toggle('active', textObj.bold);
        document.getElementById('italicBtn').classList.toggle('active', textObj.italic);
        document.getElementById('underlineBtn').classList.toggle('active', textObj.underline);
        
        document.querySelectorAll('.panel-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.querySelector('.panel-tab[data-tab="text"]').classList.add('active');
        document.getElementById('text-tab').classList.add('active');
    });
    
    element.querySelector('.delete-text').addEventListener('click', (e) => {
        e.stopPropagation();
        texts = texts.filter(t => t.id !== textObj.id);
        element.remove();
        if (selectedElement === textObj) {
            selectedElement = null;
        }
        
        // Remove from 3D scene
        scene.children.forEach(child => {
            if (child.userData && child.userData.id === textObj.id) {
                scene.remove(child);
            }
        });
        
        showToast('Text element removed');
    });
    
    textElements.appendChild(element);
}

function addImageElement(imgObj) {
    const imageElements = document.getElementById('imageElements');
    const element = document.createElement('div');
    element.className = 'design-element';
    element.dataset.id = imgObj.id;
    
    element.innerHTML = `
        <div class="element-icon">
            <i class="fas fa-image"></i>
        </div>
        <div class="element-info">
            <h4>Uploaded Image</h4>
            <p>${imgObj.width}×${imgObj.height}px</p>
        </div>
        <div class="element-actions">
            <button class="delete-image"><i class="fas fa-trash"></i></button>
        </div>
    `;
    
    element.addEventListener('click', () => {
        selectedElement = imgObj;
        document.querySelectorAll('.design-element').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
    });
    
    element.querySelector('.delete-image').addEventListener('click', (e) => {
        e.stopPropagation();
        images = images.filter(i => i.id !== imgObj.id);
        element.remove();
        if (selectedElement === imgObj) {
            selectedElement = null;
        }
        
        // Remove from 3D scene
        scene.children.forEach(child => {
            if (child.userData && child.userData.id === imgObj.id) {
                scene.remove(child);
            }
        });
        
        showToast('Image element removed');
    });
    
    imageElements.appendChild(element);
}

function addShapeElement(shapeObj) {
    const shapeElements = document.getElementById('shapeElements');
    const element = document.createElement('div');
    element.className = 'design-element';
    element.dataset.id = shapeObj.id;
    
    let typeName = shapeObj.shapeType.charAt(0).toUpperCase() + shapeObj.shapeType.slice(1);
    
    element.innerHTML = `
        <div class="element-icon" style="background-color: ${shapeObj.color};">
            <i class="fas fa-${shapeObj.shapeType}" style="color: white;"></i>
        </div>
        <div class="element-info">
            <h4>${typeName}</h4>
            <p>${shapeObj.color}</p>
        </div>
        <div class="element-actions">
            <button class="delete-shape"><i class="fas fa-trash"></i></button>
        </div>
    `;
    
    element.addEventListener('click', () => {
        selectedElement = shapeObj;
        document.querySelectorAll('.design-element').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
    });
    
    element.querySelector('.delete-shape').addEventListener('click', (e) => {
        e.stopPropagation();
        shapes = shapes.filter(s => s.id !== shapeObj.id);
        element.remove();
        if (selectedElement === shapeObj) {
            selectedElement = null;
        }
        
        // Remove from 3D scene
        scene.children.forEach(child => {
            if (child.userData && child.userData.id === shapeObj.id) {
                scene.remove(child);
            }
        });
        
        showToast('Shape element removed');
    });
    
    shapeElements.appendChild(element);
}

function getDesignData() {
    const designData = {
        texts: texts,
        shapes: shapes,
        images: images,
        productColor: currentColor,
        productType: '<?php echo $product_type; ?>'
    };
    
    return JSON.stringify(designData);
}

// The rest of the functions (loadDesignData, saveDesign, loadSavedDesigns, loadDesign, deleteDesign) remain the same as before
// They are omitted for brevity but should be included in the actual implementation

// Event listeners for save design
document.getElementById('saveDesignBtn').addEventListener('click', saveDesign);
document.getElementById('designName').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        saveDesign();
    }
});

function saveDesign() {
    <?php if (!$user_id): ?>
    showToast('Please log in to save designs', true);
    return;
    <?php else: ?>
    const designName = document.getElementById('designName').value.trim();
    if (!designName) {
        showToast('Please enter a design name', true);
        return;
    }
    
    showLoading();
    const designData = getDesignData();
    
    const formData = new FormData();
    formData.append('design_action', 'save_design');
    formData.append('item_id', <?php echo $item['id']; ?>);
    formData.append('design_name', designName);
    formData.append('design_data', designData);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            showToast('Design saved successfully!');
            document.getElementById('designName').value = '';
            loadSavedDesigns();
        } else {
            showToast('Failed to save design: ' + data.message, true);
        }
    })
    .catch(error => {
        hideLoading();
        showToast('Failed to save design: Server error', true);
        console.error('Error:', error);
    });
    <?php endif; ?>
}

function loadSavedDesigns() {
    <?php if (!$user_id): ?>
    showToast('Please log in to load saved designs', true);
    return;
    <?php else: ?>
    const designsList = document.getElementById('savedDesignsList');
    designsList.innerHTML = '<div style="text-align: center; padding: 10px; color: var(--light-text-color);"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    
    const formData = new FormData();
    formData.append('design_action', 'get_designs');
    formData.append('item_id', <?php echo $item['id']; ?>);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const designs = data.designs;
            if (designs.length === 0) {
                designsList.innerHTML = '<div style="text-align: center; padding: 40px 20px; color: var(--light-text-color);"><i class="fas fa-palette" style="font-size: 32px; margin-bottom: 10px;"></i><p>No saved designs yet</p></div>';
                return;
            }
            
            let html = '';
            designs.forEach(design => {
                const date = new Date(design.created_at).toLocaleDateString();
                html += `
                    <div class="design-element">
                        <div class="element-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <div class="element-info">
                            <h4>${design.design_name}</h4>
                            <p>Saved: ${date}</p>
                        </div>
                        <div class="element-actions">
                            <button class="load-saved-design" data-id="${design.id}" title="Load Design">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="delete-saved-design" data-id="${design.id}" title="Delete Design">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            designsList.innerHTML = html;
            
            designsList.querySelectorAll('.load-saved-design').forEach(btn => {
                btn.addEventListener('click', function() {
                    loadDesign(this.dataset.id);
                });
            });
            
            designsList.querySelectorAll('.delete-saved-design').forEach(btn => {
                btn.addEventListener('click', function() {
                    deleteDesign(this.dataset.id);
                });
            });
            
        } else {
            designsList.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--error-color);">Error loading designs</div>';
        }
    })
    .catch(error => {
        designsList.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--error-color);">Error loading designs</div>';
        console.error('Error:', error);
    });
    <?php endif; ?>
}

function loadDesign(designId) {
    <?php if (!$user_id): ?>
    showToast('Please log in to load designs', true);
    return;
    <?php else: ?>
    showLoading();
    
    const formData = new FormData();
    formData.append('design_action', 'load_design');
    formData.append('item_id', <?php echo $item['id']; ?>);
    formData.append('design_id', designId);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            loadDesignData(data.design_data);
            document.querySelectorAll('.panel-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelector('.panel-tab[data-tab="design"]').classList.add('active');
            document.getElementById('design-tab').classList.add('active');
        } else {
            showToast('Failed to load design: ' + data.message, true);
        }
    })
    .catch(error => {
        hideLoading();
        showToast('Failed to load design: Server error', true);
        console.error('Error:', error);
    });
    <?php endif; ?>
}

function deleteDesign(designId) {
    <?php if (!$user_id): ?>
    showToast('Please log in to delete designs', true);
    return;
    <?php else: ?>
    if (!confirm('Are you sure you want to delete this design? This action cannot be undone.')) {
        return;
    }
    
    showLoading();
    
    const formData = new FormData();
    formData.append('design_action', 'delete_design');
    formData.append('item_id', <?php echo $item['id']; ?>);
    formData.append('design_id', designId);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            showToast('Design deleted successfully');
            loadSavedDesigns();
        } else {
            showToast('Failed to delete design: ' + data.message, true);
        }
    })
    .catch(error => {
        hideLoading();
        showToast('Failed to delete design: Server error', true);
        console.error('Error:', error);
    });
    <?php endif; ?>
}

function loadDesignData(designData) {
    try {
        const data = JSON.parse(designData);
        
        // Reset current design
        texts = [];
        images = [];
        shapes = [];
        selectedElement = null;
        
        document.getElementById('textElements').innerHTML = '';
        document.getElementById('imageElements').innerHTML = '';
        document.getElementById('shapeElements').innerHTML = '';
        
        // Remove all user-added objects from scene
        scene.children.forEach(child => {
            if (child.userData && (child.userData.type === 'text' || child.userData.type === 'image' || child.userData.type === 'shape')) {
                scene.remove(child);
            }
        });
        
        if (data.texts) {
            texts = data.texts;
            texts.forEach(textObj => {
                addTextElement(textObj);
                addTextTo3DScene(textObj);
            });
        }
        
        if (data.shapes) {
            shapes = data.shapes;
            shapes.forEach(shapeObj => {
                addShapeElement(shapeObj);
                addShapeTo3DScene(shapeObj);
            });
        }
        
        if (data.images) {
            images = data.images;
            images.forEach(imgObj => {
                addImageElement(imgObj);
                addImageTo3DScene(imgObj);
            });
        }
        
        if (data.productColor) {
            currentColor = data.productColor;
            document.getElementById('colorPicker').value = currentColor;
            document.querySelectorAll('#productColorGrid .color-option').forEach(option => {
                option.classList.remove('selected');
                if (option.dataset.color === currentColor) {
                    option.classList.add('selected');
                }
            });
            updateProductColor(currentColor);
        }
        
        showToast('Design loaded successfully');
        
    } catch (error) {
        console.error('Error loading design:', error);
        showToast('Error loading design', true);
    }
}

// Prevent leaving page with unsaved changes
window.addEventListener('beforeunload', function (e) {
    if (texts.length > 0 || images.length > 0 || shapes.length > 0) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>
</body>
</html>