<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Check if user is logged in and is an employee/admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'employee' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: orderpage.php");
    exit;
}

$orderID = (int)$_GET['id'];

// Get order details
$orderResult = mysqli_query($conn, "
    SELECT o.*, c.Name as CustomerName
    FROM `order` o
    LEFT JOIN customer c ON o.CustomerID = c.CustomerID
    WHERE o.OrderID = $orderID
");
$order = mysqli_fetch_assoc($orderResult);

if (!$order) {
    header("Location: orderpage.php?error=Order not found");
    exit;
}

if ($order['Status'] === 'Completed') {
    header("Location: view.php?id=$orderID&error=Order is already completed");
    exit;
}

if ($order['Status'] === 'Cancelled') {
    header("Location: view.php?id=$orderID&error=Cannot approve a cancelled order");
    exit;
}

// Get employee ID from user
$userID = $_SESSION['user_id'];
$userResult = mysqli_query($conn, "SELECT EmployeeID FROM user WHERE UserID = $userID");
$user = mysqli_fetch_assoc($userResult);
$employeeID = $user['EmployeeID'] ?? null;

// Get order items
$itemsResult = mysqli_query($conn, "
    SELECT oi.*, p.Name as PlantName, p.QuantityAvailable
    FROM orderitem oi
    LEFT JOIN plant p ON oi.PlantID = p.PlantID
    WHERE oi.OrderID = $orderID
");
$items = mysqli_fetch_all($itemsResult, MYSQLI_ASSOC);

// Validate inventory availability
$errors = [];
foreach ($items as $item) {
    if ($item['QuantityAvailable'] < $item['Quantity']) {
        $errors[] = "Insufficient stock for {$item['PlantName']}. Available: {$item['QuantityAvailable']}, Required: {$item['Quantity']}";
    }
}

if (!empty($errors)) {
    $errorMessage = implode('; ', $errors);
    header("Location: view.php?id=$orderID&error=" . urlencode($errorMessage));
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update order status to Completed and assign employee
    $updateOrder = mysqli_query($conn, "
        UPDATE `order` 
        SET Status = 'Completed', 
            EmployeeID = " . ($employeeID ? $employeeID : 'NULL') . "
        WHERE OrderID = $orderID
    ");
    
    if (!$updateOrder) {
        throw new Exception("Failed to update order: " . mysqli_error($conn));
    }
    
    // Decrease plant quantities
    foreach ($items as $item) {
        $updatePlant = mysqli_query($conn, "
            UPDATE plant 
            SET QuantityAvailable = QuantityAvailable - {$item['Quantity']} 
            WHERE PlantID = {$item['PlantID']} AND QuantityAvailable >= {$item['Quantity']}
        ");
        
        if (!$updatePlant || mysqli_affected_rows($conn) == 0) {
            throw new Exception("Failed to update inventory for {$item['PlantName']}");
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    header("Location: view.php?id=$orderID&success=Order approved and completed successfully");
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    header("Location: view.php?id=$orderID&error=" . urlencode($e->getMessage()));
}

exit;

