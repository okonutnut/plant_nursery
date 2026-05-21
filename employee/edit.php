<?php
require_once '../config/database.php';

$error = '';

if (!isset($_GET['id'])) {
    header("Location: employeepage.php");
    exit;
}

$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['Name']);
    $role = mysqli_real_escape_string($conn, $_POST['Role']);
    $email = mysqli_real_escape_string($conn, $_POST['Email']);
    $phone = mysqli_real_escape_string($conn, $_POST['Phone']);
    
    $sql = "UPDATE employee SET Name = '$name', Role = '$role', Email = '$email', Phone = '$phone' WHERE EmployeeID = $id";
    mysqli_query($conn, $sql);
    
    header("Location: employeepage.php?success=1");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM employee WHERE EmployeeID = $id");
$employee = mysqli_fetch_assoc($result);

if (!$employee) {
    header("Location: employeepage.php");
    exit;
}

$pageTitle = 'Edit Employee';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Edit Employee</h2>
    <a href="employeepage.php" class="btn btn-secondary">Back to List</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="Name" class="form-label">Name *</label>
                <input type="text" class="form-control" id="Name" name="Name" required value="<?php echo htmlspecialchars($employee['Name']); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Role" class="form-label">Role</label>
                <input type="text" class="form-control" id="Role" name="Role" value="<?php echo htmlspecialchars($employee['Role'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Email" class="form-label">Email</label>
                <input type="email" class="form-control" id="Email" name="Email" value="<?php echo htmlspecialchars($employee['Email'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Phone" class="form-label">Phone</label>
                <input type="text" class="form-control" id="Phone" name="Phone" value="<?php echo htmlspecialchars($employee['Phone'] ?? ''); ?>">
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Employee</button>
                <a href="employeepage.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

