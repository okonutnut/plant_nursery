<?php
require_once '../config/database.php';

$error = '';

if (!isset($_GET['id'])) {
    header("Location: costumerpage.php");
    exit;
}

$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['Name']);
    $email = mysqli_real_escape_string($conn, $_POST['Email']);
    $phone = mysqli_real_escape_string($conn, $_POST['Phone']);
    $address = mysqli_real_escape_string($conn, $_POST['Address']);
    
    $sql = "UPDATE customer SET Name = '$name', Email = '$email', Phone = '$phone', Address = '$address' WHERE CustomerID = $id";
    mysqli_query($conn, $sql);
    
    header("Location: costumerpage.php?success=1");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM customer WHERE CustomerID = $id");
$customer = mysqli_fetch_assoc($result);

if (!$customer) {
    header("Location: costumerpage.php");
    exit;
}

$pageTitle = 'Edit Customer';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Edit Customer</h2>
    <a href="costumerpage.php" class="btn btn-secondary">Back to List</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="Name" class="form-label">Name *</label>
                <input type="text" class="form-control" id="Name" name="Name" required value="<?php echo htmlspecialchars($customer['Name']); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Email" class="form-label">Email</label>
                <input type="email" class="form-control" id="Email" name="Email" value="<?php echo htmlspecialchars($customer['Email'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Phone" class="form-label">Phone</label>
                <input type="text" class="form-control" id="Phone" name="Phone" value="<?php echo htmlspecialchars($customer['Phone'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Address" class="form-label">Address</label>
                <textarea class="form-control" id="Address" name="Address" rows="3"><?php echo htmlspecialchars($customer['Address'] ?? ''); ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Customer</button>
                <a href="costumerpage.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

