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

// Get filter status
$statusFilter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'all';

// Build query
$whereClause = '';
if ($statusFilter !== 'all') {
    $whereClause = "WHERE o.Status = '$statusFilter'";
}

$result = mysqli_query($conn, "
    SELECT o.*, c.Name as CustomerName, c.Email as CustomerEmail,
           e.Name as EmployeeName
    FROM `order` o
    LEFT JOIN customer c ON o.CustomerID = c.CustomerID
    LEFT JOIN employee e ON o.EmployeeID = e.EmployeeID
    $whereClause
    ORDER BY o.OrderDate DESC
");
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get counts
$pendingCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM `order` WHERE Status = 'Pending'"))['count'];
$completedCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM `order` WHERE Status = 'Completed'"))['count'];
$cancelledCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM `order` WHERE Status = 'Cancelled'"))['count'];
$totalCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM `order`"))['count'];

$pageTitle = 'Manage Orders';
include 'includes/header.php';
?>

<div class="page-header">
    <h2>Manage Orders</h2>
    <p style="color: #666; margin-top: 0.5rem;">Review and approve customer orders</p>
</div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <h3 style="margin: 0; color: var(--primary-color); font-size: 1.5rem; font-weight: 600;">All Orders</h3>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label for="statusFilter" style="font-weight: 500; color: var(--text-color);">
                        <i class="fas fa-filter"></i> Filter:
                    </label>
                        <select id="statusFilter" onchange="filterByStatus()" style="min-width: 150px;">
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                            <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo $statusFilter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card" style="border-left-color: #ffc107;">
                    <div class="number" style="color: #ffc107;"><?php echo $pendingCount; ?></div>
                    <div><i class="fas fa-clock"></i> Pending Orders</div>
                </div>
                <div class="stat-card" style="border-left-color: #28a745;">
                    <div class="number" style="color: #28a745;"><?php echo $completedCount; ?></div>
                    <div><i class="fas fa-check-circle"></i> Completed Orders</div>
                </div>
                <div class="stat-card" style="border-left-color: #dc3545;">
                    <div class="number" style="color: #dc3545;"><?php echo $cancelledCount; ?></div>
                    <div><i class="fas fa-times-circle"></i> Cancelled Orders</div>
                </div>
                <div class="stat-card" style="border-left-color: var(--primary-color);">
                    <div class="number" style="color: var(--primary-color);"><?php echo $totalCount; ?></div>
                    <div><i class="fas fa-list"></i> Total Orders</div>
                </div>
            </div>
            
            <?php if (count($orders) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Cancellation Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['OrderID']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['OrderDate'])); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($order['CustomerName'] ?? 'N/A'); ?></div>
                                        <small style="color: #666;"><?php echo htmlspecialchars($order['CustomerEmail'] ?? ''); ?></small>
                                    </td>
                                    <td><strong>₱<?php echo number_format($order['TotalAmount'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge" style="background-color: 
                                            <?php 
                                            echo $order['Status'] === 'Completed' ? '#d4edda' : 
                                                ($order['Status'] === 'Pending' ? '#fff3cd' : '#f8d7da'); 
                                            ?>; color: 
                                            <?php 
                                            echo $order['Status'] === 'Completed' ? '#155724' : 
                                                ($order['Status'] === 'Pending' ? '#856404' : '#721c24'); 
                                            ?>;">
                                            <?php if ($order['Status'] === 'Completed'): ?>
                                                <i class="fas fa-check-circle"></i>
                                            <?php elseif ($order['Status'] === 'Pending'): ?>
                                                <i class="fas fa-clock"></i>
                                            <?php elseif ($order['Status'] === 'Cancelled'): ?>
                                                <i class="fas fa-times-circle"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($order['Status']); ?>
                                        </span>
                                    </td>
                                    <td style="max-width: 200px;">
                                        <?php if ($order['Status'] === 'Cancelled' && !empty($order['CancellationReason'])): ?>
                                            <span style="color: #721c24; font-size: 0.9rem;"><?php echo htmlspecialchars($order['CancellationReason']); ?></span>
                                        <?php else: ?>
                                            <span style="color: #999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view_order.php?id=<?php echo $order['OrderID']; ?>" class="btn btn-success">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if ($order['Status'] === 'Pending'): ?>
                                                <a href="approve_order.php?id=<?php echo $order['OrderID']; ?>" class="btn btn-primary" onclick="return confirm('Approve and complete this order? This will decrease plant inventory.')">
                                                    <i class="fas fa-check"></i> Approve
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No orders found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
function filterByStatus() {
    const status = document.getElementById('statusFilter').value;
    window.location.href = 'orders.php?status=' + status;
}
</script>

<?php include 'includes/footer.php'; ?>