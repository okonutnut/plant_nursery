<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$orderID = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderID > 0) {
    $orderResult = mysqli_query($conn, "
        SELECT o.*, c.Name as CustomerName
        FROM `order` o
        LEFT JOIN customer c ON o.CustomerID = c.CustomerID
        WHERE o.OrderID = $orderID
    ");
    $order = mysqli_fetch_assoc($orderResult);
    
    $itemsResult = mysqli_query($conn, "
        SELECT oi.*, p.Name as PlantName
        FROM orderitem oi
        LEFT JOIN plant p ON oi.PlantID = p.PlantID
        WHERE oi.OrderID = $orderID
    ");
    $items = mysqli_fetch_all($itemsResult, MYSQLI_ASSOC);
} else {
    header("Location: shop.php");
    exit;
}

$pageTitle = 'Order Confirmed';
include 'includes/header.php';
?>

<style>
    .success-card {
        background: white;
        border-radius: 10px;
        padding: 3rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        text-align: center;
    }
    .success-icon {
        font-size: 5rem;
        margin-bottom: 1rem;
    }
    .order-details {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 2rem;
        margin-top: 2rem;
        text-align: left;
    }
    .order-item {
        padding: 1rem 0;
        border-bottom: 1px solid #ddd;
    }
    .order-item:last-child {
        border-bottom: none;
    }
</style>

<div class="page-header mb-4">
    <h2><i class="fas fa-check-circle"></i> Order Confirmed</h2>
    <p class="mb-0">Your order has been placed successfully</p>
</div>

<div class="container" style="max-width: 800px;">
        <div class="success-card">
            <h2 style="color: var(--primary-color);">Thank you for your order!</h2>
            <p>Your order has been placed successfully.</p>
            
            <div class="order-details">
                <h3>Order Details</h3>
                <p><strong>Order ID:</strong> #<?php echo $order['OrderID']; ?></p>
                <p><strong>Order Date:</strong> <?php echo date('F d, Y', strtotime($order['OrderDate'])); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($order['Status']); ?></p>
                <p><strong>Total Amount:</strong> $<?php echo number_format($order['TotalAmount'], 2); ?></p>
                
                <h4 style="margin-top: 2rem;">Order Items:</h4>
                <?php foreach ($items as $item): ?>
                    <div class="order-item">
                        <strong><?php echo htmlspecialchars($item['PlantName']); ?></strong>
                        <br>
                        Quantity: <?php echo $item['Quantity']; ?> × $<?php echo number_format($item['Price'], 2); ?> = $<?php echo number_format($item['Quantity'] * $item['Price'], 2); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <a href="shop.php" class="btn btn-primary mt-3">Continue Shopping</a>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>

