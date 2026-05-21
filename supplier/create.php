<?php
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['Name']);
    $contact = mysqli_real_escape_string($conn, $_POST['Contact']);
    $email = mysqli_real_escape_string($conn, $_POST['Email']);
    $address = mysqli_real_escape_string($conn, $_POST['Address']);
    
    if (empty($name)) {
        $error = 'Name is required';
    } else {
        $sql = "INSERT INTO supplier (Name, Contact, Email, Address) VALUES ('$name', '$contact', '$email', '$address')";
        if (mysqli_query($conn, $sql)) {
            header("Location: supplierpage.php?success=1");
            exit;
        } else {
            $error = 'Error: ' . mysqli_error($conn);
        }
    }
}

$pageTitle = 'Create Supplier';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Create Supplier</h2>
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
                <input type="text" class="form-control" id="Name" name="Name" required value="<?php echo htmlspecialchars($_POST['Name'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Contact" class="form-label">Contact</label>
                <input type="text" class="form-control" id="Contact" name="Contact" value="<?php echo htmlspecialchars($_POST['Contact'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Email" class="form-label">Email</label>
                <input type="email" class="form-control" id="Email" name="Email" value="<?php echo htmlspecialchars($_POST['Email'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Address" class="form-label">Address</label>
                <textarea class="form-control" id="Address" name="Address" rows="3"><?php echo htmlspecialchars($_POST['Address'] ?? ''); ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Supplier</button>
                <a href="supplierpage.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

