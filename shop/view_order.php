<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: my_orders.php");
    exit;
}

$orderID = (int)$_GET['id'];

// Get customer ID from user email
$userEmail = $_SESSION['email'];
$customerResult = mysqli_query($conn, "SELECT CustomerID FROM customer WHERE Email = '$userEmail' LIMIT 1");
$customer = mysqli_fetch_assoc($customerResult);

$customerID = $customer ? $customer['CustomerID'] : 0;

// Get order details
$orderResult = mysqli_query($conn, "
    SELECT o.*, c.Name as CustomerName, c.Email as CustomerEmail, c.Phone as CustomerPhone, c.Address as CustomerAddress,
           e.Name as EmployeeName, e.Role as EmployeeRole
    FROM `order` o
    LEFT JOIN customer c ON o.CustomerID = c.CustomerID
    LEFT JOIN employee e ON o.EmployeeID = e.EmployeeID
    WHERE o.OrderID = $orderID AND o.CustomerID = $customerID
");
$order = mysqli_fetch_assoc($orderResult);

// Check if order is successful (prevents refunds)
// Handle backward compatibility if IsSuccessful column doesn't exist yet
$isOrderSuccessful = false;
if (isset($order['IsSuccessful'])) {
    $isOrderSuccessful = $order['IsSuccessful'] == 1;
}

if (!$order) {
    header("Location: my_orders.php");
    exit;
}

// Get order items with warranty and refund information
$itemsResult = mysqli_query($conn, "
    SELECT oi.*, p.Name as PlantName, p.ScientificName
    FROM orderitem oi
    LEFT JOIN plant p ON oi.PlantID = p.PlantID
    WHERE oi.OrderID = $orderID
");
$items = mysqli_fetch_all($itemsResult, MYSQLI_ASSOC);

// Calculate warranty status and get existing refund requests for each item
$orderDate = strtotime($order['OrderDate']);
$currentDate = time();
$warrantyDays = 30;
$warrantyExpiry = $orderDate + ($warrantyDays * 24 * 60 * 60);
$isWithinWarranty = ($currentDate <= $warrantyExpiry);
$daysRemaining = $isWithinWarranty ? ceil(($warrantyExpiry - $currentDate) / (24 * 60 * 60)) : 0;

// Check if order is completed (required for refund requests)
$isOrderCompleted = (strtolower($order['Status']) === 'completed');

// Get existing refund requests for this order
$refundRequests = [];
$refundResult = mysqli_query($conn, "
    SELECT wr.*, oi.PlantID, p.Name as PlantName
    FROM warranty_refund wr
    LEFT JOIN orderitem oi ON wr.OrderItemID = oi.OrderItemID
    LEFT JOIN plant p ON oi.PlantID = p.PlantID
    WHERE wr.OrderID = $orderID
");
if ($refundResult) {
    while ($refund = mysqli_fetch_assoc($refundResult)) {
        $refundRequests[$refund['OrderItemID']] = $refund;
    }
}

$pageTitle = 'Order Details';
include 'includes/header.php';
?>

<style>
    .order-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .order-card h2 {
            margin-top: 0;
            color: var(--primary-color);
        }
        .order-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 1.5rem;
        }
        .info-group {
            margin-bottom: 1rem;
        }
        .info-group label {
            display: block;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        .info-group .value {
            font-size: 1.1rem;
            color: #333;
            font-weight: 500;
        }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .items-table th {
            text-align: left;
            padding: 1rem;
            border-bottom: 2px solid #ddd;
            background: #f8f9fa;
            color: var(--primary-color);
        }
        .items-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        .total-row {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .btn-back {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 1rem;
        }
        .cart-badge {
            background: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 0.2rem 0.5rem;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        .warranty-info {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #2196F3;
        }
        .warranty-info.expired {
            background: #fff3cd;
            border-left-color: #ff9800;
        }
        .warranty-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }
        .warranty-active {
            background-color: #d4edda;
            color: #155724;
        }
        .warranty-expired {
            background-color: #f8d7da;
            color: #721c24;
        }
        .refund-status {
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .refund-pending {
            color: #856404;
            font-weight: 500;
        }
        .refund-approved {
            color: #155724;
            font-weight: 500;
        }
        .refund-rejected {
            color: #721c24;
            font-weight: 500;
        }
        .btn-request-refund {
            padding: 0.5rem 1rem;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            display: inline-block;
        }
        .btn-request-refund:hover {
            background: #c82333;
        }
        .btn-request-refund:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .modal-header h3 {
            margin: 0;
            color: var(--primary-color);
        }
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #000;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .btn-submit {
            width: 100%;
            padding: 0.75rem;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-submit:hover {
            background: #c82333;
        }
        .no-results {
            text-align: center;
            padding: 2rem;
            color: #666;
            display: none;
        }
    </style>

<div class="page-header mb-4">
    <h2><i class="fas fa-file-invoice"></i> Order Details</h2>
    <p class="mb-0">View order information and manage refunds</p>
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
        
        <div class="order-card">
            <h2>Order #<?php echo $order['OrderID']; ?></h2>
            
            <div class="order-info">
                <div>
                    <div class="info-group">
                        <label>Order Date</label>
                        <div class="value"><?php echo date('F d, Y', strtotime($order['OrderDate'])); ?></div>
                    </div>
                    <div class="info-group">
                        <label>Status</label>
                        <div class="value">
                            <span class="status-badge status-<?php echo strtolower($order['Status']); ?>">
                                <?php echo htmlspecialchars($order['Status']); ?>
                            </span>
                        </div>
                    </div>
                    <?php if (!empty($order['PaymentMethod'])): ?>
                    <div class="info-group">
                        <label>Payment Method</label>
                        <div class="value"><?php echo htmlspecialchars($order['PaymentMethod']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-group">
                        <label>Total Amount</label>
                        <div class="value" style="font-size: 1.5rem; color: var(--primary-color); font-weight: bold;">
                            $<?php echo number_format($order['TotalAmount'], 2); ?>
                        </div>
                    </div>
                    <div class="info-group">
                        <label>Warranty Status</label>
                        <div class="value">
                            <?php if ($isWithinWarranty): ?>
                                <span class="warranty-badge warranty-active">
                                    <i class="fas fa-check"></i> Active (<?php echo $daysRemaining; ?> days remaining)
                                </span>
                            <?php else: ?>
                                <span class="warranty-badge warranty-expired">
                                    <i class="fas fa-times"></i> Expired
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($isOrderCompleted): ?>
                    <div class="info-group">
                        <label>Order Status</label>
                        <div class="value">
                            <?php if ($isOrderSuccessful): ?>
                                <span class="warranty-badge warranty-active" style="background-color: #28a745; color: white;">
                                    <i class="fas fa-check-circle"></i> Successful Order
                                </span>
                            <?php else: ?>
                                <a href="mark_successful.php?id=<?php echo $orderID; ?>" class="btn-request-refund" style="background: #28a745; color: white; text-decoration: none;" onclick="return confirm('Mark this order as successful? Once marked, you will not be able to request refunds for this order.')">
                                    <i class="fas fa-check-circle"></i> Mark as Successful
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="info-group">
                        <label>Customer</label>
                        <div class="value"><?php echo htmlspecialchars($order['CustomerName'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-group">
                        <label>Email</label>
                        <div class="value"><?php echo htmlspecialchars($order['CustomerEmail'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-group">
                        <label>Phone</label>
                        <div class="value"><?php echo htmlspecialchars($order['CustomerPhone'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-group">
                        <label>Address</label>
                        <div class="value"><?php echo htmlspecialchars($order['CustomerAddress'] ?? 'N/A'); ?></div>
                    </div>
                    <?php if (strtolower($order['Status']) === 'pending'): ?>
                    <div class="info-group">
                        <label>Actions</label>
                        <div class="value">
                            <button class="btn-request-refund" style="background: #dc3545; color: white; text-decoration: none; border: none; cursor: pointer;" onclick="openCancelModal()">
                                <i class="fas fa-times-circle"></i> Cancel Order
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (strtolower($order['Status']) === 'cancelled' && !empty($order['CancellationReason'])): ?>
                    <div class="info-group">
                        <label>Cancellation Reason</label>
                        <div class="value" style="background: #f8d7da; color: #721c24; padding: 0.75rem; border-radius: 5px; margin-top: 0.25rem;">
                            <?php echo htmlspecialchars($order['CancellationReason']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="warranty-info <?php echo $isWithinWarranty ? '' : 'expired'; ?>">
            <strong><i class="fas fa-shield-alt"></i> 30-Day Warranty:</strong>
            <?php if ($isWithinWarranty): ?>
                Your order is covered by our 30-day warranty. If any plants die within this period, you can request a free replacement or refund.
                <strong><?php echo $daysRemaining; ?> days remaining</strong>
            <?php else: ?>
                The 30-day warranty period for this order has expired.
            <?php endif; ?>
            <?php if (strtolower($order['Status']) === 'cancelled'): ?>
                <br><br>
                <strong style="color: #721c24;"><i class="fas fa-times-circle"></i> Order Cancelled:</strong> This order has been cancelled. No further actions are available.
            <?php elseif (!$isOrderCompleted): ?>
                <br><br>
                <strong style="color: #856404;">⚠️ Note:</strong> Refund requests can only be made for orders with "Completed" status. Your order status is currently: <strong><?php echo htmlspecialchars($order['Status']); ?></strong>
            <?php elseif ($isOrderSuccessful): ?>
                <br><br>
                <strong style="color: #28a745;"><i class="fas fa-check-circle"></i> Order Marked as Successful:</strong> This order has been marked as successful. Refund requests are no longer available for this order.
            <?php endif; ?>
        </div>

        <div class="order-card">
            <h2>Order Items</h2>
            <div class="mb-3">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchItems" placeholder="Search by plant name or scientific name..." onkeyup="filterOrderItems()">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
            </div>
            <div class="no-results" id="noResults">
                <p>No items found matching your search.</p>
            </div>
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th>Plant</th>
                        <th>Scientific Name</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): 
                        $hasRefundRequest = isset($refundRequests[$item['OrderItemID']]);
                        $refundRequest = $hasRefundRequest ? $refundRequests[$item['OrderItemID']] : null;
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['PlantName']); ?></strong></td>
                            <td><?php echo htmlspecialchars($item['ScientificName'] ?? 'N/A'); ?></td>
                            <td><?php echo $item['Quantity']; ?></td>
                            <td>$<?php echo number_format($item['Price'], 2); ?></td>
                            <td>$<?php echo number_format($item['Quantity'] * $item['Price'], 2); ?></td>
                            <td>
                                <?php if ($isWithinWarranty && $isOrderCompleted && !$isOrderSuccessful): ?>
                                    <?php if ($hasRefundRequest): ?>
                                        <div class="refund-status refund-<?php echo strtolower($refundRequest['Status']); ?>">
                                            Refund Request: <strong><?php echo htmlspecialchars($refundRequest['Status']); ?></strong>
                                            <?php if ($refundRequest['Status'] == 'Pending'): ?>
                                                <br><small>Submitted on <?php echo date('M d, Y', strtotime($refundRequest['RequestDate'])); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <button class="btn-request-refund" onclick="openRefundModal(<?php echo $item['OrderItemID']; ?>, '<?php echo htmlspecialchars($item['PlantName'], ENT_QUOTES); ?>', <?php echo $item['Quantity']; ?>)">
                                            Request Refund
                                        </button>
                                    <?php endif; ?>
                                <?php elseif (strtolower($order['Status']) === 'cancelled'): ?>
                                    <span style="color: #721c24; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-times-circle"></i> Order Cancelled</span>
                                <?php elseif ($isOrderSuccessful): ?>
                                    <span style="color: #28a745; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-check-circle"></i> Order Successful</span>
                                <?php elseif (!$isOrderCompleted): ?>
                                    <span style="color: #856404; font-size: 0.9rem;">Order must be Completed</span>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 0.9rem;">Warranty Expired</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="5" style="text-align: right;">Total:</td>
                        <td>$<?php echo number_format($order['TotalAmount'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Cancel Order Modal -->
        <div id="cancelModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Cancel Order #<?php echo $orderID; ?></h3>
                    <span class="close" onclick="closeCancelModal()">&times;</span>
                </div>
                <form method="POST" action="cancel_order.php">
                    <input type="hidden" name="id" value="<?php echo $orderID; ?>">
                    
                    <div class="form-group">
                        <label for="reason">Reason for Cancellation *</label>
                        <textarea name="reason" id="cancel_reason" required placeholder="Please tell us why you want to cancel this order (e.g., changed my mind, found a better price, ordered by mistake, etc.)"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit" style="background: #dc3545;">Confirm Cancellation</button>
                    <button type="button" class="btn-submit" style="background: #6c757d; margin-top: 0.5rem;" onclick="closeCancelModal()">Keep Order</button>
                </form>
            </div>
        </div>

        <!-- Refund Request Modal -->
        <div id="refundModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Request Refund/Replacement</h3>
                    <span class="close" onclick="closeRefundModal()">&times;</span>
                </div>
                <form id="refundForm" method="POST" action="request_refund.php">
                    <input type="hidden" name="order_id" value="<?php echo $orderID; ?>">
                    <input type="hidden" name="order_item_id" id="order_item_id">
                    
                    <div class="form-group">
                        <label>Plant Name</label>
                        <input type="text" id="plant_name" readonly style="background: #f5f5f5;">
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantity to Refund *</label>
                        <input type="number" name="quantity" id="quantity" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="reason">Reason for Refund/Replacement *</label>
                        <textarea name="reason" id="reason" required placeholder="Please describe the problem with the plant/seed (e.g., plant died, seeds didn't germinate, damaged on arrival, etc.)"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">Submit Refund Request</button>
                </form>
            </div>
        </div>

    <script>
        function openCancelModal() {
            document.getElementById('cancelModal').style.display = 'block';
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
        }

        function openRefundModal(orderItemID, plantName, maxQuantity) {
            document.getElementById('order_item_id').value = orderItemID;
            document.getElementById('plant_name').value = plantName;
            document.getElementById('quantity').max = maxQuantity;
            document.getElementById('quantity').value = 1;
            document.getElementById('refundModal').style.display = 'block';
        }

        function closeRefundModal() {
            document.getElementById('refundModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const refundModal = document.getElementById('refundModal');
            const cancelModal = document.getElementById('cancelModal');
            if (event.target == refundModal) {
                closeRefundModal();
            }
            if (event.target == cancelModal) {
                closeCancelModal();
            }
        }

        // Search functionality for order items
        function filterOrderItems() {
            const input = document.getElementById('searchItems');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('itemsTable');
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = tbody.getElementsByTagName('tr');
            const noResults = document.getElementById('noResults');
            
            let visibleCount = 0;
            
            // Loop through all rows (except the total row)
            for (let i = 0; i < rows.length - 1; i++) {
                const row = rows[i];
                const plantName = row.cells[0].textContent.toLowerCase();
                const scientificName = row.cells[1].textContent.toLowerCase();
                
                if (plantName.includes(filter) || scientificName.includes(filter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
            
            // Show/hide no results message
            if (visibleCount === 0 && filter !== '') {
                noResults.style.display = 'block';
                table.style.display = 'none';
            } else {
                noResults.style.display = 'none';
                table.style.display = 'table';
            }
        }
    </script>

<?php include 'includes/footer.php'; ?>

