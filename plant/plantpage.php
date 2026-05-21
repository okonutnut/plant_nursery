<?php
require_once '../config/database.php';

$result = mysqli_query($conn, "
    SELECT p.*, pc.Name as CategoryName, s.Name as SupplierName, pt.Name as PlantTypeName
    FROM plant p
    LEFT JOIN plantcategory pc ON p.PlantCategoryID = pc.PlantCategoryID
    LEFT JOIN planttype pt ON p.PlantTypeID = pt.PlantTypeID
    LEFT JOIN supplier s ON p.SupplierID = s.SupplierID
    ORDER BY p.Name
");
$plants = mysqli_fetch_all($result, MYSQLI_ASSOC);

$countResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM plant");
$totalCount = mysqli_fetch_assoc($countResult)['total'];

$totalQuantity = 0;
$totalValue = 0;
foreach ($plants as $plant) {
    $totalQuantity += $plant['QuantityAvailable'];
    $totalValue += $plant['QuantityAvailable'] * $plant['Price'];
}

$pageTitle = 'Plants';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Plants</h2>
    <a href="create.php" class="btn btn-primary">Add New Plant</a>
</div>

<div class="card">
    <div class="card-body">
        <h3 class="card-title mb-4" style="color: var(--primary-color);">All Plants</h3>
        <?php if (count($plants) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Scientific Name</th>
                            <th>Category</th>
                            <th>Plant Type</th>
                            <th>Supplier</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($plants as $plant): ?>
                            <tr>
                                <td>#<?php echo $counter; ?></td>
                                <td><?php echo htmlspecialchars($plant['Name']); ?></td>
                                <td><?php echo htmlspecialchars($plant['ScientificName'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($plant['CategoryName'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($plant['PlantTypeName'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($plant['SupplierName'] ?? 'N/A'); ?></td>
                                <td><?php echo $plant['QuantityAvailable']; ?></td>
                                <td>₱<?php echo number_format($plant['Price'], 2); ?></td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="edit.php?id=<?php echo $plant['PlantID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="delete.php?id=<?php echo $plant['PlantID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                                    </div>
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
                <p class="text-muted mb-0">No plants found. <a href="create.php">Create one now</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>