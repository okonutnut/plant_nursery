<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    header("Location: cart.php");
    exit;
}

$error = '';
$success = '';

// Get current user info
$userID = $_SESSION['user_id'];
$userEmail = $_SESSION['email'];
$userResult = mysqli_query($conn, "SELECT CustomerID, Username, Email FROM user WHERE UserID = $userID");
$user = mysqli_fetch_assoc($userResult);
$userCustomerID = $user['CustomerID'] ?? null;

// Get customer record - prefer the one linked to user, otherwise get by email
$customer = null;
$customerID = null;

if ($userCustomerID) {
    // User already has a customer record linked
    $customerResult = mysqli_query($conn, "SELECT * FROM customer WHERE CustomerID = $userCustomerID LIMIT 1");
    $customer = mysqli_fetch_assoc($customerResult);
    $customerID = $userCustomerID;
} else {
    // Check if customer exists by email
    $customerResult = mysqli_query($conn, "SELECT * FROM customer WHERE Email = '$userEmail' LIMIT 1");
    $customer = mysqli_fetch_assoc($customerResult);
    
    if ($customer) {
        $customerID = $customer['CustomerID'];
        // Link the existing customer to the user
        mysqli_query($conn, "UPDATE user SET CustomerID = $customerID WHERE UserID = $userID");
    } else {
        // Customer doesn't exist - will be created when form is submitted or use user info
        $customerID = null;
    }
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get shipping information from form
    $shippingName = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $shippingEmail = mysqli_real_escape_string($conn, $_POST['email'] ?? $userEmail);
    $shippingPhone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $shippingAddress = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    
    // Validate required fields
    if (empty($shippingName) || empty($shippingEmail)) {
        $error = 'Name and Email are required for shipping information.';
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Update or create customer record
            if ($customerID) {
                // Update existing customer with shipping information
                $updateSql = "UPDATE customer SET Name = '$shippingName', Email = '$shippingEmail', Phone = '$shippingPhone', Address = '$shippingAddress' WHERE CustomerID = $customerID";
                if (!mysqli_query($conn, $updateSql)) {
                    throw new Exception('Error updating customer: ' . mysqli_error($conn));
                }
            } else {
                // Create new customer record with shipping information
                $insertSql = "INSERT INTO customer (Name, Email, Phone, Address) VALUES ('$shippingName', '$shippingEmail', '$shippingPhone', '$shippingAddress')";
                if (!mysqli_query($conn, $insertSql)) {
                    throw new Exception('Error creating customer: ' . mysqli_error($conn));
                }
                $customerID = mysqli_insert_id($conn);
                
                // Link customer to user
                $linkSql = "UPDATE user SET CustomerID = $customerID WHERE UserID = $userID";
                if (!mysqli_query($conn, $linkSql)) {
                    throw new Exception('Error linking customer to user: ' . mysqli_error($conn));
                }
            }
            
            // Validate quantities
            foreach ($_SESSION['cart'] as $item) {
                $plantID = $item['PlantID'];
                $quantity = $item['Quantity'];
                
                $plantResult = mysqli_query($conn, "SELECT Name, QuantityAvailable FROM plant WHERE PlantID = $plantID");
                $plant = mysqli_fetch_assoc($plantResult);
                
                if (!$plant || $plant['QuantityAvailable'] < $quantity) {
                    throw new Exception("Insufficient quantity for {$item['Name']}. Available: " . ($plant['QuantityAvailable'] ?? 0));
                }
            }
            
            // Calculate total
            $totalAmount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $totalAmount += $item['Price'] * $item['Quantity'];
            }
            
            // Create order with status 'Pending'
            $orderDate = date('Y-m-d');
            $status = 'Pending';
            $orderSql = "INSERT INTO `order` (CustomerID, EmployeeID, OrderDate, TotalAmount, Status) VALUES ($customerID, NULL, '$orderDate', $totalAmount, '$status')";
            
            if (!mysqli_query($conn, $orderSql)) {
                throw new Exception('Error creating order: ' . mysqli_error($conn));
            }
            
            $orderID = mysqli_insert_id($conn);
            
            // Insert order items
            foreach ($_SESSION['cart'] as $item) {
                $plantID = $item['PlantID'];
                $quantity = $item['Quantity'];
                $price = $item['Price'];
                
                $itemSql = "INSERT INTO orderitem (OrderID, PlantID, Quantity, Price) VALUES ($orderID, $plantID, $quantity, $price)";
                if (!mysqli_query($conn, $itemSql)) {
                    throw new Exception('Error creating order item: ' . mysqli_error($conn));
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Clear cart
            $_SESSION['cart'] = [];
            
            header("Location: order_success.php?order_id=$orderID");
            exit;
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

// Calculate totals
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['Price'] * $item['Quantity'];
}

$pageTitle = 'Checkout';
include 'includes/header.php';
?>

<style>
    .checkout-content {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }
        .checkout-form {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .order-summary {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .order-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }
        .summary-row.total {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            border-top: 2px solid #eee;
            margin-top: 1rem;
            padding-top: 1rem;
        }
        .btn-place-order {
            width: 100%;
            padding: 1rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
        }
        .btn-place-order:hover {
            background: #218838;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
    </style>

<div class="page-header mb-4">
    <h2><i class="fas fa-check-circle"></i> Checkout</h2>
    <p class="mb-0">Complete your order</p>
</div>
        <?php if ($error): ?>
            <div class="alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="checkout-content">
            <div class="checkout-form">
                <h2 style="margin-top: 0; color: var(--primary-color);">Shipping Information</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($customer['Name'] ?? $_POST['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer['Email'] ?? $_POST['email'] ?? $userEmail); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['Phone'] ?? $_POST['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($customer['Address'] ?? $_POST['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-place-order">Place Order</button>
                </form>
            </div>

            <div class="order-summary">
                <h2 style="margin-top: 0; color: var(--primary-color);">Order Summary</h2>
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <div class="order-item">
                        <div><strong><?php echo htmlspecialchars($item['Name']); ?></strong></div>
                        <div>Qty: <?php echo $item['Quantity']; ?> × $<?php echo number_format($item['Price'], 2); ?></div>
                        <div style="text-align: right; margin-top: 0.5rem;">$<?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
                
                <div class="summary-row total">
                    <span>Total:</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
            </div>
        </div>

<?php include 'includes/footer.php'; ?>

