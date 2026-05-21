<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Check if user is logged in and is admin/employee
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'employee')) {
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

$pageTitle = 'Refund Requests';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Refund Requests</h2>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title mb-4" style="color: var(--primary-color);">Statistics</h3>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <div class="display-4 fw-bold text-warning"><?php echo $pendingCount; ?></div>
                        <div class="text-warning">Pending</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <div class="display-4 fw-bold text-success"><?php echo $approvedCount; ?></div>
                        <div class="text-success">Approved</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <div class="display-4 fw-bold text-danger"><?php echo $rejectedCount; ?></div>
                        <div class="text-danger">Rejected</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <div class="display-4 fw-bold text-info"><?php echo $totalCount; ?></div>
                        <div class="text-info">Total</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="card-title mb-0" style="color: var(--primary-color);">All Refund Requests</h3>
            <div>
                <label for="statusFilter" class="form-label me-2">Filter:</label>
                <select class="form-select d-inline-block w-auto" id="statusFilter" onchange="filterByStatus()">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Approved" <?php echo $statusFilter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="Rejected" <?php echo $statusFilter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
        </div>
        
        <?php if (count($refunds) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Order Date</th>
                            <th>Customer</th>
                            <th>Plant</th>
                            <th>Quantity</th>
                            <th>Refund Amount</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
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
                                    <small class="text-muted"><?php echo htmlspecialchars($refund['CustomerEmail']); ?></small>
                                </td>
                                <td>
                                    <div><strong><?php echo htmlspecialchars($refund['PlantName']); ?></strong></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($refund['ScientificName'] ?? 'N/A'); ?></small>
                                    <?php if ($refund['Status'] === 'Pending'): ?>
                                        <br><small class="<?php echo $hasEnoughStock ? 'text-success' : 'text-danger'; ?> fw-semibold">
                                            Stock: <?php echo $availableQuantity; ?> 
                                            <?php if (!$hasEnoughStock): ?>
                                                <span class="text-danger">⚠ Insufficient</span>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $refund['Quantity']; ?> / <?php echo $refund['OrderQuantity']; ?></td>
                                <td><strong>$<?php echo number_format($refundAmount, 2); ?></strong></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($refund['RequestDate'])); ?>
                                    <br><small class="text-muted"><?php echo $daysSinceOrder; ?> days after order</small>
                                </td>
                                <td>
                                    <span class="badge" style="padding: 0.25rem 0.75rem; border-radius: 20px; background-color: 
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
                                <td class="text-end">
                                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                                        <a href="view_refund.php?id=<?php echo $refund['RefundID']; ?>" class="btn btn-success btn-sm">View</a>
                                        <?php if ($refund['Status'] === 'Pending'): ?>
                                            <?php if ($hasEnoughStock): ?>
                                                <a href="process_refund.php?id=<?php echo $refund['RefundID']; ?>&action=approve" class="btn btn-primary btn-sm" onclick="return confirm('Approve this refund request and send replacement? This will decrease inventory by <?php echo $refundQuantity; ?> units.')">Approve</a>
                                            <?php else: ?>
                                                <span class="btn btn-secondary btn-sm" style="opacity: 0.6; cursor: not-allowed;" title="Insufficient stock for replacement">Approve</span>
                                            <?php endif; ?>
                                            <a href="process_refund.php?id=<?php echo $refund['RefundID']; ?>&action=reject" class="btn btn-danger btn-sm" onclick="return confirm('Reject this refund request?')">Reject</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <p class="text-muted mb-0">No refund requests found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterByStatus() {
    const status = document.getElementById('statusFilter').value;
    window.location.href = 'refundpage.php?status=' + status;
}
</script>

<?php include '../includes/footer.php'; ?>

