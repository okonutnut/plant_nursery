<?php
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: orderpage.php");
    exit;
}

$id = $_GET['id'];

$result = mysqli_query($conn, "
    SELECT o.*, c.Name as CustomerName, c.Email as CustomerEmail, c.Phone as CustomerPhone,
           e.Name as EmployeeName, e.Role as EmployeeRole
    FROM `order` o
    LEFT JOIN customer c ON o.CustomerID = c.CustomerID
    LEFT JOIN employee e ON o.EmployeeID = e.EmployeeID
    WHERE o.OrderID = $id
");
$order = mysqli_fetch_assoc($result);

if (!$order) {
    header("Location: orderpage.php");
    exit;
}

$result2 = mysqli_query($conn, "
    SELECT oi.*, p.Name as PlantName, p.ScientificName
    FROM orderitem oi
    LEFT JOIN plant p ON oi.PlantID = p.PlantID
    WHERE oi.OrderID = $id
");
$orderItems = mysqli_fetch_all($result2, MYSQLI_ASSOC);

// Check if user is employee/admin for approve functionality
$canApprove = false;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['role']) && ($_SESSION['role'] === 'employee' || $_SESSION['role'] === 'admin')) {
    $canApprove = true;
}

$pageTitle = 'View Order #' . $id;
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Order #<?php echo $order['OrderID']; ?></h2>
    <div class="d-flex gap-2">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success mb-0"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger mb-0"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <?php if ($canApprove && $order['Status'] === 'Pending'): ?>
            <a href="approve_order.php?id=<?php echo $id; ?>" class="btn btn-primary" onclick="return confirm('Approve and complete this order? This will decrease plant inventory.')">✓ Approve Order</a>
        <?php endif; ?>
        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning">Edit</a>
        <a href="orderpage.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title mb-4" style="color: var(--primary-color);">Order Information</h3>
        <div class="row">
            <div class="col-md-6 mb-3">
                <strong>Order ID:</strong> #<?php echo $order['OrderID']; ?>
            </div>
            <div class="col-md-6 mb-3">
                <strong>Order Date:</strong> <?php echo date('F d, Y', strtotime($order['OrderDate'])); ?>
            </div>
            <div class="col-md-6 mb-3">
                <strong>Customer:</strong> <?php echo htmlspecialchars($order['CustomerName'] ?? 'N/A'); ?>
            </div>
            <div class="col-md-6 mb-3">
                <strong>Employee:</strong> <?php echo htmlspecialchars($order['EmployeeName'] ?? 'N/A'); ?> 
                <?php if ($order['EmployeeRole']): ?>
                    (<?php echo htmlspecialchars($order['EmployeeRole']); ?>)
                <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
                <strong>Status:</strong>
                <span class="badge" style="padding: 0.25rem 0.75rem; border-radius: 20px; background-color: 
                    <?php 
                    echo $order['Status'] === 'Completed' ? '#d4edda' : 
                        ($order['Status'] === 'Pending' ? '#fff3cd' : 
                            ($order['Status'] === 'Cancelled' ? '#f8d7da' : '#f8d7da')); 
                    ?>; color: 
                    <?php 
                    echo $order['Status'] === 'Completed' ? '#155724' : 
                        ($order['Status'] === 'Pending' ? '#856404' : 
                            ($order['Status'] === 'Cancelled' ? '#721c24' : '#721c24')); 
                    ?>;">
                    <?php echo htmlspecialchars($order['Status']); ?>
                </span>
            </div>
            <div class="col-md-6 mb-3">
                <strong>Total Amount:</strong>
                <span class="fs-4 fw-bold" style="color: var(--primary-color);">
                    $<?php echo number_format($order['TotalAmount'], 2); ?>
                </span>
            </div>
            <?php if (!empty($order['PaymentMethod'])): ?>
            <div class="col-md-6 mb-3">
                <strong>Payment Method:</strong>
                <span><?php echo htmlspecialchars($order['PaymentMethod']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($order['Status'] === 'Cancelled' && !empty($order['CancellationReason'])): ?>
            <div class="col-12 mb-3">
                <strong>Cancellation Reason:</strong>
                <div style="background: #f8d7da; color: #721c24; padding: 0.75rem; border-radius: 5px; margin-top: 0.25rem;">
                    <?php echo htmlspecialchars($order['CancellationReason']); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h3 class="card-title mb-4" style="color: var(--primary-color);">Order Items</h3>
        <?php if (count($orderItems) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Plant</th>
                            <th>Scientific Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['PlantName']); ?></td>
                                <td><?php echo htmlspecialchars($item['ScientificName'] ?? 'N/A'); ?></td>
                                <td><?php echo $item['Quantity']; ?></td>
                                <td>$<?php echo number_format($item['Price'], 2); ?></td>
                                <td>$<?php echo number_format($item['Quantity'] * $item['Price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <p class="text-muted mb-0">No items in this order</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

