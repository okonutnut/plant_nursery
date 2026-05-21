<?php
require_once '../config/database.php';

$result = mysqli_query($conn, "SELECT * FROM employee WHERE Role != 'admin' ORDER BY Name");
$employees = mysqli_fetch_all($result, MYSQLI_ASSOC);

$countResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM employee");
$totalCount = mysqli_fetch_assoc($countResult)['total'];

$pageTitle = 'Employees';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Employees</h2>
    <a href="create.php" class="btn btn-primary">Add New Employee</a>
</div>

<div class="card">
    <div class="card-body">
        <h3 class="card-title mb-4" style="color: var(--primary-color);">All Employees</h3>
        <?php if (count($employees) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($employees as $employee): ?>
                            <tr>
                                <td>#<?php echo $counter; ?></td>
                                <td><?php echo htmlspecialchars($employee['Name']); ?></td>
                                <td><?php echo htmlspecialchars($employee['Role'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($employee['Email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($employee['Phone'] ?? 'N/A'); ?></td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="edit.php?id=<?php echo $employee['EmployeeID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="delete.php?id=<?php echo $employee['EmployeeID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
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
                <p class="text-muted mb-0">No employees found. <a href="create.php">Create one now</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

