<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$userID = $_SESSION['user_id'];
$userResult = mysqli_query($conn, "SELECT SupplierID FROM user WHERE UserID = $userID");
$user = mysqli_fetch_assoc($userResult);
$supplierID = $user['SupplierID'] ?? null;

if (!$supplierID) {
    header("Location: dashboard.php?error=no_supplier");
    exit;
}

$id = (int)$_GET['id'];

// Verify the plant belongs to this supplier
$plantStmt = mysqli_prepare($conn, "SELECT PlantID, Name, QuantityAvailable, Price FROM plant WHERE PlantID = ? AND SupplierID = ?");
mysqli_stmt_bind_param($plantStmt, "ii", $id, $supplierID);
mysqli_stmt_execute($plantStmt);
$plantResult = mysqli_stmt_get_result($plantStmt);
$plant = mysqli_fetch_assoc($plantResult);
mysqli_stmt_close($plantStmt);

if (!$plant) {
    header("Location: products.php");
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $additionalQty = (int)($_POST['additional_quantity'] ?? 0);

    if ($additionalQty <= 0) {
        $error = 'Please enter a valid quantity greater than 0.';
    } else {
        $newQty = $plant['QuantityAvailable'] + $additionalQty;
        $updateStmt = mysqli_prepare($conn, "UPDATE plant SET QuantityAvailable = ? WHERE PlantID = ? AND SupplierID = ?");
        mysqli_stmt_bind_param($updateStmt, "iii", $newQty, $id, $supplierID);

        if (mysqli_stmt_execute($updateStmt)) {
            $success = "Successfully added $additionalQty units. New stock: $newQty";
            $plant['QuantityAvailable'] = $newQty;
        } else {
            $error = 'Error updating stock: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($updateStmt);
    }
}

$pageTitle = 'Restock Product';
include 'includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Restock Product</h2>
    <a href="products.php" class="btn btn-secondary">Back to Products</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div style="background: #f8f9fa; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid var(--primary-color);">
            <h4 style="margin: 0 0 0.5rem;"><?php echo htmlspecialchars($plant['Name']); ?></h4>
            <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                <div>
                    <small style="color: #666;">Current Stock</small>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);"><?php echo $plant['QuantityAvailable']; ?></div>
                </div>
                <div>
                    <small style="color: #666;">Price</small>
                    <div style="font-size: 1.5rem; font-weight: 700;">₱<?php echo number_format($plant['Price'], 2); ?></div>
                </div>
            </div>
        </div>

        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="additional_quantity" class="form-label">Additional Quantity to Add *</label>
                    <input type="number" class="form-control" id="additional_quantity" name="additional_quantity" min="1" required placeholder="Enter quantity to add">
                    <small class="form-text text-muted">Enter the number of units to add to current stock.</small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Stock</button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
