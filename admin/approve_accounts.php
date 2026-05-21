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

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $userID = (int)$_POST['user_id'];
        $action = $_POST['action'];
        
        if ($action === 'approve') {
            $updateSql = "UPDATE user SET IsActive = 1 WHERE UserID = $userID";
            if (mysqli_query($conn, $updateSql)) {
                $success = 'Account approved successfully!';
            } else {
                $error = 'Error approving account: ' . mysqli_error($conn);
            }
        } elseif ($action === 'reject') {
            // Delete the user and associated records
            mysqli_begin_transaction($conn);
            try {
                // Get user info
                $userResult = mysqli_query($conn, "SELECT EmployeeID, CustomerID, SupplierID FROM user WHERE UserID = $userID");
                $userData = mysqli_fetch_assoc($userResult);
                
                // Delete user
                mysqli_query($conn, "DELETE FROM user WHERE UserID = $userID");
                
                // Delete employee if exists
                if ($userData['EmployeeID']) {
                    mysqli_query($conn, "DELETE FROM employee WHERE EmployeeID = " . $userData['EmployeeID']);
                }
                
                // Delete supplier if exists
                if ($userData['SupplierID']) {
                    mysqli_query($conn, "DELETE FROM supplier WHERE SupplierID = " . $userData['SupplierID']);
                }
                
                // Delete customer if exists (only if not linked to orders)
                if ($userData['CustomerID']) {
                    // Check if customer has orders
                    $orderCheck = mysqli_query($conn, "SELECT COUNT(*) as count FROM `order` WHERE CustomerID = " . $userData['CustomerID']);
                    $orderData = mysqli_fetch_assoc($orderCheck);
                    if ($orderData['count'] == 0) {
                        mysqli_query($conn, "DELETE FROM customer WHERE CustomerID = " . $userData['CustomerID']);
                    }
                }
                
                mysqli_commit($conn);
                $success = 'Account rejected and removed successfully!';
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = 'Error rejecting account: ' . $e->getMessage();
            }
        }
    }
}

// Get pending accounts
$pendingAccounts = [];
$result = mysqli_query($conn, "
    SELECT u.*, 
           c.Name as CustomerName, c.Email as CustomerEmail, c.Phone as CustomerPhone, c.Address as CustomerAddress,
           e.Name as EmployeeName, e.Email as EmployeeEmail, e.Phone as EmployeePhone, e.Role as EmployeeRole,
           s.Name as SupplierName, s.Email as SupplierEmail, s.Contact as SupplierPhone, s.Address as SupplierAddress
    FROM user u
    LEFT JOIN customer c ON u.CustomerID = c.CustomerID
    LEFT JOIN employee e ON u.EmployeeID = e.EmployeeID
    LEFT JOIN supplier s ON u.SupplierID = s.SupplierID
    WHERE u.IsActive = 0
    ORDER BY u.CreatedAt DESC
");
if ($result) {
    $pendingAccounts = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

$pageTitle = 'Approve Accounts';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Approve Accounts</h2>
    <p style="color: #666; margin-top: 0.5rem;">Review and approve pending account registrations</p>
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

<div class="card">
    <div class="card-body">
        <h3 class="card-title mb-4" style="color: var(--primary-color);">Pending Account Approvals</h3>
        <?php if (count($pendingAccounts) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Email Verified</th>
                            <th>Username</th>
                            <th>Registered Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($pendingAccounts as $account): 
                            $displayName = $account['EmployeeName'] ?? $account['SupplierName'] ?? $account['CustomerName'] ?? 'N/A';
                            $displayEmail = $account['EmployeeEmail'] ?? $account['SupplierEmail'] ?? $account['CustomerEmail'] ?? $account['Email'];
                            $displayPhone = $account['EmployeePhone'] ?? $account['SupplierPhone'] ?? $account['CustomerPhone'] ?? 'N/A';
                        ?>
                            <tr>
                                <td><?php echo $counter; ?></td>
                                <td><strong><?php echo htmlspecialchars($displayName); ?></strong></td>
                                <td><?php echo htmlspecialchars($displayEmail); ?></td>
                                <td><?php echo htmlspecialchars($displayPhone); ?></td>
                                <td>
                                    <span class="badge" style="padding: 0.25rem 0.75rem; border-radius: 20px; background-color: 
                                        <?php 
                                        echo $account['Role'] === 'admin' ? '#dc3545' : 
                                            ($account['Role'] === 'seller' ? '#ffc107' : 
                                            ($account['Role'] === 'staff' ? '#17a2b8' : 
                                            ($account['Role'] === 'supplier' ? '#9b59b6' : '#28a745'))); 
                                        ?>; color: white;">
                                        <?php echo strtoupper(htmlspecialchars($account['Role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($account['EmailVerified'] == 1): ?>
                                        <span class="badge bg-success">Verified</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($account['Username']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($account['CreatedAt'])); ?></td>
                                <td class="text-end">
                                    <form method="POST" action="" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to approve this account?');">
                                        <input type="hidden" name="user_id" value="<?php echo $account['UserID']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success btn-sm" <?php echo $account['EmailVerified'] == 0 ? 'disabled' : ''; ?>><?php echo $account['EmailVerified'] == 0 ? 'Awaiting Verification' : 'Approve'; ?></button>
                                        <?php if ($account['EmailVerified'] == 0): ?>
                                            <br><small class="text-muted">User has not verified email</small>
                                        <?php endif; ?>
                                    </form>
                                    <form method="POST" action="" style="display: inline-block; margin-left: 0.5rem;" onsubmit="return confirm('Are you sure you want to reject and delete this account? This action cannot be undone.');">
                                        <input type="hidden" name="user_id" value="<?php echo $account['UserID']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                    </form>
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
                <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 1rem;"></i>
                <p class="text-muted mb-0">No pending account approvals at this time.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

