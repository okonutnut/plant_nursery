<?php
require_once '../config/database.php';

$error = '';

if (!isset($_GET['id'])) {
    header("Location: supplierpage.php");
    exit;
}

$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['Name']);
    $contact = mysqli_real_escape_string($conn, $_POST['Contact']);
    $email = mysqli_real_escape_string($conn, $_POST['Email']);
    $address = mysqli_real_escape_string($conn, $_POST['Address']);
    
    $sql = "UPDATE supplier SET Name = '$name', Contact = '$contact', Email = '$email', Address = '$address' WHERE SupplierID = $id";
    mysqli_query($conn, $sql);
    
    header("Location: supplierpage.php?success=1");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM supplier WHERE SupplierID = $id");
$supplier = mysqli_fetch_assoc($result);

if (!$supplier) {
    header("Location: supplierpage.php");
    exit;
}

$pageTitle = 'Edit Supplier';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Edit Supplier</h2>
    <a href="supplierpage.php" class="btn btn-secondary">Back to List</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="Name" class="form-label">Name *</label>
                <input type="text" class="form-control" id="Name" name="Name" required value="<?php echo htmlspecialchars($supplier['Name']); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Contact" class="form-label">Contact</label>
                <input type="text" class="form-control" id="Contact" name="Contact" value="<?php echo htmlspecialchars($supplier['Contact'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Email" class="form-label">Email</label>
                <input type="email" class="form-control" id="Email" name="Email" value="<?php echo htmlspecialchars($supplier['Email'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Address" class="form-label">Address</label>
                <textarea class="form-control" id="Address" name="Address" rows="3"><?php echo htmlspecialchars($supplier['Address'] ?? ''); ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Supplier</button>
                <a href="supplierpage.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

