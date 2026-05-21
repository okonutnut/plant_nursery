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
    $whereClause = "WHERE wr.Status = '$statusFilter'";
}

$result = mysqli_query($conn, "
    SELECT wr.*, 
           o.OrderDate, o.OrderID,
           c.Name as CustomerName, c.Email as CustomerEmail,
           oi.PlantID, oi.Quantity as OrderQuantity, oi.Price as ItemPrice,
           p.Name as PlantName, p.ScientificName, p.QuantityAvailable,
           e.Name as ProcessedByName
    FROM warranty_refund wr
    LEFT JOIN `order` o ON wr.OrderID = o.OrderID
    LEFT JOIN customer c ON wr.CustomerID = c.CustomerID
    LEFT JOIN orderitem oi ON wr.OrderItemID = oi.OrderItemID
    LEFT JOIN plant p ON oi.PlantID = p.PlantID
    LEFT JOIN employee e ON wr.ProcessedBy = e.EmployeeID
    $whereClause
    ORDER BY wr.RequestDate DESC
");
$refunds = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get counts
$pendingCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM warranty_refund WHERE Status = 'Pending'"))['count'];
$approvedCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM warranty_refund WHERE Status = 'Approved'"))['count'];
$rejectedCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM warranty_refund WHERE Status = 'Rejected'"))['count'];
$totalCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM warranty_refund"))['count'];

$pageTitle = 'Manage Refunds';
include 'includes/header.php';
?>

<div class="page-header">
    <h2>Manage Refunds</h2>
    <p style="color: #666; margin-top: 0.5rem;">Process customer refund requests</p>
</div>

        <?php if (isset($_GET['success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border-left: 4px solid #28a745;">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border-left: 4px solid #dc3545;">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Statistics</h3>
            <div class="stats-grid">
                <div class="stat-card" style="background: #fff3cd; border-left-color: #ffc107;">
                    <div class="number" style="color: #856404;"><?php echo $pendingCount; ?></div>
                    <div style="color: #856404;">Pending</div>
                </div>
                <div class="stat-card" style="background: #d4edda; border-left-color: #28a745;">
                    <div class="number" style="color: #155724;"><?php echo $approvedCount; ?></div>
                    <div style="color: #155724;">Approved</div>
                </div>
                <div class="stat-card" style="background: #f8d7da; border-left-color: #dc3545;">
                    <div class="number" style="color: #721c24;"><?php echo $rejectedCount; ?></div>
                    <div style="color: #721c24;">Rejected</div>
                </div>
                <div class="stat-card" style="background: #e7f3ff; border-left-color: #2196F3;">
                    <div class="number" style="color: #004085;"><?php echo $totalCount; ?></div>
                    <div style="color: #004085;">Total</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0;">All Refund Requests</h3>
                <div>
                    <label for="statusFilter" style="margin-right: 0.5rem;">Filter:</label>
                    <select id="statusFilter" onchange="filterByStatus()">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo $statusFilter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo $statusFilter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
            </div>
            
            <?php if (count($refunds) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Order Date</th>
                                <th>Customer</th>
                                <th>Plant</th>
                                <th>Quantity</th>
                                <th>Refund Amount</th>
                                <th>Stock</th>
                                <th>Request Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($refunds as $refund): 
                                $refundAmount = $refund['Quantity'] * $refund['ItemPrice'];
                                $orderDate = strtotime($refund['OrderDate']);
                                $requestDate = strtotime($refund['RequestDate']);
                                $daysSinceOrder = floor(($requestDate - $orderDate) / (24 * 60 * 60));
                                $availableQuantity = (int)($refund['QuantityAvailable'] ?? 0);
                                $refundQuantity = (int)$refund['Quantity'];
                                $hasEnoughStock = $availableQuantity >= $refundQuantity;
                            ?>
                                <tr>
                                    <td>#<?php echo $refund['RefundID']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($refund['OrderDate'])); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($refund['CustomerName']); ?></div>
                                        <small style="color: #666;"><?php echo htmlspecialchars($refund['CustomerEmail']); ?></small>
                                    </td>
                                    <td>
                                        <div><strong><?php echo htmlspecialchars($refund['PlantName']); ?></strong></div>
                                        <small style="color: #666;"><?php echo htmlspecialchars($refund['ScientificName'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><?php echo $refund['Quantity']; ?> / <?php echo $refund['OrderQuantity']; ?></td>
                                    <td><strong>₱<?php echo number_format($refundAmount, 2); ?></strong></td>
                                    <td>
                                        <span style="color: <?php echo $hasEnoughStock ? '#28a745' : '#dc3545'; ?>; font-weight: 500;">
                                            <?php echo $availableQuantity; ?>
                                            <?php if (!$hasEnoughStock): ?>
                                                <span style="color: #dc3545;">⚠</span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($refund['RequestDate'])); ?>
                                        <br><small style="color: #666;"><?php echo $daysSinceOrder; ?> days after order</small>
                                    </td>
                                    <td>
                                        <span style="padding: 0.25rem 0.75rem; border-radius: 20px; background-color: 
                                            <?php 
                                            echo $refund['Status'] === 'Approved' ? '#d4edda' : 
                                                ($refund['Status'] === 'Pending' ? '#fff3cd' : '#f8d7da'); 
                                            ?>; color: 
                                            <?php 
                                            echo $refund['Status'] === 'Approved' ? '#155724' : 
                                                ($refund['Status'] === 'Pending' ? '#856404' : '#721c24'); 
                                            ?>;">
                                            <?php echo htmlspecialchars($refund['Status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view_refund.php?id=<?php echo $refund['RefundID']; ?>" class="btn btn-success">View</a>
                                            <?php if ($refund['Status'] === 'Pending'): ?>
                                                <?php if ($hasEnoughStock): ?>
                                                    <a href="process_refund.php?id=<?php echo $refund['RefundID']; ?>&action=approve" class="btn btn-primary" onclick="return confirm('Approve this refund request and send replacement? This will decrease inventory by <?php echo $refundQuantity; ?> units.')">Approve</a>
                                                <?php else: ?>
                                                    <span class="btn btn-secondary" style="opacity: 0.6; cursor: not-allowed;" title="Insufficient stock">Approve</span>
                                                <?php endif; ?>
                                                <a href="process_refund.php?id=<?php echo $refund['RefundID']; ?>&action=reject" class="btn btn-danger" onclick="return confirm('Reject this refund request?')">Reject</a>
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
                    <p>No refund requests found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
function filterByStatus() {
    const status = document.getElementById('statusFilter').value;
    window.location.href = 'refunds.php?status=' + status;
}
</script>

<?php include 'includes/footer.php'; ?>