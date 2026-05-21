<?php
require_once 'config/database.php';

// Get counts for dashboard
$counts = [];

$tables = ['plantcategory', 'supplier', 'plant', 'customer', 'employee', 'order'];
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM `$table`");
    $row = mysqli_fetch_assoc($result);
    $counts[$table] = $row['count'];
}

// Get pending refund requests count (if table exists)
$counts['pending_refunds'] = 0;
$refundResult = @mysqli_query($conn, "SELECT COUNT(*) as count FROM warranty_refund WHERE Status = 'Pending'");
if ($refundResult) {
    $refundRow = mysqli_fetch_assoc($refundResult);
    $counts['pending_refunds'] = $refundRow['count'];
}

// Get pending account approvals count
$counts['pending_approvals'] = 0;
$approvalResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM user WHERE IsActive = 0 AND Role != 'customer'");
if ($approvalResult) {
    $approvalRow = mysqli_fetch_assoc($approvalResult);
    $counts['pending_approvals'] = $approvalRow['count'];
}

// Get recent orders
$result = mysqli_query($conn, "
    SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.Status, 
           c.Name as CustomerName, e.Name as EmployeeName
    FROM `order` o
    LEFT JOIN customer c ON o.CustomerID = c.CustomerID
    LEFT JOIN employee e ON o.EmployeeID = e.EmployeeID
    ORDER BY o.OrderDate DESC
    LIMIT 5
");
$recentOrders = mysqli_fetch_all($result, MYSQLI_ASSOC);

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Dashboard</h2>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">Plant Categories</h5>
                <div class="count"><?php echo $counts['plantcategory']; ?></div>
                <a href="plantcategory/categorypage.php" class="text-decoration-none">View All →</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">Suppliers</h5>
                <div class="count"><?php echo $counts['supplier']; ?></div>
                <a href="supplier/supplierpage.php" class="text-decoration-none">View All →</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">Supplier Stock Overview</h5>
                <?php
                $stockSummary = mysqli_fetch_assoc(mysqli_query($conn, "
                    SELECT COUNT(p.PlantID) as TotalProducts,
                           COALESCE(SUM(p.QuantityAvailable), 0) as TotalQuantity,
                           COALESCE(SUM(p.QuantityAvailable * p.Price), 0) as TotalValue
                    FROM plant p
                "));
                ?>
                <div class="count" style="font-size: 1.5rem;"><?php echo $stockSummary['TotalQuantity']; ?></div>
                <small style="color: #666;">Total Items in Stock</small>
                <div style="margin-top: 0.5rem; font-size: 0.85rem;">
                    <span>₱<?php echo number_format($stockSummary['TotalValue'], 2); ?> Value</span>
                    <span style="margin: 0 0.5rem; color: #ddd;">|</span>
                    <span><?php echo $stockSummary['TotalProducts']; ?> Products</span>
                </div>
                <a href="plant/plantpage.php" class="text-decoration-none" style="display: block; margin-top: 0.25rem;">View Inventory →</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">Plants</h5>
                <div class="count"><?php echo $counts['plant']; ?></div>
                <a href="plant/plantpage.php" class="text-decoration-none">View All →</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">Customers</h5>
                <div class="count"><?php echo $counts['customer']; ?></div>
                <a href="customer/costumerpage.php" class="text-decoration-none">View All →</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">Employees</h5>
                <div class="count"><?php echo $counts['employee']; ?></div>
                <a href="employee/employeepage.php" class="text-decoration-none">View All →</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">Orders</h5>
                <div class="count"><?php echo $counts['order']; ?></div>
                <a href="order/orderpage.php" class="text-decoration-none">View All →</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card dashboard-card h-100" style="background: linear-gradient(135deg, #fff3cd, #ffc107);">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">Pending Refunds</h5>
                <div class="count" style="color: #856404;"><?php echo $counts['pending_refunds']; ?></div>
                <a href="refund/refundpage.php?status=Pending" style="color: #856404;" class="text-decoration-none">View All →</a>
            </div>
        </div>
    </div>
    
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card dashboard-card h-100" style="background: linear-gradient(135deg, #f8d7da, #dc3545);">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">Pending Account Approvals</h5>
                <div class="count" style="color: #721c24;"><?php echo $counts['pending_approvals']; ?></div>
                <a href="admin/approve_accounts.php" style="color: #721c24;" class="text-decoration-none">Review →</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <h3 class="card-title mb-4" style="color: var(--primary-color);">Recent Orders</h3>
        <?php if (count($recentOrders) > 0): ?>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $counter; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['OrderDate'])); ?></td>
                                <td><?php echo htmlspecialchars($order['CustomerName'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['EmployeeName'] ?? 'N/A'); ?></td>
                                <td>₱<?php echo number_format($order['TotalAmount'], 2); ?></td>
                                <td>
                                    <span class="badge" style="padding: 0.25rem 0.75rem; border-radius: 20px; background-color: 
                                        <?php 
                                        echo $order['Status'] === 'Completed' ? '#d4edda' : 
                                            ($order['Status'] === 'Pending' ? '#fff3cd' : '#f8d7da'); 
                                        ?>; color: 
                                        <?php 
                                        echo $order['Status'] === 'Completed' ? '#155724' : 
                                            ($order['Status'] === 'Pending' ? '#856404' : '#721c24'); 
                                        ?>;">
                                        <?php echo htmlspecialchars($order['Status']); ?>
                                    </span>
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
                <p class="text-muted mb-0">No orders found</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>