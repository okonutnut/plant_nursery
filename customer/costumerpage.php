<?php
require_once '../config/database.php';

// Only show customers who have transactions (orders)
$result = mysqli_query($conn, "
    SELECT DISTINCT c.*, 
           COUNT(o.OrderID) as OrderCount,
           COALESCE(SUM(o.TotalAmount), 0) as TotalSpent
    FROM customer c
    INNER JOIN `order` o ON c.CustomerID = o.CustomerID
    GROUP BY c.CustomerID
    ORDER BY c.Name
");
$customers = mysqli_fetch_all($result, MYSQLI_ASSOC);

$countResult = mysqli_query($conn, "
    SELECT COUNT(DISTINCT CustomerID) as total 
    FROM `order` 
    WHERE CustomerID IS NOT NULL
");
$totalCount = mysqli_fetch_assoc($countResult)['total'];

$pageTitle = 'Customers';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Customers</h2>
    <a href="create.php" class="btn btn-primary">Add New Customer</a>
</div>

<div class="card">
    <div class="card-body">
        <h3 class="card-title mb-4" style="color: var(--primary-color);">Customers with Transactions</h3>
        <p class="text-muted mb-3">This page shows only customers who have made orders. To view all registered users, go to <a href="../user/userspage.php">Users</a>.</p>
        <?php if (count($customers) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($customers as $customer): ?>
                            <tr>
                                <td>#<?php echo $counter; ?></td>
                                <td><?php echo htmlspecialchars($customer['Name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['Email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($customer['Phone'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($customer['Address'] ?? 'N/A'); ?></td>
                                <td><span class="badge bg-primary"><?php echo $customer['OrderCount']; ?></span></td>
                                <td><strong>₱<?php echo number_format($customer['TotalSpent'], 2); ?></strong></td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="edit.php?id=<?php echo $customer['CustomerID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="delete.php?id=<?php echo $customer['CustomerID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
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
                <p class="text-muted mb-0">No customers found. <a href="create.php">Create one now</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

