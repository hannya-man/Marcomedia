<?php
session_name('website_session');
session_start();
require __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    
    // Verify the booking belongs to the user
    $verify_stmt = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND user_id = ?");
    $verify_stmt->bind_param("ii", $booking_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        // Soft delete by setting status to cancelled
        $cancel_stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
        $cancel_stmt->bind_param("i", $booking_id);
        
        if ($cancel_stmt->execute()) {
            $_SESSION['booking_message'] = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Booking #{$booking_id} has been cancelled successfully.</div>";
        } else {
            $_SESSION['booking_message'] = "<div class='alert alert-error'><i class='fas fa-times-circle'></i> Failed to cancel booking. Please try again.</div>";
        }
        $cancel_stmt->close();
    } else {
        $_SESSION['booking_message'] = "<div class='alert alert-error'><i class='fas fa-times-circle'></i> Booking not found or you don't have permission to cancel it.</div>";
    }
    
    $verify_stmt->close();
    header("Location: my_bookings.php");
    exit();
}

// FIXED: Fetch user's bookings with JOIN to get photographer name
$bookings = [];
$stmt = $conn->prepare("
    SELECT a.*, u.fullname as photographer_name 
    FROM appointments a 
    LEFT JOIN users u ON a.photographer_id = u.id
    WHERE a.user_id = ? 
    ORDER BY a.date DESC, a.created_at DESC
");

if ($stmt !== false) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Marcomedia</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reuse the same CSS from index.php */
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
            --border-radius: 16px;
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
            padding: 100px 20px 40px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 5%;
            height: 80px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(0,0,0,0.1);
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
        }

        .logo h2 {
            font-size: 2rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        nav a {
            color: var(--dark-text-color);
            text-decoration: none;
            font-weight: 600;
            padding: 12px 20px;
            transition: var(--transition);
            border-radius: var(--border-radius);
        }

        nav a:hover {
            background: var(--gradient-primary);
            color: white;
        }

        h1 {
            text-align: center;
            margin-bottom: 50px;
            font-size: 3rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

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

        .bookings-grid {
            display: grid;
            gap: 25px;
            margin-top: 30px;
        }

        .booking-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            border-left: 6px solid var(--primary-color);
            transition: var(--transition);
            position: relative;
        }

        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .booking-card.confirmed {
            border-left-color: var(--success-color);
        }

        .booking-card.cancelled {
            border-left-color: var(--error-color);
            opacity: 0.7;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .booking-id {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--secondary-color);
        }

        .booking-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8fafc;
            border-radius: var(--border-radius);
        }

        .detail-item i {
            color: var(--primary-color);
            font-size: 1.2rem;
            width: 20px;
        }

        .cancel-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f1f5f9;
            text-align: right;
        }

        .cancel-btn {
            background: var(--gradient-secondary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .cancel-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .cancel-btn:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--light-text-color);
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        @media (max-width: 768px) {
            .booking-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .booking-details {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="logo">
            <img src="images/rsz_logo.png" alt="Marcomedia Logo" class="logo-img">
            <h2>Marcomedia</h2>
        </a>
        <nav>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="index.php#photographer-booking"><i class="fas fa-camera"></i> New Booking</a>
        </nav>
    </header>

    <div class="container">
        <h1>My Bookings</h1>

        <?php if(isset($_SESSION['booking_message'])): ?>
            <?php echo $_SESSION['booking_message']; ?>
            <?php unset($_SESSION['booking_message']); ?>
        <?php endif; ?>

        <?php if(empty($bookings)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Bookings Yet</h3>
                <p>You haven't made any bookings yet. Start by booking a photographer for your next event!</p>
                <a href="index.php#photographer-booking" style="display: inline-block; margin-top: 20px; background: var(--gradient-primary); color: white; padding: 15px 30px; border-radius: var(--border-radius); text-decoration: none; font-weight: 600;">
                    <i class="fas fa-plus"></i> Book Now
                </a>
            </div>
        <?php else: ?>
            <div class="bookings-grid">
                <?php foreach($bookings as $booking): ?>
                    <div class="booking-card <?php echo $booking['status']; ?>">
                        <div class="booking-header">
                            <div class="booking-id">Booking #<?php echo $booking['id']; ?></div>
                            <div class="booking-status status-<?php echo $booking['status']; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </div>
                        </div>

                        <div class="booking-details">
                            <div class="detail-item">
                                <i class="fas fa-user"></i>
                                <div>
                                    <strong>Customer:</strong><br>
                                    <?php echo htmlspecialchars($booking['customer']); ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-user-camera"></i>
                                <div>
                                    <strong>Photographer:</strong><br>
                                    <?php echo htmlspecialchars($booking['photographer_name'] ?? 'Not assigned'); ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar"></i>
                                <div>
                                    <strong>Date:</strong><br>
                                    <?php echo date('F j, Y', strtotime($booking['date'])); ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <strong>Time:</strong><br>
                                    <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <strong>Location:</strong><br>
                                    <?php echo htmlspecialchars($booking['location']); ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-tag"></i>
                                <div>
                                    <strong>Event Type:</strong><br>
                                    <?php echo htmlspecialchars($booking['event_type']); ?>
                                </div>
                            </div>
                        </div>

                        <?php if(!empty($booking['notes'])): ?>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <i class="fas fa-sticky-note"></i>
                                <div>
                                    <strong>Notes:</strong><br>
                                    <?php echo htmlspecialchars($booking['notes']); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if($booking['status'] === 'confirmed'): ?>
                            <form method="POST" class="cancel-form" onsubmit="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.');">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <button type="submit" name="cancel_booking" class="cancel-btn">
                                    <i class="fas fa-times"></i> Cancel Booking
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>