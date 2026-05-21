<?php
require_once '../config/database.php';

$result = mysqli_query($conn, "SELECT * FROM planttype ORDER BY Name");
$types = mysqli_fetch_all($result, MYSQLI_ASSOC);

$countResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM planttype");
$totalCount = mysqli_fetch_assoc($countResult)['total'];

$pageTitle = 'Plant Types';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Plant Types (Propagation Methods)</h2>
    <a href="create.php" class="btn btn-primary">Add New Type</a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Plant type created/updated successfully!</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <h3 class="card-title mb-4" style="color: var(--primary-color);">All Plant Types</h3>
        <?php if (count($types) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Created At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($types as $type): ?>
                            <tr>
                                <td>#<?php echo $counter; ?></td>
                                <td><strong><?php echo htmlspecialchars($type['Name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($type['Description'] ?? 'N/A'); ?></td>
                                <td><?php echo $type['CreatedAt'] ? date('M d, Y', strtotime($type['CreatedAt'])) : 'N/A'; ?></td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="edit.php?id=<?php echo $type['PlantTypeID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="delete.php?id=<?php echo $type['PlantTypeID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this plant type?')">Delete</a>
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
                <p class="text-muted mb-0">No plant types found. <a href="create.php">Create one now</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

