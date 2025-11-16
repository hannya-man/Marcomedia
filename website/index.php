<?php
session_name('website_session');
session_start();
require __DIR__ . '/../db.php';

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// -------------------------------
// Initialize Cart
// -------------------------------
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// -------------------------------
// Fetch User Notifications - FIXED: Better text visibility and mark as read
// -------------------------------
$userNotifications = [];
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $notification_stmt = $conn->prepare("
        SELECT * FROM user_notifications 
        WHERE user_id = ? AND is_read = 0 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    if ($notification_stmt !== false) {
        $notification_stmt->bind_param("i", $uid);
        $notification_stmt->execute();
        $notification_result = $notification_stmt->get_result();
        while ($row = $notification_result->fetch_assoc()) {
            $userNotifications[] = $row;
        }
        $notification_stmt->close();
    }
}

// -------------------------------
// Handle Mark Notification as Read - FIXED: Proper implementation
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_notification_read'])) {
    if (isset($_SESSION['user_id'])) {
        $notification_id = intval($_POST['notification_id']);
        $mark_read_stmt = $conn->prepare("UPDATE user_notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $mark_read_stmt->bind_param("ii", $notification_id, $_SESSION['user_id']);
        $mark_read_stmt->execute();
        $mark_read_stmt->close();
        
        header("Location: index.php");
        exit;
    }
}

// -------------------------------
// Security Functions
// -------------------------------
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_name($name) {
    return preg_match("/^[a-zA-Z\s.'-]{2,50}$/", $name);
}

function validate_time_slot($start_time, $end_time) {
    $start = strtotime($start_time);
    $end = strtotime($end_time);
    return $end > $start && ($end - $start) <= (12 * 3600); // Max 12 hours
}

// -------------------------------
// FIXED: AJAX HANDLING FOR PRODUCT FILTERS - IMPROVED SEARCH WITH SPACES
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    // This is an AJAX request for products
    ob_clean(); // Clear any previous output
    
    // Get filter parameters - FIXED: Proper handling of spaces in category and search
    $search_query = isset($_POST['search']) ? sanitize_input($_POST['search']) : "";
    $category_filter = isset($_POST['category']) ? sanitize_input($_POST['category']) : "";
    
    // Build WHERE conditions
    $where_conditions = ["status='active'"];
    
    if (!empty($search_query)) {
        // FIXED: Improved search with better space handling
        $search_terms = preg_split('/\s+/', $search_query);
        $search_conditions = [];
        foreach ($search_terms as $term) {
            if (!empty(trim($term))) {
                $safe_term = $conn->real_escape_string(trim($term));
                $search_conditions[] = "(name LIKE '%$safe_term%' OR description LIKE '%$safe_term%')";
            }
        }
        if (!empty($search_conditions)) {
            $where_conditions[] = "(" . implode(' AND ', $search_conditions) . ")";
        }
    }
    
    // Check if category column exists
    $stock_columns_check = $conn->query("SHOW COLUMNS FROM stock");
    $stock_columns = [];
    while ($column = $stock_columns_check->fetch_assoc()) {
        $stock_columns[] = $column['Field'];
    }
    $category_column_exists = in_array('category', $stock_columns);
    
    // FIXED: Proper category filtering with spaces - use exact matching
    if ($category_column_exists && !empty($category_filter)) {
        $safe_category = $conn->real_escape_string(trim($category_filter));
        $where_conditions[] = "category = '$safe_category'";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Build select query with only existing columns
    $stock_select_columns = ['id', 'name', 'price'];
    if (in_array('image_path', $stock_columns)) $stock_select_columns[] = 'image_path';
    if (in_array('image', $stock_columns)) $stock_select_columns[] = 'image';
    if (in_array('category', $stock_columns)) $stock_select_columns[] = 'category';
    if (in_array('description', $stock_columns)) $stock_select_columns[] = 'description';

    $column_list_stock = implode(', ', $stock_select_columns);
    
    // Build the final query
    $items_query = "SELECT $column_list_stock FROM stock WHERE $where_clause ORDER BY id DESC";
    
    $items_result = $conn->query($items_query);
    
    if (!$items_result) {
        // If query fails, return error
        echo "<div style='grid-column: 1 / -1; text-align: center; padding: 60px; background: rgba(255,255,255,0.1); border-radius: var(--border-radius); backdrop-filter: blur(10px);'>
                <i class='fas fa-exclamation-triangle' style='font-size: 4rem; color: rgba(255,255,255,0.5); margin-bottom: 20px;'></i>
                <h3 style='color: white; margin-bottom: 15px;'>Query Error</h3>
                <p style='color: rgba(255,255,255,0.8); font-size: 1.1rem;'>There was an error loading products. Please try again.</p>
              </div>";
        exit;
    }
    
    // Output the products grid
    ?>
    <div class="items-grid" id="items-grid">
        <?php if($items_result && $items_result->num_rows > 0): ?>
            <?php while($item = $items_result->fetch_assoc()): ?>
                <div class="item-card">
                    <?php
                    // FIXED: Improved image path handling with fallbacks
                    $image_path = '';
                    if (isset($item['image_path']) && !empty($item['image_path'])) {
                        $image_path = htmlspecialchars($item['image_path']);
                        // If it's a relative path, make it absolute
                        if (!preg_match('/^(http|\/)/', $image_path)) {
                            $image_path = '/' . ltrim($image_path, '/');
                        }
                    } elseif (isset($item['image']) && !empty($item['image'])) {
                        $image_path = htmlspecialchars($item['image']);
                        if (!preg_match('/^(http|\/)/', $image_path)) {
                            $image_path = '/' . ltrim($image_path, '/');
                        }
                    } else {
                        $image_path = 'https://via.placeholder.com/300x250/667eea/ffffff?text=No+Image';
                    }
                    ?>
                    <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='https://via.placeholder.com/300x250/667eea/ffffff?text=Image+Not+Found'">
                    <div class="item-content">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <span class="price">₱<?php echo number_format($item['price'], 2); ?></span>
                        <?php if(isset($item['category']) && !empty($item['category'])): ?>
                            <p style="color: var(--light-text-color); margin-bottom: 10px; font-size: 0.9rem;">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="actions">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="add_to_cart" class="add-cart">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </form>

                            <form method="GET" action="design_item.php">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="design-item">
                                    <i class="fas fa-palette"></i> Design
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px; background: rgba(255,255,255,0.1); border-radius: var(--border-radius); backdrop-filter: blur(10px);">
                <i class="fas fa-box-open" style="font-size: 4rem; color: rgba(255,255,255,0.5); margin-bottom: 20px;"></i>
                <h3 style="color: white; margin-bottom: 15px;">No Products Found</h3>
                <p style="color: rgba(255,255,255,0.8); font-size: 1.1rem;">
                    <?php if(!empty($search_query) || !empty($category_filter)): ?>
                        No products match your search criteria. Try different keywords or <a href="index.php#items" style="color: var(--accent-color); text-decoration: underline;" onclick="clearFilters()">clear filters</a>.
                    <?php else: ?>
                        We're currently updating our product catalog. Please check back soon!
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
    <?php
    exit; // Stop further execution for AJAX requests
}

// -------------------------------
// FIXED: Fetch Photographers - REMOVED BIO COLUMN
// -------------------------------
$photographers = [];

// First, let's check what columns actually exist in the users table
$check_columns = $conn->query("SHOW COLUMNS FROM users");
$existing_columns = [];
while ($column = $check_columns->fetch_assoc()) {
    $existing_columns[] = $column['Field'];
}

// Build query with only existing columns
$select_columns = [];
if (in_array('id', $existing_columns)) $select_columns[] = 'id';
if (in_array('fullname', $existing_columns)) $select_columns[] = 'fullname';
if (in_array('email', $existing_columns)) $select_columns[] = 'email';
if (in_array('profile_image', $existing_columns)) $select_columns[] = 'profile_image';
if (in_array('role', $existing_columns)) $select_columns[] = 'role';

if (empty($select_columns)) {
    die("No valid columns found in users table");
}

$column_list = implode(', ', $select_columns);

// Fixed query without the bio column
$photographer_query = "SELECT $column_list 
                      FROM users 
                      WHERE status = 'active' 
                      AND (
                          LOWER(role) LIKE '%photo%' 
                          OR LOWER(role) LIKE '%camera%'
                          OR LOWER(role) LIKE '%photographer%'
                          OR role = 'photographer'
                          OR role = 'Photographer'
                          OR role = 'PHOTOGRAPHER'
                          OR role IS NULL
                      )
                      ORDER BY fullname";

$photographer_result = $conn->query($photographer_query);

if (!$photographer_result) {
    die("Query failed: " . $conn->error);
}

$photographers = [];
if ($photographer_result->num_rows > 0) {
    while ($photographer = $photographer_result->fetch_assoc()) {
        $photographers[] = $photographer;
    }
} else {
    // Alternative approach: Get all active users and filter in PHP
    $all_users_query = "SELECT $column_list FROM users WHERE status='active'";
    $all_users_result = $conn->query($all_users_query);
    
    if ($all_users_result && $all_users_result->num_rows > 0) {
        while ($user = $all_users_result->fetch_assoc()) {
            $role_lower = strtolower($user['role'] ?? '');
            if (strpos($role_lower, 'photo') !== false || 
                strpos($role_lower, 'camera') !== false ||
                empty($user['role'])) {
                $photographers[] = $user;
            }
        }
    }
}

// If still no photographers, let's just get all active users
if (empty($photographers)) {
    $all_users_query = "SELECT $column_list FROM users WHERE status='active' LIMIT 10";
    $all_users_result = $conn->query($all_users_query);
    
    if ($all_users_result && $all_users_result->num_rows > 0) {
        while ($user = $all_users_result->fetch_assoc()) {
            $photographers[] = $user;
        }
    }
}

// -------------------------------
// Handle Add to Cart
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security violation detected.");
    }
    
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['flash_message'] = "<div class='alert alert-error'><i class='fas fa-exclamation-triangle'></i> Please <a href='login.php'>login</a> first before ordering.</div>";
    } else {
        $item_id = intval($_POST['product_id']);
        
        // Validate product exists
        $product_check = $conn->prepare("SELECT id, name, price FROM stock WHERE id = ? AND status='active'");
        $product_check->bind_param("i", $item_id);
        $product_check->execute();
        $product_result = $product_check->get_result();
        
        if ($product_result->num_rows > 0) {
            if (isset($_SESSION['cart'][$item_id])) {
                $_SESSION['cart'][$item_id]++;
            } else {
                $_SESSION['cart'][$item_id] = 1;
            }
            $_SESSION['flash_message'] = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Item added to your cart!</div>";
        } else {
            $_SESSION['flash_message'] = "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> Product not found.</div>";
        }
        $product_check->close();
    }
    header("Location: index.php#items");
    exit();
}

// -------------------------------
// FIXED: Handle Calendar Booking - ADDED SERVICE FIELD
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_photographer'])) {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['booking_message'] = "<div class='alert alert-error'><i class='fas fa-times-circle'></i> Security violation detected.</div>";
        header("Location: index.php#photographer-booking");
        exit();
    }
    
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['booking_message'] = "<div class='alert alert-error'><i class='fas fa-exclamation-triangle'></i> Please <a href='login.php'>login</a> first to book a photographer.</div>";
        header("Location: index.php#photographer-booking");
        exit();
    }
    
    // Get and validate form data
    $required_fields = ['customer_name', 'event_date', 'start_time', 'end_time', 'location', 'event_type'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = str_replace('_', ' ', $field);
        }
    }
    
    if (!empty($missing_fields)) {
        $_SESSION['booking_message'] = "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> Missing required fields: " . implode(', ', $missing_fields) . "</div>";
        header("Location: index.php#photographer-booking");
        exit();
    }
    
    // Sanitize inputs
    $user_id = intval($_SESSION['user_id']);
    $photographer_id = isset($_POST['photographer_id']) && !empty($_POST['photographer_id']) ? intval($_POST['photographer_id']) : 0;
    $customer_name = sanitize_input($_POST['customer_name']);
    $event_date = sanitize_input($_POST['event_date']);
    $start_time = sanitize_input($_POST['start_time']);
    $end_time = sanitize_input($_POST['end_time']);
    $location = sanitize_input($_POST['location']);
    $event_type = sanitize_input($_POST['event_type']);
    $notes = sanitize_input($_POST['notes'] ?? '');
    
    // Set service based on event type
    $service = "Photography Service - " . $event_type;
    
    // Validate time slot
    if (!validate_time_slot($start_time, $end_time)) {
        $_SESSION['booking_message'] = "<div class='alert alert-error'><i class='fas fa-clock'></i> Invalid time slot. End time must be after start time (max 12 hours).</div>";
        header("Location: index.php#photographer-booking");
        exit();
    }
    
    // If no photographer selected, find an available one
    if ($photographer_id <= 0) {
        // Find first available photographer for the selected date/time
        $available_photographer_stmt = $conn->prepare("
            SELECT u.id 
            FROM users u 
            WHERE u.status = 'active' 
            AND (u.role LIKE '%photo%' OR u.role LIKE '%camera%' OR u.role = 'photographer' OR u.role IS NULL)
            AND u.id NOT IN (
                SELECT a.photographer_id 
                FROM appointments a 
                WHERE a.date = ? 
                AND a.status IN ('confirmed', 'pending')
                AND (
                    (a.start_time < ? AND a.end_time > ?) OR 
                    (a.start_time < ? AND a.end_time > ?)
                )
            )
            LIMIT 1
        ");
        
        if ($available_photographer_stmt) {
            $available_photographer_stmt->bind_param("sssss", $event_date, $end_time, $start_time, $start_time, $end_time);
            $available_photographer_stmt->execute();
            $available_result = $available_photographer_stmt->get_result();
            
            if ($available_result->num_rows > 0) {
                $available_photographer = $available_result->fetch_assoc();
                $photographer_id = $available_photographer['id'];
            }
            $available_photographer_stmt->close();
        }
        
        // If still no photographer found, use the first active photographer
        if ($photographer_id <= 0) {
            $first_photographer_stmt = $conn->prepare("
                SELECT id FROM users 
                WHERE status = 'active' 
                AND (role LIKE '%photo%' OR role LIKE '%camera%' OR role = 'photographer' OR role IS NULL)
                LIMIT 1
            ");
            if ($first_photographer_stmt) {
                $first_photographer_stmt->execute();
                $first_result = $first_photographer_stmt->get_result();
                if ($first_result->num_rows > 0) {
                    $first_photographer = $first_result->fetch_assoc();
                    $photographer_id = $first_photographer['id'];
                }
                $first_photographer_stmt->close();
            }
        }
    }
    
    // Final check - if still no photographer, show error
    if ($photographer_id <= 0) {
        $_SESSION['booking_message'] = "<div class='alert alert-error'><i class='fas fa-user-slash'></i> No photographers available. Please contact support.</div>";
        header("Location: index.php#photographer-booking");
        exit();
    }
    
    // Verify the photographer exists and is active
    $photographer_check = $conn->prepare("SELECT id FROM users WHERE id = ? AND status = 'active'");
    if ($photographer_check) {
        $photographer_check->bind_param("i", $photographer_id);
        $photographer_check->execute();
        $photographer_result = $photographer_check->get_result();
        
        if ($photographer_result->num_rows === 0) {
            $_SESSION['booking_message'] = "<div class='alert alert-error'><i class='fas fa-user-slash'></i> Selected photographer not found or inactive.</div>";
            $photographer_check->close();
            header("Location: index.php#photographer-booking");
            exit();
        }
        $photographer_check->close();
    }
    
    // Check for scheduling conflicts
    $conflict_check = $conn->prepare("
        SELECT id FROM appointments 
        WHERE photographer_id = ? 
        AND date = ? 
        AND status IN ('confirmed', 'pending')
        AND (
            (start_time < ? AND end_time > ?) OR 
            (start_time < ? AND end_time > ?)
        )
    ");
    
    if ($conflict_check) {
        $conflict_check->bind_param("isssss", $photographer_id, $event_date, $end_time, $start_time, $start_time, $end_time);
        $conflict_check->execute();
        $conflict_result = $conflict_check->get_result();
        
        if ($conflict_result->num_rows > 0) {
            $_SESSION['booking_message'] = "<div class='alert alert-error'><i class='fas fa-user-clock'></i> Photographer not available at chosen time. Please select different time.</div>";
            $conflict_check->close();
            header("Location: index.php#photographer-booking");
            exit();
        }
        $conflict_check->close();
    }
    
    // FIXED: Insert the appointment with SERVICE field included
    $stmt = $conn->prepare("
        INSERT INTO appointments (user_id, photographer_id, customer, service, date, start_time, end_time, location, event_type, notes, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    if ($stmt === false) {
        error_log("Booking Prepare Error: " . $conn->error);
        $_SESSION['booking_message'] = "<div class='alert alert-error'><i class='fas fa-database'></i> System preparation error. Please try again.</div>";
        header("Location: index.php#photographer-booking");
        exit();
    }
    
    // FIXED: Bind parameters - now 10 parameters including service
    $bind_result = $stmt->bind_param("iissssssss", 
        $user_id, 
        $photographer_id, 
        $customer_name,
        $service, // Added service parameter
        $event_date, 
        $start_time, 
        $end_time, 
        $location, 
        $event_type, 
        $notes
    );
    
    if ($bind_result === false) {
        error_log("Booking Bind Error: " . $stmt->error);
        $_SESSION['booking_message'] = "<div class='alert alert-error'><i class='fas fa-database'></i> System binding error. Please check your input.</div>";
        $stmt->close();
        header("Location: index.php#photographer-booking");
        exit();
    }
    
    // Execute the query
    $execute_result = $stmt->execute();
    
    if ($execute_result) {
        $booking_id = $stmt->insert_id;
        $_SESSION['booking_message'] = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Booking #$booking_id submitted successfully! We'll contact you to confirm.</div>";
    } else {
        error_log("Booking Execute Error: " . $stmt->error);
        $_SESSION['booking_message'] = "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> Failed to save booking: " . $stmt->error . ". Please try again.</div>";
    }
    $stmt->close();
    
    header("Location: index.php#photographer-booking");
    exit();
}

// -------------------------------
// FIXED: Handle Product Search and Filter - IMPROVED CATEGORY WITH SPACES
// -------------------------------
$search_query = "";
$category_filter = "";
$where_conditions = ["status='active'"];

// Check if search form was submitted
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = sanitize_input($_GET['search']);
    // FIXED: Improved search with better space handling
    $search_terms = preg_split('/\s+/', $search_query);
    $search_conditions = [];
    foreach ($search_terms as $term) {
        if (!empty(trim($term))) {
            $safe_term = $conn->real_escape_string(trim($term));
            $search_conditions[] = "(name LIKE '%$safe_term%' OR description LIKE '%$safe_term%')";
        }
    }
    if (!empty($search_conditions)) {
        $where_conditions[] = "(" . implode(' AND ', $search_conditions) . ")";
    }
}

// FIXED: Check if category filter was applied - handle URL encoded spaces
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category_filter = sanitize_input($_GET['category']);
}

// Build the WHERE clause
$where_clause = implode(' AND ', $where_conditions);

// FIXED: Check what columns exist in stock table
$stock_columns_check = $conn->query("SHOW COLUMNS FROM stock");
$stock_columns = [];
while ($column = $stock_columns_check->fetch_assoc()) {
    $stock_columns[] = $column['Field'];
}

// Build select query with only existing columns
$stock_select_columns = ['id', 'name', 'price'];
if (in_array('image_path', $stock_columns)) $stock_select_columns[] = 'image_path';
if (in_array('image', $stock_columns)) $stock_select_columns[] = 'image';
if (in_array('category', $stock_columns)) $stock_select_columns[] = 'category';
if (in_array('description', $stock_columns)) $stock_select_columns[] = 'description';

$column_list_stock = implode(', ', $stock_select_columns);

// DEBUG: Check if category column exists
$category_column_exists = in_array('category', $stock_columns);

// FIXED: Apply category filter if category column exists and filter is set
if ($category_column_exists && !empty($category_filter)) {
    $safe_category = $conn->real_escape_string($category_filter);
    $where_conditions[] = "category = '$safe_category'";
    $where_clause = implode(' AND ', $where_conditions);
}

// Fetch Products with filters - FIXED CATEGORY WITH SPACES
$items_query = "SELECT $column_list_stock FROM stock WHERE $where_clause ORDER BY id DESC";
$items_result = $conn->query($items_query);

if (!$items_result) {
    // If query fails, try a simpler query
    $items_query = "SELECT id, name, price FROM stock WHERE status='active' ORDER BY id DESC";
    $items_result = $conn->query($items_query);
    
    if (!$items_result) {
        die("Products query failed: " . $conn->error);
    }
}

// FIXED: Fetch all categories for the filter dropdown - only if category column exists
$categories = [];
if ($category_column_exists) {
    $categories_result = $conn->query("SELECT DISTINCT category FROM stock WHERE status='active' AND category IS NOT NULL AND category != '' ORDER BY category");
    if ($categories_result && $categories_result->num_rows > 0) {
        while ($cat = $categories_result->fetch_assoc()) {
            $categories[] = $cat['category'];
        }
    }
}

// -------------------------------
// FIXED: Fetch Carousel Items - USE SHIRT/APPAREL IMAGES
// -------------------------------
$carousel_result = $conn->query("SELECT * FROM stock WHERE status='active' AND (category LIKE '%shirt%' OR category LIKE '%apparel%' OR category LIKE '%clothing%' OR name LIKE '%shirt%' OR name LIKE '%tshirt%' OR name LIKE '%t-shirt%') ORDER BY RAND() LIMIT 5");
if (!$carousel_result || $carousel_result->num_rows == 0) {
    // Fallback if no shirt/apparel items found
    $carousel_result = $conn->query("SELECT * FROM stock WHERE status='active' ORDER BY RAND() LIMIT 5");
}

// -------------------------------
// FIXED: Fetch User Bookings - JOIN with users table to get photographer name
// -------------------------------
$userBookings = [];
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $stmt = $conn->prepare("
        SELECT a.id, a.customer, a.service, a.date, a.start_time, a.end_time, a.location, a.event_type, a.status, a.created_at, u.fullname as photographer_name
        FROM appointments a
        LEFT JOIN users u ON a.photographer_id = u.id
        WHERE a.user_id = ? 
        ORDER BY a.date DESC, a.start_time DESC
        LIMIT 5
    ");
    if ($stmt !== false) {
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $userBookings[] = $row;
        }
        $stmt->close();
    }
}

// -------------------------------
// Fetch Booked Dates for Calendar
// -------------------------------
$bookedDates = [];
$bookedSlots = [];
$calendar_stmt = $conn->prepare("
    SELECT date, start_time, end_time, status, photographer_id 
    FROM appointments 
    WHERE status IN ('confirmed', 'pending') 
    AND date >= CURDATE() 
    ORDER BY date, start_time
");
if ($calendar_stmt !== false) {
    $calendar_stmt->execute();
    $calendar_result = $calendar_stmt->get_result();
    while ($row = $calendar_result->fetch_assoc()) {
        $bookedDates[$row['date']][] = [
            'start' => $row['start_time'],
            'end' => $row['end_time'],
            'status' => $row['status'],
            'photographer_id' => $row['photographer_id']
        ];
        $bookedSlots[] = $row;
    }
    $calendar_stmt->close();
}

// Display flash messages
$flash_message = $_SESSION['flash_message'] ?? '';
$booking_message = $_SESSION['booking_message'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['booking_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Marcomedia POS | Professional Media & Photography Services</title>
<meta name="description" content="Marcomedia - Professional photography services, custom merchandise, and media solutions. Book photographers, order custom products, and elevate your brand.">
<meta name="keywords" content="photography, custom merchandise, media services, branding, events">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* ----------------------------------- */
    /* ENHANCED COLOR PALETTE & VARIABLES */
    /* ----------------------------------- */
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
    }

    h1, h2, h3, h4 {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        color: var(--secondary-color);
        margin-bottom: 0.5em;
    }

    section {
        padding: 100px 5%;
        text-align: center;
        position: relative;
    }
    
    /* Utility Classes */
    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .gradient-text {
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
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
    .alert-warning {
        background: linear-gradient(135deg, #fef3c7, #fffbeb);
        color: #92400e;
        border-left-color: #f59e0b;
    }
    .alert a {
        color: inherit;
        text-decoration: underline;
        font-weight: 600;
        transition: var(--transition);
    }
    .alert a:hover {
        opacity: 0.8;
    }

    /* Loading Spinner */
    .spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid var(--primary-color);
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
        display: inline-block;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* ----------------------------------- */
    /* FIXED: ENHANCED HEADER & NAVIGATION */
    /* ----------------------------------- */
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
        border-bottom: 1px solid rgba(255,255,255,0.2);
    }

    header.scrolled {
        height: 70px;
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }

    /* Logo Styling */
    .logo-link {
        text-decoration: none; 
        transition: var(--transition);
        display: flex;
        align-items: center;
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
        margin: 0;
        padding: 0;
    }

    /* FIXED: Navigation Container */
    .nav-container {
        display: flex;
        align-items: center;
        gap: 25px;
        flex-wrap: nowrap;
    }

    /* FIXED: Navigation Links */
    .nav-links {
        display: flex;
        align-items: center;
        gap: 25px;
        list-style: none;
    }

    .nav-links a {
        color: var(--dark-text-color);
        text-decoration: none;
        font-weight: 600;
        padding: 12px 16px;
        transition: var(--transition);
        position: relative;
        font-size: 1rem;
        border-radius: var(--border-radius);
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nav-links a:hover {
        color: var(--primary-color);
        background: rgba(37, 99, 235, 0.1);
        transform: translateY(-2px);
    }

    .nav-links a:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 3px;
        background: var(--gradient-primary);
        transition: var(--transition);
        border-radius: 2px;
    }

    .nav-links a:hover:after {
        width: 80%;
    }

    /* FIXED: Enhanced Cart Dropdown - Improved hover behavior */
    .cart-dropdown { 
        position: relative; 
        cursor: pointer; 
        padding: 12px 20px;
        border-radius: var(--border-radius);
        background: var(--background-light);
        transition: var(--transition);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .cart-dropdown:hover {
        background: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    /* FIXED: Cart text stays dark on hover */
    .cart-dropdown:hover span {
        color: white;
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
        z-index: 1001;
        margin-top: 15px;
        animation: fadeInUp 0.3s ease;
        border: 1px solid rgba(0,0,0,0.1);
    }
    
    /* FIXED: Cart dropdown stays open when hovering over it */
    .cart-dropdown:hover .cart-items,
    .cart-items:hover { 
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
        border: none;
        width: 100%;
        cursor: pointer;
    }

    .view-cart:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-lg);
    }

    /* FIXED: Notification Dropdown Styles - IMPROVED TEXT VISIBILITY */
    .notification-dropdown {
        position: relative;
        cursor: pointer;
        padding: 12px 20px;
        border-radius: var(--border-radius);
        background: var(--background-light);
        transition: var(--transition);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .notification-dropdown:hover {
        background: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .notification-dropdown.active {
        background: var(--primary-color);
        color: white;
    }

    /* FIXED: Notification text stays dark on hover */
    .notification-dropdown:hover span {
        color: white;
    }

    .notification-bell {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .notification-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background: var(--error-color);
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }

    .notification-dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background: var(--card-bg);
        border-radius: var(--border-radius);
        width: 400px;
        max-height: 500px;
        overflow-y: auto;
        box-shadow: var(--shadow-lg);
        z-index: 1001;
        margin-top: 15px;
        animation: fadeInUp 0.3s ease;
        border: 1px solid rgba(0,0,0,0.1);
    }

    .notification-dropdown.active .notification-dropdown-content {
        display: block;
    }

    .notification-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        padding: 20px;
        border-bottom: 1px solid #f1f5f9;
        transition: var(--transition);
        background: white;
    }

    .notification-item:hover {
        background: #f8fafc;
    }

    .notification-item:last-child {
        border-bottom: none;
    }

    .notification-icon {
        font-size: 1.4rem;
        flex-shrink: 0;
        margin-top: 2px;
        width: 30px;
        text-align: center;
    }

    .notification-success .notification-icon {
        color: var(--success-color);
    }

    .notification-warning .notification-icon {
        color: var(--warning-color);
    }

    .notification-error .notification-icon {
        color: var(--error-color);
    }

    .notification-info .notification-icon {
        color: var(--primary-color);
    }

    .notification-content {
        flex: 1;
    }

    .notification-content p {
        margin: 0 0 8px 0;
        font-size: 1rem;
        line-height: 1.5;
        color: var(--dark-text-color);
        font-weight: 500;
    }

    .notification-content small {
        color: var(--light-text-color);
        font-size: 0.85rem;
        display: block;
    }

    .notification-mark-read {
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: var(--border-radius);
        padding: 8px 15px;
        font-size: 0.85rem;
        cursor: pointer;
        transition: var(--transition);
        margin-top: 10px;
        display: inline-block;
    }

    .notification-mark-read:hover {
        background: var(--secondary-color);
        transform: translateY(-2px);
    }

    .no-notifications {
        padding: 30px 20px;
        text-align: center;
        color: var(--light-text-color);
    }

    .no-notifications i {
        font-size: 2.5rem;
        margin-bottom: 15px;
        color: #cbd5e1;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* FIXED: Mobile Menu Toggle */
    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--dark-text-color);
        cursor: pointer;
        padding: 10px;
        border-radius: var(--border-radius);
        transition: var(--transition);
    }

    .mobile-menu-toggle:hover {
        background: rgba(37, 99, 235, 0.1);
        color: var(--primary-color);
    }

    /* FIXED: Auth Links */
    .auth-links a {
        color: var(--dark-text-color);
        text-decoration: none;
        font-weight: 600;
        padding: 12px 20px;
        border-radius: var(--border-radius);
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--background-light);
    }

    .auth-links a:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
    }

    /* FIXED: Mobile Navigation */
    @media (max-width: 768px) {
        header {
            padding: 0 20px;
            height: 70px;
        }

        .mobile-menu-toggle {
            display: block;
        }

        .nav-container {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            flex-direction: column;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-top: 1px solid rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .nav-container.active {
            display: flex;
        }

        .nav-links {
            flex-direction: column;
            width: 100%;
            gap: 10px;
        }

        .nav-links a {
            justify-content: center;
            padding: 15px;
            width: 100%;
        }

        .cart-dropdown,
        .notification-dropdown,
        .auth-links a {
            justify-content: center;
            width: 100%;
            margin: 5px 0;
        }

        .cart-items,
        .notification-dropdown-content {
            position: fixed;
            left: 50%;
            transform: translateX(-50%);
            width: 90vw;
            max-width: 320px;
        }

        .logo h2 {
            font-size: 1.5rem;
        }
    }

    /* ----------------------------------- */
    /* ENHANCED HERO SECTION */
    /* ----------------------------------- */
    .hero {
        position: relative;
        color: #fff;
        background-image: url('images/IMG_7399.jpg');
        height: 100vh;
        min-height: 800px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 0 20px;
        overflow: hidden;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.4);
        animation: float 20s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(1deg); }
    }

    .hero-content {
        position: relative;
        z-index: 2;
        max-width: 800px;
    }
    
    .hero h1 {
        font-size: 5rem;
        margin-bottom: 25px;
        color: #fff;
        font-weight: 800;
        text-shadow: 2px 2px 20px rgba(0,0,0,0.3);
        letter-spacing: -1px;
        line-height: 1.1;
        animation: slideInUp 1s ease;
    }

    .hero p.tagline {
        font-size: 1.8rem;
        margin-bottom: 50px;
        font-weight: 300;
        color: rgba(255,255,255,0.9);
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        animation: slideInUp 1s ease 0.2s both;
    }

    .hero .button {
        background: var(--gradient-accent);
        padding: 20px 50px;
        color: #fff;
        text-decoration: none;
        font-weight: 700;
        border-radius: 50px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        box-shadow: 0 15px 35px rgba(79, 172, 254, 0.4);
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 12px;
        font-size: 1.1rem;
        animation: slideInUp 1s ease 0.4s both;
        position: relative;
        overflow: hidden;
    }

    .hero .button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: 0.5s;
    }

    .hero .button:hover::before {
        left: 100%;
    }

    .hero .button:hover {
        transform: translateY(-5px) scale(1.05);
        box-shadow: 0 20px 40px rgba(79, 172, 254, 0.6);
    }

    /* Scroll Down Indicator */
    .scroll-indicator {
        position: absolute;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        animation: bounce 2s infinite;
    }

    .scroll-indicator i {
        font-size: 2rem;
        color: rgba(255,255,255,0.8);
    }

    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
        40% { transform: translateX(-50%) translateY(-10px); }
        60% { transform: translateX(-50%) translateY(-5px); }
    }

    /* ----------------------------------- */
    /* ENHANCED CAROUSEL */
    /* ----------------------------------- */
    .carousel-section {
        padding: 80px 5%;
        background: var(--background-light);
    }

    .carousel-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .carousel-header h2 {
        font-size: 3rem;
        margin-bottom: 15px;
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .carousel-container {
        position: relative;
        max-width: 1200px;
        margin: 0 auto;
        border-radius: var(--border-radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-lg);
    }

    .carousel-slide {
        display: flex;
        transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .carousel-slide img {
        flex-shrink: 0;
        width: 100%;
        height: 600px;
        object-fit: cover;
    }

    .carousel-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255,255,255,0.9);
        border: none;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        font-size: 1.5rem;
        color: var(--primary-color);
        cursor: pointer;
        transition: var(--transition);
        box-shadow: var(--shadow);
        z-index: 10;
    }

    .carousel-nav:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-50%) scale(1.1);
    }

    .carousel-prev {
        left: 20px;
    }

    .carousel-next {
        right: 20px;
    }

    /* ----------------------------------- */
    /* IMPROVED: PRODUCT SEARCH AND FILTER */
    /* ----------------------------------- */
    .search-filter-container {
        max-width: 800px;
        margin: 0 auto 50px;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(20px);
        padding: 30px;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow);
        border: 1px solid rgba(255,255,255,0.2);
    }

    .search-filter-form {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        align-items: end;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        text-align: left;
    }

    .form-group label {
        font-weight: 600;
        margin-bottom: 12px;
        color: white;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .search-filter-form input,
    .search-filter-form select {
        padding: 18px 20px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: var(--border-radius);
        background: rgba(255,255,255,0.95);
        font-size: 1.1rem;
        transition: var(--transition);
        color: var(--dark-text-color);
        font-weight: 500;
    }

    .search-filter-form input:focus,
    .search-filter-form select:focus {
        border-color: var(--accent-color);
        box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.3);
        outline: none;
        transform: translateY(-2px);
        background: white;
    }

    .search-filter-form input::placeholder {
        color: var(--light-text-color);
        font-weight: 400;
    }

    /* Clear button for search input */
    .search-clear {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--light-text-color);
        cursor: pointer;
        padding: 5px;
        border-radius: 50%;
        transition: var(--transition);
        display: none;
    }

    .search-clear:hover {
        background: rgba(0,0,0,0.1);
        color: var(--error-color);
    }

    .search-wrapper {
        position: relative;
    }

    .search-wrapper .fas {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--light-text-color);
        z-index: 2;
    }

    .search-wrapper input {
        padding-left: 50px !important;
    }

    /* Active state for filters */
    .filter-active {
        border-color: var(--accent-color) !important;
        background: rgba(245, 158, 11, 0.1) !important;
    }

    /* ----------------------------------- */
    /* ENHANCED PRODUCTS SECTION - FIXED BUTTONS */
    /* ----------------------------------- */
    #items {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        color: white;
    }

    #items::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="%23ffffff" opacity="0.05"><circle cx="50" cy="50" r="2"/></svg>') repeat;
    }

    #items h2 {
        font-size: 3.5rem;
        margin-bottom: 60px;
        color: #fff;
        position: relative;
        display: inline-block;
        text-shadow: 2px 2px 10px rgba(0,0,0,0.2);
    }

    #items h2:after {
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

    .items-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 40px;
        max-width: 1300px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }

    .item-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: var(--border-radius-lg);
        text-align: left;
        overflow: hidden;
        transition: var(--transition);
        box-shadow: var(--shadow);
        position: relative;
        border: 1px solid rgba(255,255,255,0.2);
    }

    .item-card:hover {
        transform: translateY(-15px) scale(1.02);
        box-shadow: var(--shadow-lg);
    }

    .item-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 100%);
        pointer-events: none;
    }

    .item-card img {
        width: 100%;
        height: 250px;
        object-fit: cover;
        transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .item-card:hover img {
        transform: scale(1.1);
    }

    .item-content {
        padding: 30px;
    }

    .item-card h3 {
        font-size: 1.5rem;
        margin-bottom: 12px;
        color: var(--dark-text-color);
        font-weight: 700;
    }

    .item-card .price {
        font-size: 1.4rem;
        color: var(--primary-color);
        font-weight: 800;
        margin-bottom: 25px;
        display: block;
    }

    /* FIXED BUTTON STYLES - IMPROVED RESPONSIVENESS */
    .item-card .actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        width: 100%;
    }

    .item-card form {
        width: 100%;
        display: contents; /* This makes forms behave as grid items */
    }

    .item-card form button {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 15px 20px;
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-weight: 600;
        font-size: 1rem;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        min-height: 54px;
        white-space: nowrap;
    }

    .item-card form button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: 0.5s;
    }

    .item-card form button:hover::before {
        left: 100%;
    }

    .item-card form button.add-cart {
        background: var(--gradient-primary);
        color: #fff;
    }

    .item-card form button.design-item {
        background: var(--gradient-secondary);
        color: #fff;
    }

    .item-card form button:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    /* ----------------------------------- */
    /* ENHANCED PHOTOGRAPHER BOOKING SECTION */
    /* ----------------------------------- */
    #photographer-booking {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        position: relative;
        overflow: hidden;
    }

    #photographer-booking::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: var(--gradient-accent);
        opacity: 0.03;
        transform: rotate(45deg);
    }

    #photographer-booking h2 {
        font-size: 3.5rem;
        margin-bottom: 60px;
        position: relative;
        display: inline-block;
    }

    #photographer-booking h2:after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 5px;
        background: var(--gradient-primary);
        border-radius: 3px;
    }

    .booking-steps {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-bottom: 50px;
        position: relative;
        z-index: 2;
    }

    .step {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 20px 30px;
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        transition: var(--transition);
    }

    .step.active {
        background: var(--gradient-primary);
        color: white;
        transform: translateY(-5px);
    }

    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }

    .step.active .step-number {
        background: white;
        color: var(--primary-color);
    }

    .booking-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 50px;
        max-width: 1400px;
        margin: 0 auto;
        align-items: start;
        position: relative;
        z-index: 2;
    }

    .calendar-section {
        background: var(--card-bg);
        border-radius: var(--border-radius-lg);
        padding: 40px;
        box-shadow: var(--shadow-lg);
        border: 1px solid rgba(255,255,255,0.2);
        backdrop-filter: blur(20px);
    }

    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f1f5f9;
    }

    .calendar-nav {
        background: var(--gradient-primary);
        color: white;
        border: none;
        padding: 12px 16px;
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: var(--transition);
        font-weight: 600;
    }

    .calendar-nav:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
        margin-bottom: 25px;
    }

    .calendar-day-header {
        font-weight: 700;
        color: var(--primary-color);
        padding: 15px;
        text-align: center;
        font-size: 0.9rem;
    }

    .calendar-day {
        padding: 15px;
        text-align: center;
        border-radius: 12px;
        cursor: pointer;
        transition: var(--transition);
        border: 2px solid transparent;
        font-weight: 600;
        position: relative;
        overflow: hidden;
    }

    .calendar-day::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: var(--gradient-primary);
        opacity: 0;
        transition: var(--transition);
    }

    .calendar-day:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .calendar-day:hover::before {
        opacity: 0.1;
    }

    .calendar-day.selected {
        background: var(--gradient-primary);
        color: white;
        border-color: var(--secondary-color);
        transform: scale(1.05);
    }

    .calendar-day.booked {
        background: #fee2e2;
        color: #dc2626;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .calendar-day.pending {
        background: #fef3c7;
        color: #d97706;
    }

    .calendar-day.today {
        border: 2px solid var(--accent-color);
        background: #fffbeb;
    }

    .calendar-day.other-month {
        color: #cbd5e1;
        cursor: not-allowed;
    }

    .time-input-section {
        margin-top: 30px;
        background: #f8fafc;
        padding: 25px;
        border-radius: var(--border-radius);
    }

    .time-inputs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 15px;
    }

    .time-input-group {
        display: flex;
        flex-direction: column;
    }

    .time-input-group label {
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--secondary-color);
        font-size: 1rem;
    }

    .time-input-group input {
        padding: 15px;
        border: 2px solid #e2e8f0;
        border-radius: var(--border-radius);
        font-size: 1rem;
        transition: var(--transition);
        background: white;
    }

    .time-input-group input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
        transform: translateY(-2px);
    }

    .duration-display {
        text-align: center;
        margin-top: 15px;
        padding: 15px;
        background: var(--gradient-accent);
        color: white;
        border-radius: var(--border-radius);
        font-weight: 600;
        box-shadow: var(--shadow);
    }

    .photographer-selection {
        margin-top: 30px;
    }

    .photographer-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        margin-top: 20px;
        max-height: 400px;
        overflow-y: auto;
        padding: 15px;
        background: #f8fafc;
        border-radius: var(--border-radius);
    }

    .photographer-card {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        border: 2px solid #e2e8f0;
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: var(--transition);
        background: white;
        position: relative;
        overflow: hidden;
    }

    .photographer-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.1), transparent);
        transition: 0.5s;
    }

    .photographer-card:hover::before {
        left: 100%;
    }

    .photographer-card:hover {
        border-color: var(--primary-color);
        background: #f8faff;
        transform: translateX(5px);
    }

    .photographer-card.selected {
        border-color: var(--primary-color);
        background: linear-gradient(135deg, #eff6ff, #f0f9ff);
        box-shadow: var(--shadow);
    }

    .photographer-avatar {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #e2e8f0;
        transition: var(--transition);
        box-shadow: var(--shadow);
    }

    .photographer-card.selected .photographer-avatar {
        border-color: var(--primary-color);
        transform: scale(1.1);
    }

    .photographer-info {
        flex: 1;
        text-align: left;
    }

    .photographer-info h4 {
        margin-bottom: 8px;
        color: var(--secondary-color);
        font-size: 1.2rem;
    }

    .photographer-info p {
        color: var(--light-text-color);
        font-size: 0.9rem;
        margin-bottom: 5px;
    }

    .photographer-bio {
        font-size: 0.85rem;
        color: #64748b;
        margin-top: 5px;
    }

    .availability-status {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .available {
        background: #d1fae5;
        color: #065f46;
    }

    .busy {
        background: #fee2e2;
        color: #991b1b;
    }

    .booking-form-section {
        background: var(--card-bg);
        border-radius: var(--border-radius-lg);
        padding: 40px;
        box-shadow: var(--shadow-lg);
        border: 1px solid rgba(255,255,255,0.2);
        backdrop-filter: blur(20px);
    }

    /* FIXED: ADDED MISSING FORM STYLES */
    #booking-form {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        text-align: left;
    }

    .form-group label {
        font-weight: 600;
        margin-bottom: 10px;
        color: var(--secondary-color);
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    #booking-form input,
    #booking-form select,
    #booking-form textarea {
        padding: 18px;
        border-radius: var(--border-radius);
        border: 2px solid #e2e8f0;
        font-size: 1rem;
        color: var(--dark-text-color);
        transition: var(--transition);
        font-family: inherit;
        background: white;
    }

    #booking-form input:focus,
    #booking-form select:focus,
    #booking-form textarea:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
        transform: translateY(-2px);
    }

    #booking-form button[type="submit"] {
        background: var(--gradient-primary);
        color: #fff;
        padding: 20px;
        border: none;
        border-radius: var(--border-radius);
        font-size: 1.2rem;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-top: 20px;
        width: 100%;
        position: relative;
        overflow: hidden;
    }

    #booking-form button[type="submit"]::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: 0.5s;
    }

    #booking-form button[type="submit"]:hover::before {
        left: 100%;
    }

    #booking-form button[type="submit"]:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(37, 99, 235, 0.4);
    }

    #booking-form button[type="submit"]:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    #booking-form button[type="submit"]:disabled:hover::before {
        left: -100%;
    }

    /* Bookings List */
    .bookings-list {
        margin-top: 50px;
        text-align: left;
    }

    .booking-card {
        background: var(--card-bg);
        border-radius: var(--border-radius);
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: var(--shadow);
        border-left: 6px solid var(--primary-color);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .booking-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.05), transparent);
        transition: 0.5s;
    }

    .booking-card:hover::before {
        left: 100%;
    }

    .booking-card:hover {
        transform: translateX(5px);
        box-shadow: var(--shadow-lg);
    }

    .booking-card.confirmed {
        border-left-color: var(--success-color);
    }

    .booking-card.pending {
        border-left-color: var(--warning-color);
    }

    .booking-card.cancelled {
        border-left-color: var(--error-color);
    }

    .booking-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .booking-id {
        font-weight: 700;
        color: var(--secondary-color);
        font-size: 1.1rem;
    }

    .booking-status {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-confirmed {
        background: #d1fae5;
        color: #065f46;
    }

    .status-cancelled {
        background: #fee2e2;
        color: #991b1b;
    }

    .booking-summary {
        background: #f8fafc;
        padding: 25px;
        border-radius: var(--border-radius);
        margin: 25px 0;
        border-left: 4px solid var(--primary-color);
    }

    .booking-summary h4 {
        color: var(--secondary-color);
        margin-bottom: 15px;
        font-size: 1.3rem;
    }

    .booking-summary p {
        margin: 8px 0;
        font-weight: 500;
    }

    /* ----------------------------------- */
    /* MODERN FOOTER STYLES */
    /* ----------------------------------- */
    footer {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: #e2e8f0;
        padding: 80px 20px 40px;
        position: relative;
        overflow: hidden;
    }

    footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" fill="%23434c5e" opacity="0.1"><circle cx="100" cy="100" r="2"/><circle cx="300" cy="300" r="3"/><circle cx="500" cy="150" r="2"/><circle cx="700" cy="400" r="4"/><circle cx="900" cy="250" r="2"/></svg>');
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 2fr 1fr 1fr; /* Changed from 2fr 1fr 1fr 1fr */
        gap: 40px;
        position: relative;
        z-index: 2;
    }

    .footer-brand {
        text-align: left;
    }

    .footer-logo {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }

    .footer-logo img {
        width: 40px;
        height: 40px;
        filter: brightness(0) invert(1);
    }

    .footer-logo h3 {
        color: white;
        font-size: 1.8rem;
        margin: 0;
    }

    .footer-brand p {
        color: #94a3b8;
        line-height: 1.6;
        margin-bottom: 25px;
        font-size: 1.1rem;
    }

    .footer-heading {
        color: white;
        font-size: 1.3rem;
        margin-bottom: 25px;
        font-weight: 700;
        position: relative;
    }

    .footer-heading::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        width: 40px;
        height: 3px;
        background: var(--gradient-accent);
        border-radius: 2px;
    }

    .footer-links {
        list-style: none;
        padding: 0;
    }

    .footer-links li {
        margin-bottom: 12px;
    }

    .footer-links a {
        color: #94a3b8;
        text-decoration: none;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .footer-links a:hover {
        color: white;
        transform: translateX(5px);
    }

    .footer-contact li {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 15px;
        color: #94a3b8;
    }

    .footer-contact i {
        color: var(--accent-color);
        margin-top: 3px;
        flex-shrink: 0;
    }

    .social-links {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    .social-links a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        color: #94a3b8;
        text-decoration: none;
        transition: var(--transition);
        backdrop-filter: blur(10px);
    }

    .social-links a:hover {
        background: var(--gradient-primary);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    .footer-bottom {
        max-width: 1200px;
        margin: 60px auto 0;
        padding-top: 30px;
        border-top: 1px solid #334155;
        text-align: center;
        position: relative;
        z-index: 2;
    }

    .copyright {
        color: #64748b;
        font-size: 0.95rem;
    }

    .copyright a {
        color: var(--accent-color);
        text-decoration: none;
        transition: var(--transition);
    }

    .copyright a:hover {
        color: white;
    }

    /* Responsive Footer */
    @media (max-width: 992px) {
        .footer-content {
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
    }

    @media (max-width: 768px) {
        .footer-content {
            grid-template-columns: 1fr;
            gap: 40px;
            text-align: center;
        }

        .footer-brand {
            text-align: center;
        }

        .footer-logo {
            justify-content: center;
        }

        .footer-heading::after {
            left: 50%;
            transform: translateX(-50%);
        }

        .social-links {
            justify-content: center;
        }
    }

    /* ADDED: Modal Popup Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(5px);
    }

    .modal-content {
        background: white;
        padding: 40px;
        border-radius: var(--border-radius-lg);
        max-width: 500px;
        width: 90%;
        text-align: center;
        box-shadow: var(--shadow-lg);
        position: relative;
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

    .modal-close {
        position: absolute;
        top: 15px;
        right: 20px;
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--light-text-color);
        transition: var(--transition);
    }

    .modal-close:hover {
        color: var(--error-color);
        transform: scale(1.1);
    }

    .modal-icon {
        font-size: 4rem;
        margin-bottom: 20px;
    }

    .modal-success .modal-icon {
        color: var(--success-color);
    }

    .modal-error .modal-icon {
        color: var(--error-color);
    }

    .modal-title {
        font-size: 1.8rem;
        margin-bottom: 15px;
        color: var(--dark-text-color);
    }

    .modal-message {
        font-size: 1.1rem;
        color: var(--light-text-color);
        line-height: 1.6;
        margin-bottom: 25px;
    }

    .modal-button {
        background: var(--gradient-primary);
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: var(--border-radius);
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .modal-button:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    /* ----------------------------------- */
    /* RESPONSIVENESS - IMPROVED MOBILE FIXES */
    /* ----------------------------------- */
    @media (max-width: 1200px) {
        .hero h1 { 
            font-size: 4rem; 
        }
        
        .booking-container {
            gap: 30px;
        }
    }

    @media (max-width: 992px) {
        .hero h1 { 
            font-size: 3.5rem; 
        }
        
        .booking-container {
            grid-template-columns: 1fr;
            gap: 40px;
        }

        .booking-steps {
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .step {
            width: 100%;
            max-width: 400px;
        }

        .search-filter-form {
            grid-template-columns: 1fr;
            gap: 20px;
        }
    }

    @media (max-width: 768px) {
        .hero h1 { 
            font-size: 3rem; 
        }
        
        section { 
            padding: 80px 20px; 
        }
        
        .time-inputs {
            grid-template-columns: 1fr;
        }

        .calendar-grid {
            gap: 4px;
        }

        .calendar-day {
            padding: 10px 5px;
            font-size: 0.9rem;
        }

        /* FIXED: Mobile buttons layout - Single column on mobile */
        .item-card .actions {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .item-card form button {
            min-height: 50px;
            font-size: 0.95rem;
        }

        .photographer-card {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }

        .availability-status {
            margin-left: 0;
            margin-top: 10px;
        }

        .carousel-slide img {
            height: 400px;
        }

        .calendar-section,
        .booking-form-section {
            padding: 25px 20px;
        }

        .search-filter-container {
            padding: 25px;
        }

        .search-filter-form {
            gap: 15px;
        }
    }

    @media (max-width: 480px) {
        .hero h1 { 
            font-size: 2.5rem; 
        }
        
        .hero p.tagline {
            font-size: 1.3rem;
        }
        
        .items-grid {
            grid-template-columns: 1fr;
            gap: 25px;
        }

        #items h2,
        #photographer-booking h2 {
            font-size: 2.5rem;
        }

        .calendar-section,
        .booking-form-section {
            padding: 20px 15px;
        }

        footer .social-links {
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        /* Ensure buttons are properly sized on very small screens */
        .item-card form button {
            padding: 12px 15px;
            font-size: 0.9rem;
        }

        .item-card .actions {
            gap: 8px;
        }

        .search-filter-form input,
        .search-filter-form select {
            padding: 15px;
            font-size: 1rem;
        }
    }
</style>
</head>
<body>
<!-- Header with Improved navigation -->
<header>
    <a href="index.php#home" class="logo-link">
        <div class="logo">
            <img src="images/rsz_logo.png" alt="Marcomedia Logo" class="logo-img">
            <h2>Marcomedia</h2>
        </div>
    </a>
    
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Navigation Container -->
    <div class="nav-container" id="navContainer">
        <ul class="nav-links">
            <li><a href="index.php#items"><i class="fas fa-box"></i> Products</a></li>
            <li><a href="index.php#photographer-booking"><i class="fas fa-camera"></i> Book Photographer</a></li>

            <?php if(isset($_SESSION['user_id'])): ?>
                <li><a href="my_bookings.php"><i class="fas fa-calendar-alt"></i> My Bookings</a></li>
            <?php endif; ?>
        </ul>

        <!-- Cart Dropdown -->
        <div class="cart-dropdown">
            <span class="cart-icon">
                <i class="fas fa-shopping-cart"></i> 
                Cart (<?php echo array_sum($_SESSION['cart']); ?>)
            </span>
            <?php if(!empty($_SESSION['cart'])): ?>
                <div class="cart-items">
                    <?php
                    if (!empty($_SESSION['cart'])) {
                        $ids = implode(',', array_keys($_SESSION['cart']));
                        $cartQuery = $conn->query("SELECT * FROM stock WHERE id IN ($ids)");
                        if ($cartQuery) {
                            while($cartItem = $cartQuery->fetch_assoc()) {
                                $qty = $_SESSION['cart'][$cartItem['id']];
                                // FIXED: Improved image path handling
                                $image_path = '';
                                if (isset($cartItem['image_path']) && !empty($cartItem['image_path'])) {
                                    $image_path = htmlspecialchars($cartItem['image_path']);
                                    if (!preg_match('/^(http|\/)/', $image_path)) {
                                        $image_path = '/' . ltrim($image_path, '/');
                                    }
                                } elseif (isset($cartItem['image']) && !empty($cartItem['image'])) {
                                    $image_path = htmlspecialchars($cartItem['image']);
                                    if (!preg_match('/^(http|\/)/', $image_path)) {
                                        $image_path = '/' . ltrim($image_path, '/');
                                    }
                                } else {
                                    $image_path = 'https://via.placeholder.com/100x100/667eea/ffffff?text=No+Image';
                                }
                                ?>
                                <div class="cart-item">
                                    <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($cartItem['name']); ?>" onerror="this.src='https://via.placeholder.com/100x100/667eea/ffffff?text=Image+Not+Found'">
                                    <div>
                                        <strong><?php echo htmlspecialchars($cartItem['name']); ?></strong>
                                        <div>x <?php echo $qty; ?></div>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                    }
                    ?>
                    <a href="my_cart.php" class="view-cart"><i class="fas fa-shopping-bag"></i> View Cart</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- FIXED: Notification Bell - Improved text visibility -->
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="notification-dropdown" id="notificationDropdown">
                <span class="notification-bell">
                    <i class="fas fa-bell"></i>
                    <?php if(count($userNotifications) > 0): ?>
                        <span class="notification-count"><?php echo count($userNotifications); ?></span>
                    <?php endif; ?>
                </span>
                <div class="notification-dropdown-content">
                    <?php if(!empty($userNotifications)): ?>
                        <?php foreach($userNotifications as $notification): ?>
                            <div class="notification-item notification-<?php echo $notification['type']; ?>">
                                <div class="notification-icon">
                                    <?php if($notification['type'] == 'success'): ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php elseif($notification['type'] == 'warning'): ?>
                                        <i class="fas fa-exclamation-triangle"></i>
                                    <?php else: ?>
                                        <i class="fas fa-info-circle"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-content">
                                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <small><?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?></small>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="mark_notification_read" value="1">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" class="notification-mark-read">Mark as read</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-notifications">
                            <i class="fas fa-bell-slash"></i>
                            <p>No new notifications</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Login/Logout -->
        <div class="auth-links">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Flash Messages -->
<?php if($flash_message): ?>
    <div class="container" style="margin-top: 100px;">
        <?php echo $flash_message; ?>
    </div>
<?php endif; ?>

<!-- Modal Popup -->
<div class="modal-overlay" id="bookingModal">
    <div class="modal-content" id="modalContent">
        <button class="modal-close" id="modalClose">&times;</button>
        <div class="modal-icon">
            <i class="fas fa-check-circle" id="modalIcon"></i>
        </div>
        <h2 class="modal-title" id="modalTitle">Booking Successful!</h2>
        <p class="modal-message" id="modalMessage">Your booking has been submitted successfully.</p>
        <button class="modal-button" id="modalButton">
            <i class="fas fa-thumbs-up"></i> Got it!
        </button>
    </div>
</div>

<!-- Hero Section -->
<section class="hero" id="home">
    <div class="hero-content">
        <h1>The best place to <span class="gradient-text">get customized</span></h1>
        <p class="tagline">Custom Merchandise & Photography for Every Occasion</p>
        <a href="#items" class="button"><i class="fas fa-palette"></i>Customize Here</a>
    </div>
    <div class="scroll-indicator">
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

<!-- Carousel Section - FIXED: Uses shirt/apparel images -->
<section class="carousel-section">
    <div class="carousel-header">
        <h2>Featured Work</h2>
        <p style="color: var(--light-text-color); font-size: 1.2rem;">Discover our latest photography projects and custom merchandise</p>
    </div>
    <div class="carousel-container">
        <div class="carousel-slide">
            <?php 
            $slides = [];
            if ($carousel_result && $carousel_result->num_rows > 0) {
                 while ($slide = $carousel_result->fetch_assoc()): 
                    $slides[] = $slide;
                    // FIXED: Improved image path handling
                    $image_path = '';
                    if (isset($slide['image_path']) && !empty($slide['image_path'])) {
                        $image_path = htmlspecialchars($slide['image_path']);
                        if (!preg_match('/^(http|\/)/', $image_path)) {
                            $image_path = '/' . ltrim($image_path, '/');
                        }
                    } elseif (isset($slide['image']) && !empty($slide['image'])) {
                        $image_path = htmlspecialchars($slide['image']);
                        if (!preg_match('/^(http|\/)/', $image_path)) {
                            $image_path = '/' . ltrim($image_path, '/');
                        }
                    } else {
                        $image_path = 'https://via.placeholder.com/800x600/667eea/ffffff?text=No+Image';
                    }
                    ?>
                    <img src="<?php echo $image_path; ?>" alt="Featured Work: <?php echo htmlspecialchars($slide['name']); ?>" onerror="this.src='https://via.placeholder.com/800x600/667eea/ffffff?text=Image+Not+Found'">
                 <?php endwhile;
            }
            
            if (!empty($slides)):
                $first_slide = $slides[0];
                // FIXED: Improved image path handling for clone slide
                $first_image_path = '';
                if (isset($first_slide['image_path']) && !empty($first_slide['image_path'])) {
                    $first_image_path = htmlspecialchars($first_slide['image_path']);
                    if (!preg_match('/^(http|\/)/', $first_image_path)) {
                        $first_image_path = '/' . ltrim($first_image_path, '/');
                    }
                } elseif (isset($first_slide['image']) && !empty($first_slide['image'])) {
                    $first_image_path = htmlspecialchars($first_slide['image']);
                    if (!preg_match('/^(http|\/)/', $first_image_path)) {
                        $first_image_path = '/' . ltrim($first_image_path, '/');
                    }
                } else {
                    $first_image_path = 'https://via.placeholder.com/800x600/667eea/ffffff?text=No+Image';
                }
                ?>
                <img src="<?php echo $first_image_path; ?>" alt="Featured Work: <?php echo htmlspecialchars($first_slide['name']); ?>" class="clone-slide" onerror="this.src='https://via.placeholder.com/800x600/667eea/ffffff?text=Image+Not+Found'">
            <?php endif; ?>
        </div>
        <button class="carousel-nav carousel-prev"><i class="fas fa-chevron-left"></i></button>
        <button class="carousel-nav carousel-next"><i class="fas fa-chevron-right"></i></button>
    </div>
</section>

<!-- Products Section with Search -->
<section id="items">
    <h2>Premium Products & Custom Merchandise</h2>
    <p style="color: rgba(255,255,255,0.8); font-size: 1.3rem; margin-bottom: 40px; max-width: 600px; margin-left: auto; margin-right: auto;">Transform your memories into tangible treasures with our high-quality custom products</p>
    
    <!-- Search and Filter Form -->
    <div class="search-filter-container">
        <form method="GET" action="index.php#items" class="search-filter-form" id="searchForm">
            <div class="form-group">
                <label for="search"><i class="fas fa-search"></i> Search Products</label>
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" id="search" placeholder="Type to search products..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="button" class="search-clear" id="searchClear" title="Clear search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <?php if(!empty($categories)): ?>
            <div class="form-group">
                <label for="category"><i class="fas fa-filter"></i> Filter by Category</label>
                <select name="category" id="category">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter == $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="items-grid" id="items-grid">
        <?php if($items_result && $items_result->num_rows > 0): ?>
            <?php while($item = $items_result->fetch_assoc()): ?>
                <div class="item-card">
                    <!-- FIXED: Improved image path handling with better fallbacks -->
                    <?php
                    $image_path = '';
                    if (isset($item['image_path']) && !empty($item['image_path'])) {
                        $image_path = htmlspecialchars($item['image_path']);
                        // If it's a relative path, make it absolute
                        if (!preg_match('/^(http|\/)/', $image_path)) {
                            $image_path = '/' . ltrim($image_path, '/');
                        }
                    } elseif (isset($item['image']) && !empty($item['image'])) {
                        $image_path = htmlspecialchars($item['image']);
                        if (!preg_match('/^(http|\/)/', $image_path)) {
                            $image_path = '/' . ltrim($image_path, '/');
                        }
                    } else {
                        $image_path = 'https://via.placeholder.com/300x250/667eea/ffffff?text=No+Image';
                    }
                    ?>
                    <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='https://via.placeholder.com/300x250/667eea/ffffff?text=Image+Not+Found'">
                    <div class="item-content">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <span class="price">₱<?php echo number_format($item['price'], 2); ?></span>
                        <?php if(isset($item['category']) && !empty($item['category'])): ?>
                            <p style="color: var(--light-text-color); margin-bottom: 10px; font-size: 0.9rem;">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <!-- proper grid layout -->
                        <div class="actions">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="add_to_cart" class="add-cart">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </form>

                            <form method="GET" action="design_item.php">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="design-item">
                                    <i class="fas fa-palette"></i> Design
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px; background: rgba(255,255,255,0.1); border-radius: var(--border-radius); backdrop-filter: blur(10px);">
                <i class="fas fa-box-open" style="font-size: 4rem; color: rgba(255,255,255,0.5); margin-bottom: 20px;"></i>
                <h3 style="color: white; margin-bottom: 15px;">No Products Found</h3>
                <p style="color: rgba(255,255,255,0.8); font-size: 1.1rem;">
                    <?php if(!empty($search_query) || !empty($category_filter)): ?>
                        No products match your search criteria. Try different keywords or <a href="index.php#items" style="color: var(--accent-color); text-decoration: underline;" onclick="clearFilters()">clear filters</a>.
                    <?php else: ?>
                        We're currently updating our product catalog. Please check back soon!
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Photographer Booking Section -->
<section id="photographer-booking">
    <h2>Book Your Professional Photographer</h2>
    <p style="color: var(--light-text-color); font-size: 1.3rem; margin-bottom: 50px; max-width: 700px; margin-left: auto; margin-right: auto;">Schedule your perfect photoshoot in just a few simple steps</p>
    
    <?php if($booking_message): ?>
        <div class="container" style="max-width: 1200px; margin-bottom: 40px;">
            <?php echo $booking_message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Booking Steps -->
    <div class="booking-steps">
        <div class="step active" id="step-1">
            <div class="step-number">1</div>
            <span>Choose Date & Time</span>
        </div>
        <div class="step" id="step-2">
            <div class="step-number">2</div>
            <span>Select Photographer</span>
        </div>
        <div class="step" id="step-3">
            <div class="step-number">3</div>
            <span>Event Details</span>
        </div>
        <div class="step" id="step-4">
            <div class="step-number">4</div>
            <span>Confirm Booking</span>
        </div>
    </div>
    
    <!-- Form with SERVICE field included -->
    <form method="POST" id="booking-form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="book_photographer" value="1">
        
        <div class="booking-container">
            <!-- Calendar Section -->
            <div class="calendar-section">
                <div class="calendar-header">
                    <button class="calendar-nav" id="prev-month" type="button"><i class="fas fa-chevron-left"></i></button>
                    <h3 id="current-month">Month Year</h3>
                    <button class="calendar-nav" id="next-month" type="button"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="calendar-grid" id="calendar-grid">
                    <!-- Calendar will be generated by JavaScript -->
                </div>

                <!-- Time Selection -->
                <div class="time-input-section">
                    <h4><i class="fas fa-clock"></i> Select Your Preferred Time</h4>
                    <p style="color: var(--light-text-color); margin-bottom: 15px; font-size: 0.95rem;">Choose start and end time for your photoshoot (max 12 hours)</p>
                    <div class="time-inputs">
                        <div class="time-input-group">
                            <label for="start_time">Start Time</label>
                            <input type="time" id="start_time" name="start_time" min="06:00" max="22:00" required>
                        </div>
                        <div class="time-input-group">
                            <label for="end_time">End Time</label>
                            <input type="time" id="end_time" name="end_time" min="06:00" max="22:00" required>
                        </div>
                    </div>
                    <div class="duration-display" id="duration-display">
                        <i class="fas fa-hourglass-half"></i> Duration: 0 hours
                    </div>
                </div>

                <!-- Photographer Selection -->
                <div class="photographer-selection">
                    <h4><i class="fas fa-user-camera"></i> Choose Your Photographer</h4>
                    <p style="color: var(--light-text-color); margin-bottom: 15px; font-size: 0.95rem;">Select from our talented team of professional photographers</p>
                    <div class="photographer-grid" id="photographer-grid">
                        <?php if(!empty($photographers)): ?>
                            <?php foreach($photographers as $photographer): ?>
                                <div class="photographer-card" data-photographer-id="<?php echo $photographer['id']; ?>">
                                    <?php
                                    // Handle profile image - use default if not available
                                    $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($photographer['fullname']) . '&background=2563eb&color=fff&size=100';
                                    if (!empty($photographer['profile_image'])) {
                                        $avatar_url = htmlspecialchars($photographer['profile_image']);
                                        if (!preg_match('/^(http|\/)/', $avatar_url)) {
                                            $avatar_url = '/' . ltrim($avatar_url, '/');
                                        }
                                    }
                                    ?>
                                    <img src="<?php echo $avatar_url; ?>" 
                                         alt="<?php echo htmlspecialchars($photographer['fullname']); ?>" class="photographer-avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($photographer['fullname']); ?>&background=2563eb&color=fff&size=100'">
                                    <div class="photographer-info">
                                        <h4><?php echo htmlspecialchars($photographer['fullname']); ?></h4>
                                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($photographer['email']); ?></p>
                                    </div>
                                    <div class="availability-status available" id="status-<?php echo $photographer['id']; ?>">
                                        <i class="fas fa-check-circle"></i> Available
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: var(--light-text-color); background: #f8fafc; border-radius: var(--border-radius);">
                                <i class="fas fa-camera-slash" style="font-size: 3rem; margin-bottom: 20px; color: #cbd5e1;"></i>
                                <h4 style="color: var(--light-text-color); margin-bottom: 15px;">No Photographers Available</h4>
                                <p style="margin-bottom: 20px;">We're currently updating our photographer team. Please check back soon or contact us for special arrangements.</p>
                                <a href="mailto:info@marcomedia.com" style="background: var(--gradient-primary); color: white; padding: 12px 25px; border-radius: var(--border-radius); text-decoration: none; font-weight: 600; display: inline-block;">
                                    <i class="fas fa-envelope"></i> Contact Us
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Booking Form Section -->
            <div class="booking-form-section">
                <div class="booking-summary" id="booking-summary" style="display: none;">
                    <h4><i class="fas fa-clipboard-check"></i> Booking Summary</h4>
                    <div id="summary-details"></div>
                </div>

                <input type="hidden" name="event_date" id="selected-date">
                <input type="hidden" name="photographer_id" id="selected-photographer">
                
                <div class="form-group">
                    <label for="customer_name"><i class="fas fa-user"></i> Your Full Name</label>
                    <input type="text" name="customer_name" id="customer_name" required 
                           placeholder="Enter your full name"
                           value="<?php 
                           // Try multiple session variables to find the user's name
                           if (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])) {
                               echo htmlspecialchars($_SESSION['user_name']);
                           } elseif (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
                               echo htmlspecialchars($_SESSION['fullname']);
                           } elseif (isset($_SESSION['name']) && !empty($_SESSION['name'])) {
                               echo htmlspecialchars($_SESSION['name']);
                           } elseif (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
                               echo htmlspecialchars($_SESSION['username']);
                           }
                           ?>">
                </div>

                <div class="form-group">
                    <label for="event_type"><i class="fas fa-calendar-alt"></i> Event Type</label>
                    <select name="event_type" id="event_type" required>
                        <option value="">Select Event Type</option>
                        <option value="Wedding">💍 Wedding Photography</option>
                        <option value="Portrait">👤 Portrait Session</option>
                        <option value="Corporate">💼 Corporate Event</option>
                        <option value="Product">📦 Product Photography</option>
                        <option value="Real Estate">🏠 Real Estate</option>
                        <option value="Event">🎉 Special Event</option>
                        <option value="Family">👨‍👩‍👧‍👦 Family Photos</option>
                        <option value="Other">✨ Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="location"><i class="fas fa-map-marker-alt"></i> Event Location</label>
                    <input type="text" name="location" id="location" placeholder="Where will the event take place? (Full address)" required>
                </div>

                <div class="form-group">
                    <label for="notes"><i class="fas fa-sticky-note"></i> Additional Notes & Requirements</label>
                    <textarea name="notes" id="notes" rows="4" placeholder="Any specific requirements, special instructions, or details about your event..."></textarea>
                </div>

                <button type="submit" name="book_photographer" id="submit-booking" disabled>
                    <i class="fas fa-calendar-check"></i> CONFIRM BOOKING
                </button>

                <!-- User's Bookings -->
                <?php if(!empty($userBookings)): ?>
                <div class="bookings-list">
                    <h3 style="text-align: center; margin: 40px 0 25px; color: var(--secondary-color);"><i class="fas fa-history"></i> Your Recent Bookings</h3>
                    <?php foreach($userBookings as $booking): ?>
                        <div class="booking-card <?php echo $booking['status']; ?>">
                            <div class="booking-header">
                                <span class="booking-id">Booking #<?php echo $booking['id']; ?></span>
                                <span class="booking-status status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                            <p style="font-size: 1.1rem; font-weight: 600; margin-bottom: 15px;"><?php echo htmlspecialchars($booking['event_type']); ?></p>
                            <p><i class="fas fa-user"></i> <strong>Customer:</strong> <?php echo htmlspecialchars($booking['customer']); ?></p>
                            <?php if(isset($booking['photographer_name'])): ?>
                                <p><i class="fas fa-user-camera"></i> <strong>Photographer:</strong> <?php echo htmlspecialchars($booking['photographer_name']); ?></p>
                            <?php endif; ?>
                            <p><i class="fas fa-calendar"></i> <strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['date'])); ?></p>
                            <p><i class="fas fa-clock"></i> <strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> <?php echo htmlspecialchars($booking['location']); ?></p>
                            <?php if(!empty($booking['notes'])): ?>
                                <p style="margin-top: 15px; padding: 15px; background: #f8fafc; border-radius: 8px; border-left: 4px solid var(--primary-color);">
                                    <i class="fas fa-comment"></i> <strong>Notes:</strong> <?php echo htmlspecialchars($booking['notes']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if(count($userBookings) >= 5): ?>
                        <div style="text-align: center; margin-top: 30px;">
                            <a href="my_bookings.php" style="background: var(--gradient-primary); color: white; padding: 15px 30px; border-radius: var(--border-radius); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 10px;">
                                <i class="fas fa-list"></i> View All Bookings
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</section>

<!-- MODERN FOOTER -->
<footer id="footer">
    <div class="footer-content">
        <div class="footer-brand">
            <div class="footer-logo">
                <img src="images/rsz_logo.png" alt="Marcomedia Logo">
                <h3>Marcomedia</h3>
            </div>
            <p>Where Memories Become Masterpieces. Professional photography services, custom merchandise, and media solutions for every occasion.</p>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-tiktok"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
        </div>

        <div class="footer-links-column">
            <h4 class="footer-heading">Services</h4>
            <ul class="footer-links">
                <li><a href="#"><i class="fas fa-palette"></i> Custom Merchandise</a></li>
                <li><a href="#"><i class="fas fa-camera"></i> Photography</a></li>
                <li><a href="#"><i class="fas fa-video"></i> Videography</a></li>
                <li><a href="#"><i class="fas fa-brush"></i> Graphic Design</a></li>
                <li><a href="#"><i class="fas fa-calendar"></i> Event Coverage</a></li>
            </ul>
        </div>

        <div class="footer-contact">
            <h4 class="footer-heading">Contact Info</h4>
            <ul>
                <li>
                    <i class="fas fa-map-marker-alt"></i>
                    <span>123 Business Street<br>City, State 12345</span>
                </li>
                <li>
                    <i class="fas fa-phone"></i>
                    <span>+1 (555) 123-4567</span>
                </li>
                <li>
                    <i class="fas fa-envelope"></i>
                    <span>info@marcomedia.com</span>
                </li>
                <li>
                    <i class="fas fa-clock"></i>
                    <span>Mon - Fri: 9AM - 6PM<br>Sat: 10AM - 4PM</span>
                </li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <p class="copyright">&copy; <?php echo date('Y'); ?> Marcomedia POS. All rights reserved. | <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // FIXED: Search and filter functionality with improved AJAX (automatic updates)
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('search');
    const categorySelect = document.getElementById('category');
    const searchClear = document.getElementById('searchClear');
    const itemsGrid = document.getElementById('items-grid');

    let searchTimeout;
    
    // Show/hide clear button based on search input
    function toggleClearButton() {
        if (searchInput && searchInput.value.length > 0) {
            searchClear.style.display = 'block';
        } else {
            searchClear.style.display = 'none';
        }
    }
    
    // AJAX function to update products
    function updateProducts() {
        const searchValue = searchInput ? searchInput.value : '';
        const categoryValue = categorySelect ? categorySelect.value : '';
        
        // Show loading state
        if (itemsGrid) {
            itemsGrid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px;">
                    <div class="spinner"></div>
                    <p style="color: white; margin-top: 20px;">Loading products...</p>
                </div>
            `;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('ajax', '1');
        if (searchValue) formData.append('search', searchValue);
        if (categoryValue) formData.append('category', categoryValue);
        
        // AJAX request
        fetch('index.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            if (itemsGrid) {
                itemsGrid.innerHTML = html;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (itemsGrid) {
                itemsGrid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px; background: rgba(255,255,255,0.1); border-radius: var(--border-radius); backdrop-filter: blur(10px);">
                        <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: rgba(255,255,255,0.5); margin-bottom: 20px;"></i>
                        <h3 style="color: white; margin-bottom: 15px;">Connection Error</h3>
                        <p style="color: rgba(255,255,255,0.8); font-size: 1.1rem;">
                            Unable to load products. <a href="index.php#items" style="color: var(--accent-color); text-decoration: underline;" onclick="location.reload()">Refresh the page</a> and try again.
                        </p>
                    </div>
                `;
            }
        });
    }

    // Auto-update products with debouncing
    function autoUpdateProducts() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(updateProducts, 300); // 300ms delay for better performance
    }

    // FIXED: Event listeners for live search - prevent form submission
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateProducts();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            toggleClearButton();
            autoUpdateProducts();
        });
        
        // Clear search functionality
        searchClear.addEventListener('click', function() {
            searchInput.value = '';
            toggleClearButton();
            autoUpdateProducts();
        });
        
        // Initial state of clear button
        toggleClearButton();
    }

    // Auto-update on category change
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            autoUpdateProducts();
        });
    }

    // Function to clear all filters
    window.clearFilters = function() {
        if (searchInput) searchInput.value = '';
        if (categorySelect) categorySelect.value = '';
        toggleClearButton();
        updateProducts();
    }

    // Header scroll effect
    const header = document.querySelector('header');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // Mobile menu functionality
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navContainer = document.getElementById('navContainer');
    
    if (mobileMenuToggle && navContainer) {
        mobileMenuToggle.addEventListener('click', function() {
            navContainer.classList.toggle('active');
            const icon = this.querySelector('i');
            if (navContainer.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }

    // FIXED: Notification dropdown - click to open/close with AJAX mark as read
    const notificationDropdown = document.getElementById('notificationDropdown');
    if (notificationDropdown) {
        notificationDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
        });

        // Close notification dropdown when clicking outside
        document.addEventListener('click', function() {
            notificationDropdown.classList.remove('active');
        });

        // Prevent closing when clicking inside the dropdown
        const notificationContent = notificationDropdown.querySelector('.notification-dropdown-content');
        if (notificationContent) {
            notificationContent.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }

        // FIXED: AJAX mark as read functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('notification-mark-read')) {
                e.preventDefault();
                
                const form = e.target.closest('form');
                const formData = new FormData(form);
                formData.append('ajax', '1');
                
                // Show loading state
                e.target.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Marking...';
                e.target.disabled = true;
                
                fetch('index.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the notification from UI
                        const notificationItem = e.target.closest('.notification-item');
                        if (notificationItem) {
                            notificationItem.style.opacity = '0.5';
                            setTimeout(() => {
                                notificationItem.remove();
                                
                                // Update notification count
                                const notificationCount = document.querySelector('.notification-count');
                                if (notificationCount) {
                                    const currentCount = parseInt(notificationCount.textContent);
                                    if (currentCount > 1) {
                                        notificationCount.textContent = currentCount - 1;
                                    } else {
                                        notificationCount.remove();
                                        // Show "no notifications" message if empty
                                        const notificationItems = document.querySelectorAll('.notification-item');
                                        if (notificationItems.length === 0) {
                                            const dropdownContent = document.querySelector('.notification-dropdown-content');
                                            dropdownContent.innerHTML = `
                                                <div class="no-notifications">
                                                    <i class="fas fa-bell-slash"></i>
                                                    <p>No new notifications</p>
                                                </div>
                                            `;
                                        }
                                    }
                                }
                            }, 300);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    e.target.innerHTML = 'Mark as read';
                    e.target.disabled = false;
                    alert('Error marking notification as read. Please try again.');
                });
            }
        });
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        if (navContainer && mobileMenuToggle && !event.target.closest('.nav-container') && !event.target.closest('.mobile-menu-toggle')) {
            navContainer.classList.remove('active');
            const icon = mobileMenuToggle.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
    });

    // Carousel functionality
    const carouselSlide = document.querySelector('.carousel-slide');
    const slides = document.querySelectorAll('.carousel-slide img');
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    
    if (carouselSlide && slides.length > 0) {
        const slideCount = slides.length - 1; 
        let index = 0;
        const intervalTime = 5000;
        const transitionDuration = 800;

        function showNextSlide() {
            index++;
            carouselSlide.style.transition = `transform ${transitionDuration}ms cubic-bezier(0.25, 0.46, 0.45, 0.94)`;
            carouselSlide.style.transform = `translateX(-${index * 100}%)`;

            if (index >= slideCount) {
                setTimeout(() => {
                    carouselSlide.style.transition = 'none';
                    index = 0;
                    carouselSlide.style.transform = `translateX(-${index * 100}%)`;
                }, transitionDuration); 
            }
        }

        function showPrevSlide() {
            index--;
            if (index < 0) {
                carouselSlide.style.transition = 'none';
                index = slideCount - 1;
                carouselSlide.style.transform = `translateX(-${index * 100}%)`;
                setTimeout(() => {
                    carouselSlide.style.transition = `transform ${transitionDuration}ms cubic-bezier(0.25, 0.46, 0.45, 0.94)`;
                    index--;
                    carouselSlide.style.transform = `translateX(-${index * 100}%)`;
                }, 50);
            } else {
                carouselSlide.style.transition = `transform ${transitionDuration}ms cubic-bezier(0.25, 0.46, 0.45, 0.94)`;
                carouselSlide.style.transform = `translateX(-${index * 100}%)`;
            }
        }

        let carouselInterval = setInterval(showNextSlide, intervalTime);

        // Pause on hover
        carouselSlide.addEventListener('mouseenter', () => {
            clearInterval(carouselInterval);
        });

        carouselSlide.addEventListener('mouseleave', () => {
            carouselInterval = setInterval(showNextSlide, intervalTime);
        });

        // Navigation buttons
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                clearInterval(carouselInterval);
                showNextSlide();
                carouselInterval = setInterval(showNextSlide, intervalTime);
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                clearInterval(carouselInterval);
                showPrevSlide();
                carouselInterval = setInterval(showNextSlide, intervalTime);
            });
        }
    }

    // Calendar functionality - FIXED VERSION
    let currentDate = new Date();
    let selectedDate = null;
    let selectedPhotographer = null;
    const bookedSlots = <?php echo json_encode($bookedSlots); ?>;
    const photographers = <?php echo json_encode($photographers); ?>;

    const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];

    function generateCalendar() {
        const calendarGrid = document.getElementById('calendar-grid');
        const currentMonthElement = document.getElementById('current-month');
        
        // Clear existing calendar
        if (!calendarGrid) {
            console.error('Calendar grid element not found!');
            return;
        }
        
        calendarGrid.innerHTML = '';
        
        // Set current month header
        if (currentMonthElement) {
            currentMonthElement.textContent = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
        }
        
        // Add day headers
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        days.forEach(day => {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day-header';
            dayElement.textContent = day;
            calendarGrid.appendChild(dayElement);
        });
        
        // Get first day of month and total days
        const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
        const totalDays = lastDay.getDate();
        const startingDay = firstDay.getDay();
        
        // Add empty cells for days before first day of month
        for (let i = 0; i < startingDay; i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'calendar-day other-month';
            calendarGrid.appendChild(emptyDay);
        }
        
        // Add days of current month
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        for (let day = 1; day <= totalDays; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            dayElement.textContent = day;
            
            const currentDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
            
            // Check if today
            if (currentDay.getTime() === today.getTime()) {
                dayElement.classList.add('today');
            }
            
            // Check if selected
            if (selectedDate && currentDay.getTime() === selectedDate.getTime()) {
                dayElement.classList.add('selected');
            }
            
            // Check if booked
            const dateString = formatDate(currentDay);
            const isBooked = bookedSlots.some(slot => slot.date === dateString);
            
            if (isBooked) {
                dayElement.classList.add('booked');
            } else if (currentDay >= today) {
                // Only allow future dates to be selected
                dayElement.addEventListener('click', () => selectDate(currentDay));
            } else {
                dayElement.style.opacity = '0.5';
                dayElement.style.cursor = 'not-allowed';
            }
            
            calendarGrid.appendChild(dayElement);
        }
        
        // Update photographer availability for selected date
        updatePhotographerAvailability();
        updateBookingSteps();
    }
    
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    function selectDate(date) {
        selectedDate = date;
        const dateInput = document.getElementById('selected-date');
        if (dateInput) {
            dateInput.value = formatDate(date);
        }
        updateSubmitButton();
        generateCalendar();
        updatePhotographerAvailability();
        updateBookingSummary();
        updateBookingSteps();
    }
    
    function updatePhotographerAvailability() {
        if (!selectedDate) return;
        
        const dateString = formatDate(selectedDate);
        const bookedForDate = bookedSlots.filter(slot => slot.date === dateString);
        
        photographers.forEach(photographer => {
            const statusElement = document.getElementById(`status-${photographer.id}`);
            const photographerCard = document.querySelector(`[data-photographer-id="${photographer.id}"]`);
            
            if (statusElement && photographerCard) {
                // Check if photographer has any bookings on this date
                const photographerBookings = bookedForDate.filter(slot => slot.photographer_id == photographer.id);
                
                if (photographerBookings.length > 0) {
                    statusElement.innerHTML = '<i class="fas fa-times-circle"></i> Busy';
                    statusElement.className = 'availability-status busy';
                    photographerCard.style.opacity = '0.6';
                    photographerCard.style.cursor = 'not-allowed';
                    photographerCard.onclick = null;
                } else {
                    statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Available';
                    statusElement.className = 'availability-status available';
                    photographerCard.style.opacity = '1';
                    photographerCard.style.cursor = 'pointer';
                    photographerCard.onclick = () => selectPhotographer(photographer.id);
                }
            }
        });
    }
    
    function selectPhotographer(photographerId) {
        // Remove selected class from all photographers
        document.querySelectorAll('.photographer-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Add selected class to clicked photographer
        const selectedCard = document.querySelector(`[data-photographer-id="${photographerId}"]`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }
        
        selectedPhotographer = photographerId;
        const photographerInput = document.getElementById('selected-photographer');
        if (photographerInput) {
            photographerInput.value = photographerId;
        }
        updateSubmitButton();
        updateBookingSummary();
        updateBookingSteps();
    }
    
    function updateDuration() {
        const startTime = document.getElementById('start_time');
        const endTime = document.getElementById('end_time');
        const durationDisplay = document.getElementById('duration-display');
        
        if (!startTime || !endTime || !durationDisplay) return;
        
        if (startTime.value && endTime.value) {
            const start = new Date(`2000-01-01T${startTime.value}`);
            const end = new Date(`2000-01-01T${endTime.value}`);
            const duration = (end - start) / (1000 * 60 * 60); // Convert to hours
            
            if (duration > 0) {
                durationDisplay.innerHTML = `<i class="fas fa-hourglass-half"></i> Duration: ${duration.toFixed(1)} hours`;
                durationDisplay.style.background = 'var(--gradient-accent)';
            } else {
                durationDisplay.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Invalid time range';
                durationDisplay.style.background = 'var(--gradient-secondary)';
            }
            
            updateSubmitButton();
            updateBookingSummary();
            updateBookingSteps();
        }
    }
    
    function updateSubmitButton() {
        const submitButton = document.getElementById('submit-booking');
        const startTime = document.getElementById('start_time');
        const endTime = document.getElementById('end_time');
        const customerName = document.getElementById('customer_name');
        const eventType = document.getElementById('event_type');
        const location = document.getElementById('location');
        
        if (!submitButton || !startTime || !endTime || !customerName || !eventType || !location) return;
        
        const isEnabled = selectedDate && selectedPhotographer && startTime.value && endTime.value && 
                         customerName.value &&
                         eventType.value &&
                         location.value;
        
        submitButton.disabled = !isEnabled;
        
        if (isEnabled) {
            submitButton.innerHTML = '<i class="fas fa-calendar-check"></i> CONFIRM BOOKING';
        } else {
            submitButton.innerHTML = '<i class="fas fa-lock"></i> COMPLETE ALL STEPS TO BOOK';
        }
    }
    
    function updateBookingSummary() {
        const summaryElement = document.getElementById('summary-details');
        const bookingSummary = document.getElementById('booking-summary');
        
        if (!summaryElement || !bookingSummary) return;
        
        if (selectedDate && selectedPhotographer) {
            const photographer = photographers.find(p => p.id == selectedPhotographer);
            const startTime = document.getElementById('start_time');
            const endTime = document.getElementById('end_time');
            const eventType = document.getElementById('event_type');
            const location = document.getElementById('location');
            const customerName = document.getElementById('customer_name');
            const notes = document.getElementById('notes');
            
            let summaryHTML = `
                <div style="display: grid; gap: 12px; font-size: 1rem;">
                    <p><strong>👤 Customer Name:</strong> ${customerName ? customerName.value : 'Not provided'}</p>
                    <p><strong>📅 Date:</strong> ${formatDate(selectedDate)}</p>
                    <p><strong>👨‍💼 Photographer:</strong> ${photographer ? photographer.fullname : 'Not selected'}</p>
            `;
            
            if (startTime && endTime && startTime.value && endTime.value) {
                const start = new Date(`2000-01-01T${startTime.value}`);
                const end = new Date(`2000-01-01T${endTime.value}`);
                const duration = (end - start) / (1000 * 60 * 60);
                
                summaryHTML += `<p><strong>⏰ Time:</strong> ${formatTime(startTime.value)} - ${formatTime(endTime.value)} (${duration.toFixed(1)} hours)</p>`;
            }
            
            if (eventType && eventType.value) {
                summaryHTML += `<p><strong>🎯 Event Type:</strong> ${eventType.value}</p>`;
            }
            
            if (location && location.value) {
                summaryHTML += `<p><strong>📍 Location:</strong> ${location.value}</p>`;
            }
            
            if (notes && notes.value) {
                summaryHTML += `<p><strong>📝 Notes:</strong> ${notes.value.substring(0, 50)}${notes.value.length > 50 ? '...' : ''}</p>`;
            }
            
            summaryHTML += `</div>`;
            summaryElement.innerHTML = summaryHTML;
            bookingSummary.style.display = 'block';
        } else {
            bookingSummary.style.display = 'none';
        }
    }
    
    function updateBookingSteps() {
        const steps = document.querySelectorAll('.step');
        steps.forEach((step, index) => {
            step.classList.remove('active');
        });
        
        // Activate steps based on progress
        if (selectedDate) {
            steps[0].classList.add('active');
        }
        if (selectedPhotographer) {
            steps[1].classList.add('active');
        }
        const eventType = document.getElementById('event_type');
        const location = document.getElementById('location');
        if (eventType && location && eventType.value && location.value) {
            steps[2].classList.add('active');
        }
        const submitButton = document.getElementById('submit-booking');
        if (submitButton && submitButton.disabled === false) {
            steps[3].classList.add('active');
        }
    }
    
    function formatTime(timeString) {
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const formattedHour = hour % 12 || 12;
        return `${formattedHour}:${minutes} ${ampm}`;
    }
    
    // Setup photographer selection
    function setupPhotographerSelection() {
        document.querySelectorAll('.photographer-card').forEach(card => {
            card.addEventListener('click', function() {
                const photographerId = this.getAttribute('data-photographer-id');
                if (!this.style.opacity || this.style.opacity !== '0.6') {
                    selectPhotographer(photographerId);
                }
            });
        });
    }

    // Modal functionality
    const bookingModal = document.getElementById('bookingModal');
    const modalClose = document.getElementById('modalClose');
    const modalButton = document.getElementById('modalButton');
    const modalIcon = document.getElementById('modalIcon');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalContent = document.getElementById('modalContent');

    function showModal(type, title, message) {
        if (!bookingModal || !modalContent) return;
        
        modalContent.className = 'modal-content';
        if (type === 'success') {
            modalContent.classList.add('modal-success');
            modalIcon.className = 'modal-icon fas fa-check-circle';
        } else {
            modalContent.classList.add('modal-error');
            modalIcon.className = 'modal-icon fas fa-times-circle';
        }
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        bookingModal.style.display = 'flex';
    }

    function hideModal() {
        if (bookingModal) {
            bookingModal.style.display = 'none';
        }
    }

    if (modalClose) {
        modalClose.addEventListener('click', hideModal);
    }
    
    if (modalButton) {
        modalButton.addEventListener('click', hideModal);
    }
    
    if (bookingModal) {
        bookingModal.addEventListener('click', (e) => {
            if (e.target === bookingModal) {
                hideModal();
            }
        });
    }

    // Form submission with better error handling
    const bookingForm = document.getElementById('booking-form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            const submitButton = document.getElementById('submit-booking');
            if (submitButton) {
                const originalText = submitButton.innerHTML;
                
                // Show loading state
                submitButton.innerHTML = '<div class="spinner"></div> PROCESSING...';
                submitButton.disabled = true;
                
                // Form will submit normally to index.php
                // The PHP code at the top will handle the processing
            }
        });
    }
    
    // Navigation event listeners
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    
    if (prevMonthBtn) {
        prevMonthBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            generateCalendar();
        });
    }
    
    if (nextMonthBtn) {
        nextMonthBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            generateCalendar();
        });
    }
    
    // Time input event listeners
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    
    if (startTimeInput) {
        startTimeInput.addEventListener('change', updateDuration);
    }
    
    if (endTimeInput) {
        endTimeInput.addEventListener('change', updateDuration);
    }
    
    // Form input event listeners
    const customerNameInput = document.getElementById('customer_name');
    const eventTypeInput = document.getElementById('event_type');
    const locationInput = document.getElementById('location');
    const notesInput = document.getElementById('notes');
    
    if (customerNameInput) {
        customerNameInput.addEventListener('input', function() {
            updateSubmitButton();
            updateBookingSummary();
            updateBookingSteps();
        });
    }
    
    if (eventTypeInput) {
        eventTypeInput.addEventListener('change', function() {
            updateSubmitButton();
            updateBookingSummary();
            updateBookingSteps();
        });
    }
    
    if (locationInput) {
        locationInput.addEventListener('input', function() {
            updateSubmitButton();
            updateBookingSummary();
            updateBookingSteps();
        });
    }
    
    if (notesInput) {
        notesInput.addEventListener('input', function() {
            updateBookingSummary();
        });
    }
    
    // Initialize everything
    generateCalendar();
    setupPhotographerSelection();
    updateBookingSteps();
    
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Check for booking message on page load and show modal
    <?php if($booking_message): ?>
        setTimeout(() => {
            const isSuccess = <?php echo strpos($booking_message, 'alert-success') !== false ? 'true' : 'false'; ?>;
            const title = isSuccess ? 'Booking Successful!' : 'Booking Failed';
            const message = `<?php echo addslashes(strip_tags($booking_message)); ?>`;
            showModal(isSuccess ? 'success' : 'error', title, message);
        }, 500);
    <?php endif; ?>
});

// FIXED: Global function for clearing filters
function clearFilters() {
    const searchInput = document.getElementById('search');
    const categorySelect = document.getElementById('category');
    const searchClear = document.getElementById('searchClear');
    
    if (searchInput) searchInput.value = '';
    if (categorySelect) categorySelect.value = '';
    
    // Trigger the search update
    if (searchInput) {
        searchInput.dispatchEvent(new Event('input'));
    }
    
    // Hide clear button
    if (searchClear) {
        searchClear.style.display = 'none';
    }
}

// FIXED: Enhanced notification functionality
document.addEventListener('DOMContentLoaded', function() {
    // Close notifications when clicking outside
    document.addEventListener('click', function(e) {
        const notificationDropdown = document.getElementById('notificationDropdown');
        if (notificationDropdown && !notificationDropdown.contains(e.target)) {
            notificationDropdown.classList.remove('active');
        }
    });
    
    // Handle notification mark as read with better UX
    const notificationMarkReadButtons = document.querySelectorAll('.notification-mark-read');
    notificationMarkReadButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const form = this.closest('form');
            const notificationItem = this.closest('.notification-item');
            
            // Show loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Marking...';
            this.disabled = true;
            
            // Submit form via AJAX
            const formData = new FormData(form);
            
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                // Remove notification with fade effect
                notificationItem.style.opacity = '0';
                notificationItem.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    notificationItem.remove();
                    
                    // Update notification count
                    updateNotificationCount();
                    
                    // If no notifications left, show empty state
                    const remainingNotifications = document.querySelectorAll('.notification-item');
                    if (remainingNotifications.length === 0) {
                        const dropdownContent = document.querySelector('.notification-dropdown-content');
                        dropdownContent.innerHTML = `
                            <div class="no-notifications">
                                <i class="fas fa-bell-slash"></i>
                                <p>No new notifications</p>
                            </div>
                        `;
                    }
                }, 300);
            })
            .catch(error => {
                console.error('Error:', error);
                this.innerHTML = originalText;
                this.disabled = false;
                alert('Error marking notification as read. Please try again.');
            });
        });
    });
    
    function updateNotificationCount() {
        const notificationCount = document.querySelector('.notification-count');
        const notificationItems = document.querySelectorAll('.notification-item');
        
        if (notificationCount) {
            if (notificationItems.length > 0) {
                notificationCount.textContent = notificationItems.length;
            } else {
                notificationCount.remove();
            }
        }
    }
});

// FIXED: Improved search functionality with space handling
function enhanceSearch() {
    const searchInput = document.getElementById('search');
    const categorySelect = document.getElementById('category');
    
    if (searchInput) {
        // Allow Enter key to trigger search
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                updateProducts();
            }
        });
    }
    
    if (categorySelect) {
        // Improve category selection
        categorySelect.addEventListener('focus', function() {
            this.style.borderColor = 'var(--accent-color)';
        });
        
        categorySelect.addEventListener('blur', function() {
            this.style.borderColor = 'rgba(255,255,255,0.3)';
        });
    }
}

// Initialize enhanced search
document.addEventListener('DOMContentLoaded', enhanceSearch);

// FIXED: Cart dropdown improvements
document.addEventListener('DOMContentLoaded', function() {
    const cartDropdown = document.querySelector('.cart-dropdown');
    
    if (cartDropdown) {
        // Close cart when clicking outside
        document.addEventListener('click', function(e) {
            if (!cartDropdown.contains(e.target)) {
                const cartItems = cartDropdown.querySelector('.cart-items');
                if (cartItems) {
                    cartItems.style.display = 'none';
                }
            }
        });
        
        // Improved hover behavior
        cartDropdown.addEventListener('mouseenter', function() {
            const cartItems = this.querySelector('.cart-items');
            if (cartItems) {
                cartItems.style.display = 'block';
            }
        });
        
        cartDropdown.addEventListener('mouseleave', function(e) {
            // Only hide if not hovering over cart items
            const cartItems = this.querySelector('.cart-items');
            if (cartItems && !cartItems.matches(':hover')) {
                setTimeout(() => {
                    if (!cartItems.matches(':hover')) {
                        cartItems.style.display = 'none';
                    }
                }, 300);
            }
        });
    }
});

// FIXED: Mobile responsiveness improvements
function handleMobileView() {
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // Mobile-specific adjustments
        const itemCards = document.querySelectorAll('.item-card');
        itemCards.forEach(card => {
            const actions = card.querySelector('.actions');
            if (actions) {
                // Ensure single column layout on mobile
                actions.style.gridTemplateColumns = '1fr';
            }
        });
        
        // Improve touch targets
        const buttons = document.querySelectorAll('button, .button, a');
        buttons.forEach(button => {
            if (button.offsetHeight < 44) {
                button.style.minHeight = '44px';
            }
        });
    }
}

// Initialize mobile handling
document.addEventListener('DOMContentLoaded', handleMobileView);
window.addEventListener('resize', handleMobileView);

// FIXED: Image error handling
function enhanceImageHandling() {
    const images = document.querySelectorAll('img');
    
    images.forEach(img => {
        img.addEventListener('error', function() {
            // Set placeholder image on error
            if (this.src && !this.src.includes('placeholder.com')) {
                this.src = 'https://via.placeholder.com/300x250/667eea/ffffff?text=Image+Not+Found';
            }
        });
        
        // Lazy loading for better performance
        img.loading = 'lazy';
    });
}

// Initialize image handling
document.addEventListener('DOMContentLoaded', enhanceImageHandling);
</script>
</body>
</html>