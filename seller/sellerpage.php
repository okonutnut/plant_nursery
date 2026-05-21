<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Check if user is logged in and is a seller/staff/employee
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'seller' && $_SESSION['role'] !== 'employee' && $_SESSION['role'] !== 'staff')) {
    header("Location: ../login.php");
    exit;
}

// Get employee ID
$userID = $_SESSION['user_id'];
$userResult = mysqli_query($conn, "SELECT EmployeeID, Username FROM user WHERE UserID = $userID");
$user = mysqli_fetch_assoc($userResult);
$employeeID = $user['EmployeeID'] ?? null;

// Get pending orders count
$pendingOrdersResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM `order` WHERE Status = 'Pending'");
$pendingOrdersCount = mysqli_fetch_assoc($pendingOrdersResult)['count'];

// Get pending refunds count
$pendingRefundsResult = @mysqli_query($conn, "SELECT COUNT(*) as count FROM warranty_refund WHERE Status = 'Pending'");
$pendingRefundsCount = $pendingRefundsResult ? mysqli_fetch_assoc($pendingRefundsResult)['count'] : 0;

// Get completed orders today
$todayCompletedResult = mysqli_query($conn, "
    SELECT COUNT(*) as count 
    FROM `order` 
    WHERE Status = 'Completed' 
    AND DATE(OrderDate) = CURDATE()
");
$todayCompletedCount = mysqli_fetch_assoc($todayCompletedResult)['count'];

// Get total revenue today
$todayRevenueResult = mysqli_query($conn, "
    SELECT COALESCE(SUM(TotalAmount), 0) as revenue 
    FROM `order` 
    WHERE Status = 'Completed' 
    AND DATE(OrderDate) = CURDATE()
");
$todayRevenue = mysqli_fetch_assoc($todayRevenueResult)['revenue'];

// Get recent pending orders
$recentPendingOrders = [];
$ordersResult = mysqli_query($conn, "
    SELECT o.*, c.Name as CustomerName, c.Email as CustomerEmail
    FROM `order` o
    LEFT JOIN customer c ON o.CustomerID = c.CustomerID
    WHERE o.Status = 'Pending'
    ORDER BY o.OrderDate DESC
    LIMIT 5
");
if ($ordersResult) {
    $recentPendingOrders = mysqli_fetch_all($ordersResult, MYSQLI_ASSOC);
}

// Get recent pending refunds
$recentPendingRefunds = [];
$refundsResult = @mysqli_query($conn, "
    SELECT wr.*, c.Name as CustomerName, p.Name as PlantName, oi.PlantID, p.QuantityAvailable
    FROM warranty_refund wr
    LEFT JOIN customer c ON wr.CustomerID = c.CustomerID
    LEFT JOIN orderitem oi ON wr.OrderItemID = oi.OrderItemID
    LEFT JOIN plant p ON oi.PlantID = p.PlantID
    WHERE wr.Status = 'Pending'
    ORDER BY wr.RequestDate DESC
    LIMIT 5
");
if ($refundsResult) {
    $recentPendingRefunds = mysqli_fetch_all($refundsResult, MYSQLI_ASSOC);
}

$pageTitle = 'Seller Dashboard';
include 'includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Seller Dashboard</h2>
    <p style="color: #666; margin-top: 0.5rem;">Manage customer orders and refunds</p>
</div>

<div class="dashboard-grid mb-4">

    <div class="dashboard-card" style="background: linear-gradient(135deg, #fff3cd, #ffc107); border-top-color: #ffc107;">
        <h3><i class="fas fa-clock"></i> Pending Orders</h3>
        <div class="count" style="color: #856404;"><?php echo $pendingOrdersCount; ?></div>
        <a href="orders.php?status=Pending"><i class="fas fa-arrow-right"></i> View All</a>
    </div>
    
    <div class="dashboard-card" style="background: linear-gradient(135deg, #fff3cd, #ffc107); border-top-color: #ffc107;">
        <h3><i class="fas fa-peso-sign"></i> Pending Refunds</h3>
        <div class="count" style="color: #856404;"><?php echo $pendingRefundsCount; ?></div>
        <a href="refunds.php?status=Pending"><i class="fas fa-arrow-right"></i> View All</a>
    </div>
    
    <div class="dashboard-card" style="background: linear-gradient(135deg, #d4edda, #28a745); border-top-color: #28a745;">
        <h3><i class="fas fa-check-circle"></i> Completed Today</h3>
        <div class="count" style="color: #155724;"><?php echo $todayCompletedCount; ?></div>
        <a href="orders.php?status=Completed"><i class="fas fa-arrow-right"></i> View All</a>
    </div>
    
    <div class="dashboard-card" style="background: linear-gradient(135deg, #cce5ff, #2196F3); border-top-color: #2196F3;">
        <h3><i class="fas fa-money-bill-wave"></i> Revenue Today</h3>
        <div class="count" style="color: #004085;">₱<?php echo number_format($todayRevenue, 2); ?></div>
        <a href="orders.php?status=Completed"><i class="fas fa-arrow-right"></i> View Orders</a>
    </div>
</div>

        <div class="card mb-4">
            <h3>Recent Pending Orders</h3>
            <?php if (count($recentPendingOrders) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Total Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPendingOrders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['OrderID']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['OrderDate'])); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($order['CustomerName'] ?? 'N/A'); ?></div>
                                        <small style="color: #666;"><?php echo htmlspecialchars($order['CustomerEmail'] ?? ''); ?></small>
                                    </td>
                                    <td><strong>₱<?php echo number_format($order['TotalAmount'], 2); ?></strong></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view_order.php?id=<?php echo $order['OrderID']; ?>" class="btn btn-success">View</a>
                                            <a href="approve_order.php?id=<?php echo $order['OrderID']; ?>" class="btn btn-primary" onclick="return confirm('Approve and complete this order? This will decrease plant inventory.')">Approve</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 1rem; text-align: center;">
                    <a href="orders.php?status=Pending" class="btn btn-secondary">View All Pending Orders</a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No pending orders at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card mb-4">
            <h3>Recent Pending Refunds</h3>
            <?php if (count($recentPendingRefunds) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Refund ID</th>
                                <th>Customer</th>
                                <th>Plant</th>
                                <th>Quantity</th>
                                <th>Stock Available</th>
                                <th>Request Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPendingRefunds as $refund): 
                                $availableQuantity = (int)($refund['QuantityAvailable'] ?? 0);
                                $refundQuantity = (int)$refund['Quantity'];
                                $hasEnoughStock = $availableQuantity >= $refundQuantity;
                            ?>
                                <tr>
                                    <td>#<?php echo $refund['RefundID']; ?></td>
                                    <td><?php echo htmlspecialchars($refund['CustomerName'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($refund['PlantName'] ?? 'N/A'); ?></td>
                                    <td><?php echo $refund['Quantity']; ?></td>
                                    <td>
                                        <span style="color: <?php echo $hasEnoughStock ? '#28a745' : '#dc3545'; ?>; font-weight: 500;">
                                            <?php echo $availableQuantity; ?>
                                            <?php if (!$hasEnoughStock): ?>
                                                <span style="color: #dc3545;">⚠</span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($refund['RequestDate'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view_refund.php?id=<?php echo $refund['RefundID']; ?>" class="btn btn-success">View</a>
                                            <?php if ($hasEnoughStock): ?>
                                                <a href="process_refund.php?id=<?php echo $refund['RefundID']; ?>&action=approve" class="btn btn-primary" onclick="return confirm('Approve this refund request and send replacement? This will decrease inventory by <?php echo $refundQuantity; ?> units.')">Approve</a>
                                            <?php else: ?>
                                                <span class="btn btn-secondary" style="opacity: 0.6; cursor: not-allowed;" title="Insufficient stock">Approve</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 1rem; text-align: center;">
                    <a href="refunds.php?status=Pending" class="btn btn-secondary">View All Pending Refunds</a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No pending refund requests at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>