<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

$result = mysqli_query($conn, "
    SELECT o.*, c.Name as CustomerName, e.Name as EmployeeName
    FROM `order` o
    LEFT JOIN customer c ON o.CustomerID = c.CustomerID
    LEFT JOIN employee e ON o.EmployeeID = e.EmployeeID
    ORDER BY o.OrderDate DESC
");
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);

$countResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM `order`");
$totalCount = mysqli_fetch_assoc($countResult)['total'];

$totalRevenue = 0;
foreach ($orders as $order) {
    $totalRevenue += $order['TotalAmount'];
}

$pageTitle = 'Orders';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Orders</h2>
    <a href="create.php" class="btn btn-primary">Create New Order</a>
</div>

<div class="card">
    <div class="card-body">
        <h3 class="card-title mb-4" style="color: var(--primary-color);">All Orders</h3>
        <?php if (count($orders) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Employee</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $counter; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['OrderDate'])); ?></td>
                                <td><?php echo htmlspecialchars($order['CustomerName'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['EmployeeName'] ?? 'N/A'); ?></td>
                                <td>$<?php echo number_format($order['TotalAmount'], 2); ?></td>
                                <td>
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
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                                        <a href="view.php?id=<?php echo $order['OrderID']; ?>" class="btn btn-success btn-sm">View</a>
                                        <?php if ($order['Status'] === 'Pending'): ?>
                                            <a href="approve_order.php?id=<?php echo $order['OrderID']; ?>" class="btn btn-primary btn-sm" onclick="return confirm('Approve and complete this order? This will decrease plant inventory.')">Approve</a>
                                        <?php endif; ?>
                                        <a href="edit.php?id=<?php echo $order['OrderID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="delete.php?id=<?php echo $order['OrderID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                        $counter++;
                        endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <p class="text-muted mb-0">No orders found. <a href="create.php">Create one now</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

