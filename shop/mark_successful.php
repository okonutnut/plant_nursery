<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header("Location: my_orders.php?error=Order ID required");
    exit;
}

$orderID = (int)$_GET['id'];

// Get customer ID from user email
$userEmail = $_SESSION['email'];
$customerResult = mysqli_query($conn, "SELECT CustomerID FROM customer WHERE Email = '$userEmail' LIMIT 1");
$customer = mysqli_fetch_assoc($customerResult);

if (!$customer) {
    header("Location: my_orders.php?error=Customer not found");
    exit;
}

$customerID = $customer['CustomerID'];

// Verify order belongs to customer and is completed
$orderResult = mysqli_query($conn, "
    SELECT o.*
    FROM `order` o
    WHERE o.OrderID = $orderID AND o.CustomerID = $customerID
");
$order = mysqli_fetch_assoc($orderResult);

if (!$order) {
    header("Location: my_orders.php?error=Order not found");
    exit;
}

// Check if order is completed
if (strtolower($order['Status']) !== 'completed') {
    header("Location: view_order.php?id=$orderID&error=Only completed orders can be marked as successful");
    exit;
}

// Check if order is already marked as successful
if (isset($order['IsSuccessful']) && $order['IsSuccessful'] == 1) {
    header("Location: view_order.php?id=$orderID&error=Order is already marked as successful");
    exit;
}

// Mark order as successful
$updateResult = mysqli_query($conn, "
    UPDATE `order` 
    SET IsSuccessful = 1 
    WHERE OrderID = $orderID AND CustomerID = $customerID AND Status = 'Completed'
");

if ($updateResult) {
    header("Location: view_order.php?id=$orderID&success=Order marked as successful! You can no longer request refunds for this order.");
} else {
    header("Location: view_order.php?id=$orderID&error=Failed to mark order as successful: " . mysqli_error($conn));
}
exit;

