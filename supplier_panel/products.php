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
$userResult = mysqli_query($conn, "SELECT SupplierID FROM user WHERE UserID = $userID");
$user = mysqli_fetch_assoc($userResult);
$supplierID = $user['SupplierID'] ?? null;

if (!$supplierID) {
    header("Location: dashboard.php?error=no_supplier");
    exit;
}

// Handle delete action
if (isset($_GET['delete'])) {
    $plantID = (int)$_GET['delete'];
    // Only allow deleting own products
    $check = mysqli_query($conn, "SELECT PlantID FROM plant WHERE PlantID = $plantID AND SupplierID = $supplierID");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM plant WHERE PlantID = $plantID");
        $success = 'Product deleted successfully!';
    }
}

// Get filters
$stockFilter = isset($_GET['stock']) ? $_GET['stock'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$whereExtra = '';
if ($stockFilter === 'low') {
    $whereExtra = "AND p.QuantityAvailable < 20";
}

$searchExtra = '';
if ($search !== '') {
    $searchExtra = "AND (p.Name LIKE '%$search%' OR p.ScientificName LIKE '%$search%')";
}

$result = mysqli_query($conn, "
    SELECT p.*, pc.Name as CategoryName, pt.Name as PlantTypeName
    FROM plant p
    LEFT JOIN plantcategory pc ON p.PlantCategoryID = pc.PlantCategoryID
    LEFT JOIN planttype pt ON p.PlantTypeID = pt.PlantTypeID
    WHERE p.SupplierID = $supplierID $whereExtra $searchExtra
    ORDER BY p.Name
");
$plants = mysqli_fetch_all($result, MYSQLI_ASSOC);

$pageTitle = 'My Products';
include 'includes/header.php';
?>

<div class="page-header mb-4">
    <h2>My Products</h2>
    <a href="create.php" class="btn btn-primary">Add New Product</a>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <h3 style="margin: 0; color: var(--primary-color); font-size: 1.5rem; font-weight: 600;">
            <i class="fas fa-box"></i> All Products
        </h3>
        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <form method="GET" action="" style="display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                <div style="position: relative;">
                    <input type="text" name="search" id="searchInput" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 0.5rem 2rem 0.5rem 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.9rem; width: 220px;">
                    <?php if ($search !== ''): ?>
                        <a href="products.php?stock=<?php echo $stockFilter; ?>" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); color: #999; text-decoration: none; font-size: 1.1rem;">&times;</a>
                    <?php endif; ?>
                </div>
                <button type="submit" style="padding: 0.5rem 1rem; background: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer;"><i class="fas fa-search"></i></button>
                <input type="hidden" name="stock" value="<?php echo $stockFilter; ?>">
            </form>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label for="stockFilter" style="font-weight: 500; color: var(--text-color);">
                    <i class="fas fa-filter"></i> Filter:
                </label>
                <select id="stockFilter" onchange="filterByStock()" style="min-width: 150px;">
                    <option value="all" <?php echo $stockFilter === 'all' ? 'selected' : ''; ?>>All Products</option>
                    <option value="low" <?php echo $stockFilter === 'low' ? 'selected' : ''; ?>>Low Stock (&lt; 20)</option>
                </select>
            </div>
        </div>
    </div>

    <?php if (count($plants) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Scientific Name</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plants as $plant): ?>
                        <tr>
                            <td>#<?php echo $plant['PlantID']; ?></td>
                            <td><strong><?php echo htmlspecialchars($plant['Name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($plant['ScientificName'] ?? 'N/A'); ?></td>
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
                                    <a href="edit.php?id=<?php echo $plant['PlantID']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="restock.php?id=<?php echo $plant['PlantID']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Restock</a>
                                    <a href="products.php?delete=<?php echo $plant['PlantID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product permanently?')"><i class="fas fa-trash"></i> Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>No products found. <a href="create.php">Add your first product</a></p>
        </div>
    <?php endif; ?>
</div>

<script>
function filterByStock() {
    const stock = document.getElementById('stockFilter').value;
    const search = document.getElementById('searchInput').value;
    const params = new URLSearchParams();
    if (stock !== 'all') params.set('stock', stock);
    if (search) params.set('search', search);
    window.location.href = 'products.php?' + params.toString();
}
</script>

<?php include 'includes/footer.php'; ?>
