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

if (!isset($_GET['id'])) {
    header("Location: refundpage.php");
    exit;
}

$refundID = (int)$_GET['id'];

$result = mysqli_query($conn, "
    SELECT wr.*, 
           o.OrderDate, o.OrderID, o.TotalAmount as OrderTotal,
           c.Name as CustomerName, c.Email as CustomerEmail, c.Phone as CustomerPhone, c.Address as CustomerAddress,
           oi.PlantID, oi.Quantity as OrderQuantity, oi.Price as ItemPrice,
           p.Name as PlantName, p.ScientificName, p.QuantityAvailable,
           e.Name as ProcessedByName, e.Role as ProcessedByRole
    FROM warranty_refund wr
    LEFT JOIN `order` o ON wr.OrderID = o.OrderID
    LEFT JOIN customer c ON wr.CustomerID = c.CustomerID
    LEFT JOIN orderitem oi ON wr.OrderItemID = oi.OrderItemID
    LEFT JOIN plant p ON oi.PlantID = p.PlantID
    LEFT JOIN employee e ON wr.ProcessedBy = e.EmployeeID
    WHERE wr.RefundID = $refundID
");
$refund = mysqli_fetch_assoc($result);

if (!$refund) {
    header("Location: refundpage.php");
    exit;
}

$refundAmount = $refund['Quantity'] * $refund['ItemPrice'];
$orderDate = strtotime($refund['OrderDate']);
$requestDate = strtotime($refund['RequestDate']);
$daysSinceOrder = floor(($requestDate - $orderDate) / (24 * 60 * 60));
$warrantyDaysRemaining = 30 - $daysSinceOrder;

$pageTitle = 'View Refund Request #' . $refundID;
include '../includes/header.php';
?>

<div class="page-header">
    <h2>Refund Request #<?php echo $refundID; ?></h2>
    <div>
        <a href="refundpage.php" class="btn btn-secondary">Back</a>
        <?php if ($refund['Status'] === 'Pending'): 
            $availableQuantity = (int)($refund['QuantityAvailable'] ?? 0);
            $refundQuantity = (int)$refund['Quantity'];
            $hasEnoughStock = $availableQuantity >= $refundQuantity;
        ?>
            <?php if ($hasEnoughStock): ?>
                <a href="process_refund.php?id=<?php echo $refundID; ?>&action=approve" class="btn btn-primary" onclick="return confirm('Approve this refund request and send replacement? This will decrease inventory by <?php echo $refundQuantity; ?> units.')">Approve & Send Replacement</a>
            <?php else: ?>
                <span class="btn btn-secondary" style="opacity: 0.6; cursor: not-allowed;" title="Insufficient stock for replacement">Cannot Approve (Low Stock)</span>
            <?php endif; ?>
            <a href="process_refund.php?id=<?php echo $refundID; ?>&action=reject" class="btn btn-danger" onclick="return confirm('Reject this refund request?')">Reject</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Refund Request Information</h3>
    <table>
        <tr>
            <th style="width: 200px;">Refund ID</th>
            <td>#<?php echo $refund['RefundID']; ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <td>
                <span style="padding: 0.5rem 1rem; border-radius: 20px; background-color: 
                    <?php 
                    echo $refund['Status'] === 'Approved' ? '#d4edda' : 
                        ($refund['Status'] === 'Pending' ? '#fff3cd' : '#f8d7da'); 
                    ?>; color: 
                    <?php 
                    echo $refund['Status'] === 'Approved' ? '#155724' : 
                        ($refund['Status'] === 'Pending' ? '#856404' : '#721c24'); 
                    ?>; font-weight: 500;">
                    <?php echo htmlspecialchars($refund['Status']); ?>
                </span>
            </td>
        </tr>
        <tr>
            <th>Request Date</th>
            <td><?php echo date('F d, Y, g:i A', strtotime($refund['RequestDate'])); ?></td>
        </tr>
        <tr>
            <th>Order Date</th>
            <td><?php echo date('F d, Y', strtotime($refund['OrderDate'])); ?> (<?php echo $daysSinceOrder; ?> days before request)</td>
        </tr>
        <tr>
            <th>Warranty Status</th>
            <td>
                <?php if ($warrantyDaysRemaining > 0): ?>
                    <span style="color: #28a745; font-weight: 500;">✓ Within Warranty (<?php echo $warrantyDaysRemaining; ?> days remaining)</span>
                <?php else: ?>
                    <span style="color: #dc3545; font-weight: 500;">✗ Warranty Expired</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php if ($refund['ProcessedBy']): ?>
        <tr>
            <th>Processed By</th>
            <td><?php echo htmlspecialchars($refund['ProcessedByName']); ?> (<?php echo htmlspecialchars($refund['ProcessedByRole'] ?? 'N/A'); ?>)</td>
        </tr>
        <tr>
            <th>Processed Date</th>
            <td><?php echo date('F d, Y, g:i A', strtotime($refund['ProcessedDate'])); ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<div class="card">
    <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Order Information</h3>
    <table>
        <tr>
            <th style="width: 200px;">Order ID</th>
            <td><a href="../order/view.php?id=<?php echo $refund['OrderID']; ?>">#<?php echo $refund['OrderID']; ?></a></td>
        </tr>
        <tr>
            <th>Plant</th>
            <td>
                <strong><?php echo htmlspecialchars($refund['PlantName']); ?></strong>
                <br><small style="color: #666;"><?php echo htmlspecialchars($refund['ScientificName'] ?? 'N/A'); ?></small>
            </td>
        </tr>
        <tr>
            <th>Order Quantity</th>
            <td><?php echo $refund['OrderQuantity']; ?> units</td>
        </tr>
        <tr>
            <th>Refund Quantity</th>
            <td><strong><?php echo $refund['Quantity']; ?> units</strong></td>
        </tr>
        <tr>
            <th>Unit Price</th>
            <td>$<?php echo number_format($refund['ItemPrice'], 2); ?></td>
        </tr>
        <tr>
            <th>Available Stock</th>
            <td>
                <?php 
                $availableQuantity = (int)($refund['QuantityAvailable'] ?? 0);
                $refundQuantity = (int)$refund['Quantity'];
                $hasEnoughStock = $availableQuantity >= $refundQuantity;
                ?>
                <strong style="color: <?php echo $hasEnoughStock ? '#28a745' : '#dc3545'; ?>;">
                    <?php echo $availableQuantity; ?> units
                </strong>
                <?php if ($refund['Status'] === 'Pending'): ?>
                    <?php if ($hasEnoughStock): ?>
                        <span style="color: #28a745; margin-left: 0.5rem;">✓ Sufficient for replacement</span>
                    <?php else: ?>
                        <span style="color: #dc3545; margin-left: 0.5rem;">⚠ Insufficient stock (need <?php echo $refundQuantity; ?>, have <?php echo $availableQuantity; ?>)</span>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Refund Amount</th>
            <td style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                $<?php echo number_format($refundAmount, 2); ?>
            </td>
        </tr>
    </table>
</div>

<div class="card">
    <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Customer Information</h3>
    <table>
        <tr>
            <th style="width: 200px;">Name</th>
            <td><?php echo htmlspecialchars($refund['CustomerName']); ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?php echo htmlspecialchars($refund['CustomerEmail']); ?></td>
        </tr>
        <tr>
            <th>Phone</th>
            <td><?php echo htmlspecialchars($refund['CustomerPhone'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <th>Address</th>
            <td><?php echo htmlspecialchars($refund['CustomerAddress'] ?? 'N/A'); ?></td>
        </tr>
    </table>
</div>

<div class="card">
    <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Reason for Refund</h3>
    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 5px; border-left: 4px solid var(--primary-color);">
        <?php echo nl2br(htmlspecialchars($refund['Reason'])); ?>
    </div>
</div>

<?php if ($refund['Notes']): ?>
<div class="card">
    <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Admin Notes</h3>
    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 5px; border-left: 4px solid #6c757d;">
        <?php echo nl2br(htmlspecialchars($refund['Notes'])); ?>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

