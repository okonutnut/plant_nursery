<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

// Get customer ID from user email
$userEmail = $_SESSION['email'];
$customerResult = mysqli_query($conn, "SELECT CustomerID FROM customer WHERE Email = '$userEmail' LIMIT 1");
$customer = mysqli_fetch_assoc($customerResult);

$customerID = $customer ? $customer['CustomerID'] : 0;

// Get search parameter
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all orders for this customer with warranty status
$orders = [];
if ($customerID > 0) {
    // Build WHERE clause with search filter
    $whereClause = "o.CustomerID = $customerID";
    
    if (!empty($searchQuery)) {
        $searchEscaped = mysqli_real_escape_string($conn, $searchQuery);
        $whereClause .= " AND (
            o.OrderID LIKE '%$searchEscaped%' OR
            o.Status LIKE '%$searchEscaped%' OR
            DATE_FORMAT(o.OrderDate, '%M %d, %Y') LIKE '%$searchEscaped%' OR
            DATE_FORMAT(o.OrderDate, '%Y-%m-%d') LIKE '%$searchEscaped%' OR
            e.Name LIKE '%$searchEscaped%' OR
            o.TotalAmount LIKE '%$searchEscaped%'
        )";
    }
    
    $result = mysqli_query($conn, "
        SELECT o.*, e.Name as EmployeeName,
               DATEDIFF(CURDATE(), o.OrderDate) as DaysSinceOrder,
               CASE 
                   WHEN DATEDIFF(CURDATE(), o.OrderDate) <= 30 THEN 'Active'
                   ELSE 'Expired'
               END as WarrantyStatus
        FROM `order` o
        LEFT JOIN employee e ON o.EmployeeID = e.EmployeeID
        WHERE $whereClause
        ORDER BY o.OrderDate DESC
    ");
    $orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    // Handle IsSuccessful field (in case column doesn't exist yet)
    foreach ($orders as &$order) {
        if (!isset($order['IsSuccessful'])) {
            $order['IsSuccessful'] = 0;
        }
    }
    unset($order);
}

$pageTitle = 'My Orders';
include 'includes/header.php';
?>

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

<style>
    .orders-card {
        background: white;
        border-radius: 10px;
        padding: 2rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .orders-card h2 {
        margin-top: 0;
        color: var(--primary-color);
    }
    .orders-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    .orders-table th {
        text-align: left;
        padding: 1rem;
        border-bottom: 2px solid #ddd;
        color: var(--primary-color);
        background: #f8f9fa;
    }
    .orders-table td {
        padding: 1rem;
        border-bottom: 1px solid #eee;
    }
    .orders-table tr:hover {
        background: #f8f9fa;
    }
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.9rem;
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
    .btn-view {
        padding: 0.5rem 1rem;
        background: var(--primary-color);
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-size: 0.9rem;
    }
    .btn-view:hover {
        background: var(--secondary-color);
    }
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #666;
    }
    .nav-links {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .nav-links a {
        padding: 0.75rem 1.5rem;
        background: white;
        color: var(--primary-color);
        text-decoration: none;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: all 0.3s;
    }
    .nav-links a:hover {
        background: var(--primary-color);
        color: white;
    }
    .cart-badge {
        background: #ff4444;
        color: white;
        border-radius: 50%;
        padding: 0.2rem 0.5rem;
        font-size: 0.8rem;
        margin-left: 0.5rem;
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
    .no-results {
        text-align: center;
        padding: 2rem;
        color: #666;
        display: none;
    }
</style>

<div class="page-header mb-4">
    <h2><i class="fas fa-list-alt"></i> My Orders</h2>
    <p class="mb-0">View all your orders and purchases</p>
</div>

<?php
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

        <div class="orders-card">
            <h2>Order History</h2>
            
            <div class="mb-3">
                <form method="GET" action="my_orders.php">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchOrders" name="search" 
                               value="<?php echo htmlspecialchars($searchQuery); ?>" 
                               placeholder="Search by order ID, status, date, employee name, or amount..." 
                               onkeyup="filterOrders()">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="no-results" id="noResults">
                <p>No orders found matching your search.</p>
            </div>
            
            <?php if (count($orders) > 0): ?>
                <table class="orders-table" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Warranty</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <?php 
                        $counter = 1;
                        foreach ($orders as $order): 
                            $daysRemaining = 30 - (int)$order['DaysSinceOrder'];
                            $isWithinWarranty = $order['WarrantyStatus'] === 'Active';
                            $isOrderSuccessful = isset($order['IsSuccessful']) && $order['IsSuccessful'] == 1;
                            $isOrderCompleted = strtolower($order['Status']) === 'completed';
                        ?>
                            <tr>
                                <td>
                                    #<?php echo $counter; ?>
                                    <?php if ($isOrderSuccessful): ?>
                                        <span style="color: #28a745; font-weight: bold; margin-left: 0.5rem;" title="Order marked as successful"><i class="fas fa-check-circle"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['OrderDate'])); ?></td>
                                <td><?php echo htmlspecialchars($order['EmployeeName'] ?? 'N/A'); ?></td>
                                <td><strong>$<?php echo number_format($order['TotalAmount'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['Status']); ?>">
                                        <?php echo htmlspecialchars($order['Status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isWithinWarranty): ?>
                                        <span class="warranty-badge warranty-active" title="30-day warranty active">
                                            <i class="fas fa-check"></i> <?php echo $daysRemaining; ?> days left
                                        </span>
                                    <?php else: ?>
                                        <span class="warranty-badge warranty-expired" title="30-day warranty expired">
                                            <i class="fas fa-times"></i> Expired
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view_order.php?id=<?php echo $order['OrderID']; ?>" class="btn-view">View Details</a>
                                    <?php if (strtolower($order['Status']) === 'pending'): ?>
                                        <button class="btn-view" style="background: #dc3545; border: none; cursor: pointer;" onclick="openCancelModal(<?php echo $order['OrderID']; ?>)">Cancel</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                        $counter++;
                        endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <?php if (!empty($searchQuery)): ?>
                        <h3>No orders found</h3>
                        <p>No orders match your search criteria. Try a different search term.</p>
                        <a href="my_orders.php" style="display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: var(--primary-color); color: white; text-decoration: none; border-radius: 5px;">Clear Search</a>
                    <?php else: ?>
                        <h3>No orders yet</h3>
                        <p>You haven't placed any orders. Start shopping to make your first purchase!</p>
                        <a href="shop.php" style="display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: var(--primary-color); color: white; text-decoration: none; border-radius: 5px;">Browse Plants</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

<script>
    // Search functionality for orders (client-side filtering)
    function filterOrders() {
        const input = document.getElementById('searchOrders');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('ordersTable');
        const tbody = document.getElementById('ordersTableBody');
        const rows = tbody ? tbody.getElementsByTagName('tr') : [];
        const noResults = document.getElementById('noResults');
        
        let visibleCount = 0;
        
        // Loop through all rows
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const orderId = row.cells[0] ? row.cells[0].textContent.toLowerCase() : '';
            const date = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
            const employee = row.cells[2] ? row.cells[2].textContent.toLowerCase() : '';
            const amount = row.cells[3] ? row.cells[3].textContent.toLowerCase() : '';
            const status = row.cells[4] ? row.cells[4].textContent.toLowerCase() : '';
            const warranty = row.cells[5] ? row.cells[5].textContent.toLowerCase() : '';
            
            if (orderId.includes(filter) || date.includes(filter) || 
                employee.includes(filter) || amount.includes(filter) || 
                status.includes(filter) || warranty.includes(filter)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }
        
        // Show/hide no results message
        if (visibleCount === 0 && filter !== '') {
            noResults.style.display = 'block';
            if (table) table.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            if (table) table.style.display = 'table';
        }
    }
    
    // Apply filter on page load if there's a search query
    <?php if (!empty($searchQuery)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        filterOrders();
    });
    <?php endif; ?>
</script>

<!-- Cancel Order Modal -->
<div id="cancelModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: white; margin: 10% auto; padding: 2rem; border-radius: 10px; width: 90%; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; color: var(--primary-color);">Cancel Order</h3>
            <span class="close" onclick="closeCancelModal()" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        </div>
        <form method="POST" action="cancel_order.php">
            <input type="hidden" name="id" id="cancel_order_id">
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="reason" style="display: block; margin-bottom: 0.5rem; color: #333; font-weight: 500;">Reason for Cancellation *</label>
                <textarea name="reason" id="cancel_reason" required placeholder="Please tell us why you want to cancel this order (e.g., changed my mind, found a better price, ordered by mistake, etc.)" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; box-sizing: border-box; resize: vertical; min-height: 100px;"></textarea>
            </div>
            
            <button type="submit" style="width: 100%; padding: 0.75rem; background: #dc3545; color: white; border: none; border-radius: 5px; font-size: 1rem; cursor: pointer; font-weight: 600;">Confirm Cancellation</button>
            <button type="button" onclick="closeCancelModal()" style="width: 100%; padding: 0.75rem; background: #6c757d; color: white; border: none; border-radius: 5px; font-size: 1rem; cursor: pointer; font-weight: 600; margin-top: 0.5rem;">Keep Order</button>
        </form>
    </div>
</div>

<script>
    function openCancelModal(orderID) {
        document.getElementById('cancel_order_id').value = orderID;
        document.getElementById('cancelModal').style.display = 'block';
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('cancelModal');
        if (event.target == modal) {
            closeCancelModal();
        }
    }
</script>

<?php include 'includes/footer.php'; ?>

