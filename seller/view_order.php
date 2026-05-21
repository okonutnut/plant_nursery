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

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$orderID = (int)$_GET['id'];

$result = mysqli_query($conn, "
    SELECT o.*, c.Name as CustomerName, c.Email as CustomerEmail, c.Phone as CustomerPhone,
           c.Address as CustomerAddress, e.Name as EmployeeName, e.Role as EmployeeRole
    FROM `order` o
    LEFT JOIN customer c ON o.CustomerID = c.CustomerID
    LEFT JOIN employee e ON o.EmployeeID = e.EmployeeID
    WHERE o.OrderID = $orderID
");
$order = mysqli_fetch_assoc($result);

if (!$order) {
    header("Location: orders.php");
    exit;
}

$result2 = mysqli_query($conn, "
    SELECT oi.*, p.Name as PlantName, p.ScientificName, p.QuantityAvailable
    FROM orderitem oi
    LEFT JOIN plant p ON oi.PlantID = p.PlantID
    WHERE oi.OrderID = $orderID
");
$orderItems = mysqli_fetch_all($result2, MYSQLI_ASSOC);

$pageTitle = 'View Order #' . $orderID;
include 'includes/header.php';
?>

<div class="page-header">
    <h2>Order #<?php echo $orderID; ?></h2>
    <div>
        <?php if (isset($_GET['success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 0.75rem 1rem; border-radius: 5px; margin-bottom: 1rem; border-left: 4px solid #28a745; display: inline-block;">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 0.75rem 1rem; border-radius: 5px; margin-bottom: 1rem; border-left: 4px solid #dc3545; display: inline-block;">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        <?php if ($order['Status'] === 'Pending'): ?>
            <a href="approve_order.php?id=<?php echo $orderID; ?>" class="btn btn-primary" onclick="return confirm('Approve and complete this order? This will decrease plant inventory.')">✓ Approve Order</a>
        <?php endif; ?>
        <a href="orders.php" class="btn btn-secondary">← Back to Orders</a>
    </div>
</div>

        <div class="card">
            <h3>Order Information</h3>
            <table>
                <tr>
                    <th style="width: 200px;">Order ID</th>
                    <td>#<?php echo $order['OrderID']; ?></td>
                </tr>
                <tr>
                    <th>Order Date</th>
                    <td><?php echo date('F d, Y', strtotime($order['OrderDate'])); ?></td>
                </tr>
                <tr>
                    <th>Customer</th>
                    <td><?php echo htmlspecialchars($order['CustomerName'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?php echo htmlspecialchars($order['CustomerEmail'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Phone</th>
                    <td><?php echo htmlspecialchars($order['CustomerPhone'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Address</th>
                    <td><?php echo htmlspecialchars($order['CustomerAddress'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span style="padding: 0.5rem 1rem; border-radius: 20px; background-color: 
                            <?php 
                            echo $order['Status'] === 'Completed' ? '#d4edda' : 
                                ($order['Status'] === 'Pending' ? '#fff3cd' : '#f8d7da'); 
                            ?>; color: 
                            <?php 
                            echo $order['Status'] === 'Completed' ? '#155724' : 
                                ($order['Status'] === 'Pending' ? '#856404' : '#721c24'); 
                            ?>; font-weight: 500;">
                            <?php echo htmlspecialchars($order['Status']); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Total Amount</th>
                    <td style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                        ₱<?php echo number_format($order['TotalAmount'], 2); ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h3>Order Items</h3>
            <?php if (count($orderItems) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Plant</th>
                                <th>Scientific Name</th>
                                <th>Quantity</th>
                                <th>Available Stock</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): 
                                $availableStock = (int)($item['QuantityAvailable'] ?? 0);
                                $orderQuantity = (int)$item['Quantity'];
                                $hasEnoughStock = $availableStock >= $orderQuantity;
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['PlantName']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['ScientificName'] ?? 'N/A'); ?></td>
                                    <td><?php echo $item['Quantity']; ?></td>
                                    <td>
                                        <span style="color: <?php echo $hasEnoughStock ? '#28a745' : '#dc3545'; ?>; font-weight: 500;">
                                            <?php echo $availableStock; ?>
                                            <?php if (!$hasEnoughStock): ?>
                                                <span style="color: #dc3545;">⚠ Insufficient</span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>₱<?php echo number_format($item['Price'], 2); ?></td>
                                    <td>₱<?php echo number_format($item['Quantity'] * $item['Price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No items in this order</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>