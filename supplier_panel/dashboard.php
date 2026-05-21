<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header("Location: ../login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$userResult = mysqli_query($conn, "SELECT SupplierID, Username FROM user WHERE UserID = $userID");
$user = mysqli_fetch_assoc($userResult);
$supplierID = $user['SupplierID'] ?? null;

// Get supplier info
$supplierName = 'Supplier';
if ($supplierID) {
    $supResult = mysqli_query($conn, "SELECT * FROM supplier WHERE SupplierID = $supplierID");
    $supplier = mysqli_fetch_assoc($supResult);
    $supplierName = $supplier['Name'] ?? 'Supplier';
}

// Get total products count
$totalProducts = 0;
if ($supplierID) {
    $totalResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM plant WHERE SupplierID = $supplierID");
    $totalProducts = mysqli_fetch_assoc($totalResult)['count'];
}

// Get low stock products (less than 20)
$lowStockCount = 0;
if ($supplierID) {
    $lowResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM plant WHERE SupplierID = $supplierID AND QuantityAvailable < 20");
    $lowStockCount = mysqli_fetch_assoc($lowResult)['count'];
}

// Get total stock value
$totalValue = 0;
if ($supplierID) {
    $valueResult = mysqli_query($conn, "SELECT COALESCE(SUM(QuantityAvailable * Price), 0) as value FROM plant WHERE SupplierID = $supplierID");
    $totalValue = mysqli_fetch_assoc($valueResult)['value'];
}

// Get total quantity across all products
$totalQuantity = 0;
if ($supplierID) {
    $qtyResult = mysqli_query($conn, "SELECT COALESCE(SUM(QuantityAvailable), 0) as qty FROM plant WHERE SupplierID = $supplierID");
    $totalQuantity = mysqli_fetch_assoc($qtyResult)['qty'];
}

// Get recent products
$recentProducts = [];
if ($supplierID) {
    $plantsResult = mysqli_query($conn, "
        SELECT p.*, pc.Name as CategoryName, pt.Name as PlantTypeName
        FROM plant p
        LEFT JOIN plantcategory pc ON p.PlantCategoryID = pc.PlantCategoryID
        LEFT JOIN planttype pt ON p.PlantTypeID = pt.PlantTypeID
        WHERE p.SupplierID = $supplierID
        ORDER BY p.PlantID DESC
        LIMIT 5
    ");
    if ($plantsResult) {
        $recentProducts = mysqli_fetch_all($plantsResult, MYSQLI_ASSOC);
    }
}

$pageTitle = 'Supplier Dashboard';
include 'includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Supplier Dashboard</h2>
    <p style="color: #666; margin-top: 0.5rem;">Welcome, <?php echo htmlspecialchars($supplierName); ?></p>
</div>

<?php if (!$supplierID): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> Your account is not yet linked to a supplier company. Please contact the administrator.
    </div>
<?php else: ?>
    <div class="dashboard-grid mb-4">
        <div class="dashboard-card" style="background: linear-gradient(135deg, #cce5ff, #2196F3); border-top-color: #2196F3;">
            <h3><i class="fas fa-box"></i> Total Products</h3>
            <div class="count" style="color: #004085;"><?php echo $totalProducts; ?></div>
            <a href="products.php"><i class="fas fa-arrow-right"></i> View All</a>
        </div>

        <div class="dashboard-card" style="background: linear-gradient(135deg, #fff3cd, #ffc107); border-top-color: #ffc107;">
            <h3><i class="fas fa-exclamation-circle"></i> Low Stock</h3>
            <div class="count" style="color: #856404;"><?php echo $lowStockCount; ?></div>
            <a href="products.php?stock=low"><i class="fas fa-arrow-right"></i> View</a>
        </div>

        <div class="dashboard-card" style="background: linear-gradient(135deg, #d4edda, #28a745); border-top-color: #28a745;">
            <h3><i class="fas fa-cubes"></i> Total Stock</h3>
            <div class="count" style="color: #155724;"><?php echo number_format($totalQuantity); ?></div>
            <a href="products.php"><i class="fas fa-arrow-right"></i> View</a>
        </div>

        <div class="dashboard-card" style="background: linear-gradient(135deg, #e8d5f5, #9b59b6); border-top-color: #9b59b6;">
            <h3><i class="fas fa-money-bill-wave"></i> Stock Value</h3>
            <div class="count" style="color: #6c3483;">₱<?php echo number_format($totalValue, 2); ?></div>
            <a href="products.php"><i class="fas fa-arrow-right"></i> Manage</a>
        </div>
    </div>

    <div class="card mb-4">
        <h3>Recent Products</h3>
        <?php if (count($recentProducts) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Plant ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentProducts as $plant): ?>
                            <tr>
                                <td>#<?php echo $plant['PlantID']; ?></td>
                                <td><?php echo htmlspecialchars($plant['Name']); ?></td>
                                <td><?php echo htmlspecialchars($plant['CategoryName'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($plant['PlantTypeName'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($plant['QuantityAvailable'] < 20): ?>
                                        <span style="color: #dc3545; font-weight: 500;"><?php echo $plant['QuantityAvailable']; ?></span>
                                    <?php else: ?>
                                        <?php echo $plant['QuantityAvailable']; ?>
                                    <?php endif; ?>
                                </td>
                                <td><strong>₱<?php echo number_format($plant['Price'], 2); ?></strong></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit.php?id=<?php echo $plant['PlantID']; ?>" class="btn btn-warning">Edit</a>
                                        <a href="restock.php?id=<?php echo $plant['PlantID']; ?>" class="btn btn-primary">Restock</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No products yet. <a href="create.php">Add your first product</a></p>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
