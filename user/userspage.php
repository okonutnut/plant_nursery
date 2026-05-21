<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

// Handle account activation/deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    $userID = (int)$_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'deactivate') {
        $updateSql = "UPDATE user SET IsActive = 0 WHERE UserID = $userID";
        if (mysqli_query($conn, $updateSql)) {
            $success = 'Account deactivated successfully!';
        } else {
            $error = 'Error deactivating account: ' . mysqli_error($conn);
        }
    } elseif ($action === 'activate') {
        $updateSql = "UPDATE user SET IsActive = 1 WHERE UserID = $userID";
        if (mysqli_query($conn, $updateSql)) {
            $success = 'Account activated successfully!';
        } else {
            $error = 'Error activating account: ' . mysqli_error($conn);
        }
    }
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // all, with_orders, without_orders, active, inactive

// Build query to get all users with customer role
$baseQuery = "
    SELECT u.*, 
           c.Name as CustomerName, 
           c.Email as CustomerEmail, 
           c.Phone as CustomerPhone, 
           c.Address as CustomerAddress,
           COUNT(DISTINCT o.OrderID) as OrderCount,
           COALESCE(SUM(o.TotalAmount), 0) as TotalSpent,
           u.IsActive,
           u.CreatedAt
    FROM user u
    LEFT JOIN customer c ON u.CustomerID = c.CustomerID
    LEFT JOIN `order` o ON u.CustomerID = o.CustomerID
    WHERE u.Role = 'customer'
";

if ($filter === 'with_orders') {
    $baseQuery .= " AND EXISTS (SELECT 1 FROM `order` WHERE CustomerID = u.CustomerID)";
} elseif ($filter === 'without_orders') {
    $baseQuery .= " AND NOT EXISTS (SELECT 1 FROM `order` WHERE CustomerID = u.CustomerID)";
} elseif ($filter === 'active') {
    $baseQuery .= " AND u.IsActive = 1";
} elseif ($filter === 'inactive') {
    $baseQuery .= " AND u.IsActive = 0";
}

$baseQuery .= " GROUP BY u.UserID ORDER BY u.CreatedAt DESC";

$result = mysqli_query($conn, $baseQuery);
$users = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

// Get counts
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM user WHERE Role = 'customer'"))['count'];
$activeUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM user WHERE Role = 'customer' AND IsActive = 1"))['count'];
$inactiveUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM user WHERE Role = 'customer' AND IsActive = 0"))['count'];
$usersWithOrders = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(DISTINCT u.UserID) as count 
    FROM user u
    INNER JOIN `order` o ON u.CustomerID = o.CustomerID
    WHERE u.Role = 'customer'
"))['count'];

$pageTitle = 'Users';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Users</h2>
    <p style="color: #666; margin-top: 0.5rem;">All registered users with customer role</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">Total Users</h5>
                <div class="count"><?php echo $totalUsers; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card h-100" style="background: linear-gradient(135deg, #d4edda, #28a745);">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">Active</h5>
                <div class="count" style="color: #155724;"><?php echo $activeUsers; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card h-100" style="background: linear-gradient(135deg, #f8d7da, #dc3545);">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">Inactive</h5>
                <div class="count" style="color: #721c24;"><?php echo $inactiveUsers; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card h-100" style="background: linear-gradient(135deg, #cce5ff, #2196F3);">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">With Orders</h5>
                <div class="count" style="color: #004085;"><?php echo $usersWithOrders; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">Filter Users</h5>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group" role="group">
                    <a href="?filter=all" class="btn btn-sm <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                    <a href="?filter=with_orders" class="btn btn-sm <?php echo $filter === 'with_orders' ? 'btn-primary' : 'btn-outline-primary'; ?>">With Orders</a>
                    <a href="?filter=without_orders" class="btn btn-sm <?php echo $filter === 'without_orders' ? 'btn-primary' : 'btn-outline-primary'; ?>">Without Orders</a>
                    <a href="?filter=active" class="btn btn-sm <?php echo $filter === 'active' ? 'btn-primary' : 'btn-outline-primary'; ?>">Active</a>
                    <a href="?filter=inactive" class="btn btn-sm <?php echo $filter === 'inactive' ? 'btn-primary' : 'btn-outline-primary'; ?>">Inactive</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h3 class="card-title mb-4" style="color: var(--primary-color);">All Users</h3>
        <?php if (count($users) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Username</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($users as $user): 
                            $displayName = $user['CustomerName'] ?? 'N/A';
                            $displayEmail = $user['CustomerEmail'] ?? $user['Email'] ?? 'N/A';
                            $displayPhone = $user['CustomerPhone'] ?? 'N/A';
                        ?>
                            <tr>
                                <td><?php echo $counter; ?></td>
                                <td><strong><?php echo htmlspecialchars($displayName); ?></strong></td>
                                <td><?php echo htmlspecialchars($displayEmail); ?></td>
                                <td><?php echo htmlspecialchars($displayPhone); ?></td>
                                <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                <td>
                                    <?php if ($user['OrderCount'] > 0): ?>
                                        <span class="badge bg-primary"><?php echo $user['OrderCount']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['TotalSpent'] > 0): ?>
                                        <strong>₱<?php echo number_format($user['TotalSpent'], 2); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">₱0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['IsActive'] == 1): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['CreatedAt'])); ?></td>
                                <td class="text-end">
                                    <?php if ($user['IsActive'] == 1): ?>
                                        <form method="POST" action="" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to deactivate this account?');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['UserID']; ?>">
                                            <input type="hidden" name="action" value="deactivate">
                                            <button type="submit" class="btn btn-danger btn-sm">Deactivate</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to activate this account?');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['UserID']; ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="btn btn-success btn-sm">Activate</button>
                                        </form>
                                    <?php endif; ?>
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
                <p class="text-muted mb-0">No users found with the selected filter.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

