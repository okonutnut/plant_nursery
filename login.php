<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';
$isRegister = isset($_GET['register']) || (isset($_POST['action']) && $_POST['action'] === 'register');

// Check if account was deactivated
if (isset($_GET['deactivated'])) {
    $success = 'Your account has been deactivated. Please contact an administrator to reactivate it.';
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        // Handle registration
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $role = mysqli_real_escape_string($conn, $_POST['role'] ?? 'customer');
        
        if (empty($name)) {
            $error = 'Name is required';
        } elseif (empty($username)) {
            $error = 'Username is required';
        } elseif (empty($password)) {
            $error = 'Password is required';
        } elseif (empty($email)) {
            $error = 'Email is required';
        } elseif (!in_array($role, ['customer', 'admin', 'seller', 'supplier'])) {
            $error = 'Invalid role selected';
        } else {
            // Check if username already exists
            $checkUser = mysqli_query($conn, "SELECT * FROM user WHERE Username = '$username'");
            if (mysqli_num_rows($checkUser) > 0) {
                $error = 'Username already exists. Please choose a different username.';
            } else {
                // Check if email already exists
                $checkEmail = mysqli_query($conn, "SELECT * FROM user WHERE Email = '$email'");
                if (mysqli_num_rows($checkEmail) > 0) {
                    $error = 'Email already registered. Please use a different email or login.';
                } else {
                    // Start transaction
                    mysqli_begin_transaction($conn);
                    
                    try {
                        $customerID = null;
                        $employeeID = null;
                        $supplierID = null;
                        $isActive = 1; // Default to active
                        
                        if ($role === 'customer') {
                            // Insert customer
                            $sql = "INSERT INTO customer (Name, Email, Phone, Address) VALUES ('$name', '$email', '$phone', '$address')";
                            if (!mysqli_query($conn, $sql)) {
                                throw new Exception('Error creating account: ' . mysqli_error($conn));
                            }
                            $customerID = mysqli_insert_id($conn);
                        } elseif ($role === 'supplier') {
                            // For supplier, create supplier record
                            // Set IsActive to 0 (pending approval)
                            $isActive = 0;
                            
                            $supSql = "INSERT INTO supplier (Name, Contact, Email, Address) VALUES ('$name', '$phone', '$email', '$address')";
                            if (!mysqli_query($conn, $supSql)) {
                                throw new Exception('Error creating supplier record: ' . mysqli_error($conn));
                            }
                            $supplierID = mysqli_insert_id($conn);
                        } else {
                            // For admin, staff, and seller, create employee record
                            // Set IsActive to 0 (pending approval)
                            $isActive = 0;
                            
                            // Map role to employee role field
                            // Seller and staff are the same, both use 'seller' role
                            $employeeRole = ($role === 'admin') ? 'admin' : 'seller';
                            
                            $empSql = "INSERT INTO employee (Name, Role, Email, Phone) VALUES ('$name', '$employeeRole', '$email', '$phone')";
                            if (!mysqli_query($conn, $empSql)) {
                                throw new Exception('Error creating employee record: ' . mysqli_error($conn));
                            }
                            $employeeID = mysqli_insert_id($conn);
                        }
                        
                        // Create user account in user table
                        $userSql = "INSERT INTO user (Username, Password, Email, Role, CustomerID, EmployeeID, SupplierID, IsActive) VALUES ('$username', '$password', '$email', '$role', " . 
                                   ($customerID ? $customerID : 'NULL') . ", " . ($employeeID ? $employeeID : 'NULL') . ", " . ($supplierID ? $supplierID : 'NULL') . ", $isActive)";
                        if (!mysqli_query($conn, $userSql)) {
                            throw new Exception('Error creating user account: ' . mysqli_error($conn));
                        }
                        
                        $userID = mysqli_insert_id($conn);
                        
                        // Commit transaction
                        mysqli_commit($conn);
                        
                        if ($role === 'customer' && $isActive == 1) {
                            // Automatically log in the newly registered customer
                            $_SESSION['user_id'] = $userID;
                            $_SESSION['username'] = $username;
                            $_SESSION['role'] = 'customer';
                            $_SESSION['email'] = $email;
                            
                            // Update last login
                            mysqli_query($conn, "UPDATE user SET LastLogin = NOW() WHERE UserID = $userID");
                            
                            // Redirect to shop page for customers
                            header("Location: shop/shop.php");
                            exit;
                        } else {
                            // For admin/staff/seller/supplier, show success message (pending approval)
                            $success = 'Registration successful! Your account is pending approval by an administrator. You will be able to login once your account is approved.';
                            $isRegister = true; // Keep registration form visible
                        }
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        mysqli_rollback($conn);
                        $error = $e->getMessage();
                    }
                }
            }
        }
    } else {
        // Handle login
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password';
        } else {
            $result = mysqli_query($conn, "SELECT * FROM user WHERE Username = '$username'");
            $user = mysqli_fetch_assoc($result);
            
            if ($user && $user['Password'] === $password) {
                // Check if account is active
                if ($user['IsActive'] == 0) {
                    $error = 'Your account is pending approval. Please wait for an administrator to approve your account before logging in.';
                } else {
                    // Login successful
                    $_SESSION['user_id'] = $user['UserID'];
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['role'] = $user['Role'];
                    $_SESSION['email'] = $user['Email'];
                    
                    // Update last login
                    mysqli_query($conn, "UPDATE user SET LastLogin = NOW() WHERE UserID = " . $user['UserID']);
                    
                    // Redirect based on user role
                    if ($user['Role'] === 'customer') {
                        header("Location: shop/shop.php");
                    }
                    else if ($user['Role'] === 'admin') {
                        header("Location: index.php");
                    } else if ($user['Role'] === 'seller' || $user['Role'] === 'employee' || $user['Role'] === 'staff') {
                        header("Location: seller/sellerpage.php");
                    } else if ($user['Role'] === 'supplier') {
                        header("Location: supplier_panel/dashboard.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit;
                }
            } else {
                $error = 'Invalid username or password';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plant Nursery - Buy One, Sell One</title>
    <link href="assets/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
            background: url('picture/login-bg.jpg') no-repeat center center fixed, linear-gradient(135deg, #667eea 0%, #8d7c9dff 100%);
            background-size: cover;
        }
        .login-container {
            /* From https://css.glass */
background: rgba(255, 255, 255, 0.5);
border-radius: 16px;
box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
backdrop-filter: blur(5px);
-webkit-backdrop-filter: blur(5px);
border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            max-height: 100vh;
            overflow-y: auto;
            margin: 1rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #666;
        }
        .form-tabs {
            display: flex;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e0e0e0;
        }
        .form-tab {
            flex: 1;
            padding: 0.75rem;
            text-align: center;
            cursor: pointer;
            border: none;
            background: none;
            color: #666;
            font-weight: 500;
            transition: all 0.3s;
        }
        .form-tab.active {
            color: var(--primary-color, #667eea);
            border-bottom: 2px solid var(--primary-color, #667eea);
            margin-bottom: -2px;
        }
        .form-tab:hover {
            color: var(--primary-color, #667eea);
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-seedling"></i> Plant Nursery</h1>
            <p>Buy One, Sell One</p>
        </div>
        
        <div class="form-tabs">
            <button type="button" class="form-tab <?php echo !$isRegister ? 'active' : ''; ?>" onclick="switchForm('login', this)">Login</button>
            <button type="button" class="form-tab <?php echo $isRegister ? 'active' : ''; ?>" onclick="switchForm('register', this)">Register</button>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <div class="form-section <?php echo !$isRegister ? 'active' : ''; ?>" id="loginForm">
            <form method="POST" action="">
                <input type="hidden" name="action" value="login">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
        
        <!-- Register Form -->
        <div class="form-section <?php echo $isRegister ? 'active' : ''; ?>" id="registerForm">
            <form method="POST" action="">
                <input type="hidden" name="action" value="register">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">Account Type *</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="customer" <?php echo (isset($_POST['role']) && $_POST['role'] === 'customer') ? 'selected' : ''; ?>>Customer</option>
                        <option value="seller" <?php echo (isset($_POST['role']) && $_POST['role'] === 'seller') ? 'selected' : ''; ?>>Seller/Staff</option>
                        <option value="supplier" <?php echo (isset($_POST['role']) && $_POST['role'] === 'supplier') ? 'selected' : ''; ?>>Supplier</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <small class="form-text text-muted">Seller/Staff, Supplier, and Admin accounts require approval before login</small>
                </div>
                
                <hr class="my-3">
                <h6 class="mb-3">Login Credentials</h6>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="reg_username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="reg_username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        <small class="form-text text-muted">Choose a unique username for login</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="reg_password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="reg_password" name="password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success w-100">Create Account</button>
            </form>
        </div>
    </div>
    
    <script>
        function switchForm(formType, element) {
            // Update tabs
            document.querySelectorAll('.form-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            element.classList.add('active');
            
            // Update forms
            document.getElementById('loginForm').classList.toggle('active', formType === 'login');
            document.getElementById('registerForm').classList.toggle('active', formType === 'register');
            
            // Update URL without reload
            const url = new URL(window.location);
            if (formType === 'register') {
                url.searchParams.set('register', '1');
            } else {
                url.searchParams.delete('register');
            }
            window.history.pushState({}, '', url);
        }
    </script>
    <script src="assets/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

