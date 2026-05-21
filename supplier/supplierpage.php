<?php
require_once '../config/database.php';

$result = mysqli_query($conn, "SELECT * FROM supplier ORDER BY Name");
$suppliers = mysqli_fetch_all($result, MYSQLI_ASSOC);

$countResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM supplier");
$totalCount = mysqli_fetch_assoc($countResult)['total'];

$pageTitle = 'Suppliers';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Suppliers</h2>
    <a href="create.php" class="btn btn-primary">Add New Supplier</a>
</div>

<div class="card">
    <div class="card-body">
        <h3 class="card-title mb-4" style="color: var(--primary-color);">All Suppliers</h3>
        <?php if (count($suppliers) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td>#<?php echo $counter; ?></td>
                                <td><?php echo htmlspecialchars($supplier['Name']); ?></td>
                                <td><?php echo htmlspecialchars($supplier['Contact'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($supplier['Email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($supplier['Address'] ?? 'N/A'); ?></td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="edit.php?id=<?php echo $supplier['SupplierID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="delete.php?id=<?php echo $supplier['SupplierID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
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
                <p class="text-muted mb-0">No suppliers found. <a href="create.php">Create one now</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

