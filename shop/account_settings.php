<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle account deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deactivate') {
    $confirmPassword = mysqli_real_escape_string($conn, $_POST['confirm_password'] ?? '');
    
    // Verify password
    $userResult = mysqli_query($conn, "SELECT Password FROM user WHERE UserID = $userID");
    $user = mysqli_fetch_assoc($userResult);
    
    if ($user && $user['Password'] === $confirmPassword) {
        $updateSql = "UPDATE user SET IsActive = 0 WHERE UserID = $userID";
        if (mysqli_query($conn, $updateSql)) {
            session_destroy();
            header("Location: ../login.php?deactivated=1");
            exit;
        } else {
            $error = 'Error deactivating account: ' . mysqli_error($conn);
        }
    } else {
        $error = 'Incorrect password. Please try again.';
    }
}

// Get user info
$userResult = mysqli_query($conn, "
    SELECT u.*, c.Name as CustomerName, c.Email as CustomerEmail, c.Phone as CustomerPhone, c.Address as CustomerAddress
    FROM user u
    LEFT JOIN customer c ON u.CustomerID = c.CustomerID
    WHERE u.UserID = $userID
");
$userInfo = mysqli_fetch_assoc($userResult);

$pageTitle = 'Account Settings';
include 'includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Account Settings</h2>
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

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title mb-4" style="color: var(--primary-color);">Account Information</h3>
                <table class="table">
                    <tr>
                        <th width="30%">Name:</th>
                        <td><?php echo htmlspecialchars($userInfo['CustomerName'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?php echo htmlspecialchars($userInfo['CustomerEmail'] ?? $userInfo['Email'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td><?php echo htmlspecialchars($userInfo['CustomerPhone'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Address:</th>
                        <td><?php echo htmlspecialchars($userInfo['CustomerAddress'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Username:</th>
                        <td><?php echo htmlspecialchars($userInfo['Username']); ?></td>
                    </tr>
                    <tr>
                        <th>Account Status:</th>
                        <td>
                            <?php if ($userInfo['IsActive'] == 1): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Member Since:</th>
                        <td><?php echo date('F d, Y', strtotime($userInfo['CreatedAt'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card" style="border-color: #dc3545;">
            <div class="card-body">
                <h5 class="card-title text-danger">Danger Zone</h5>
                <p class="text-muted small">Deactivate your account. You can request reactivation from an administrator.</p>
                
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deactivateModal">
                    Deactivate Account
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Deactivate Account Modal -->
<div class="modal fade" id="deactivateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Deactivate Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Warning!</strong> This will deactivate your account. You will not be able to login until an administrator reactivates your account.
                    </div>
                    <p>To confirm, please enter your password:</p>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <input type="hidden" name="action" value="deactivate">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure you want to deactivate your account?');">Deactivate Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

