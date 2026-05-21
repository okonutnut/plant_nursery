<?php
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['Name']);
    $email = mysqli_real_escape_string($conn, $_POST['Email']);
    $phone = mysqli_real_escape_string($conn, $_POST['Phone']);
    $address = mysqli_real_escape_string($conn, $_POST['Address']);
    $username = mysqli_real_escape_string($conn, $_POST['Username']);
    $password = mysqli_real_escape_string($conn, $_POST['Password']);
    
    if (empty($name)) {
        $error = 'Name is required';
    } elseif (empty($username)) {
        $error = 'Username is required';
    } elseif (empty($password)) {
        $error = 'Password is required';
    } elseif (empty($email)) {
        $error = 'Email is required for login credentials';
    } else {
        // Check if username already exists
        $checkUser = mysqli_query($conn, "SELECT * FROM user WHERE Username = '$username'");
        if (mysqli_num_rows($checkUser) > 0) {
            $error = 'Username already exists. Please choose a different username.';
        } else {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Insert customer
                $sql = "INSERT INTO customer (Name, Email, Phone, Address) VALUES ('$name', '$email', '$phone', '$address')";
                if (!mysqli_query($conn, $sql)) {
                    throw new Exception('Error creating customer: ' . mysqli_error($conn));
                }
                
                $customerID = mysqli_insert_id($conn);
                
                // Create user account in user table with CustomerID link
                // Role is set to 'customer' for all customer accounts
                $userSql = "INSERT INTO user (Username, Password, Email, Role, CustomerID, IsActive) VALUES ('$username', '$password', '$email', 'customer', $customerID, 1)";
                if (!mysqli_query($conn, $userSql)) {
                    throw new Exception('Error creating user account: ' . mysqli_error($conn));
                }
                
                // Commit transaction
                mysqli_commit($conn);
                header("Location: costumerpage.php?success=1");
                exit;
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                $error = $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Create Customer';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Create Customer</h2>
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
                <input type="text" class="form-control" id="Name" name="Name" required value="<?php echo htmlspecialchars($_POST['Name'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="Email" name="Email" required value="<?php echo htmlspecialchars($_POST['Email'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Phone" class="form-label">Phone</label>
                <input type="text" class="form-control" id="Phone" name="Phone" value="<?php echo htmlspecialchars($_POST['Phone'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Address" class="form-label">Address</label>
                <textarea class="form-control" id="Address" name="Address" rows="3"><?php echo htmlspecialchars($_POST['Address'] ?? ''); ?></textarea>
            </div>
            
            <hr class="my-4">
            <h5 class="mb-3">Login Credentials</h5>
            
            <div class="mb-3">
                <label for="Username" class="form-label">Username *</label>
                <input type="text" class="form-control" id="Username" name="Username" required value="<?php echo htmlspecialchars($_POST['Username'] ?? ''); ?>">
                <small class="form-text text-muted">This will be used for login</small>
            </div>
            
            <div class="mb-3">
                <label for="Password" class="form-label">Password *</label>
                <input type="password" class="form-control" id="Password" name="Password" required>
                <small class="form-text text-muted">Password for login access</small>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Customer</button>
                <a href="costumerpage.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

