<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: my_orders.php");
    exit;
}

$orderID = (int)$_GET['id'];

$userEmail = $_SESSION['email'];
$customerResult = mysqli_query($conn, "SELECT CustomerID FROM customer WHERE Email = '$userEmail' LIMIT 1");
$customer = mysqli_fetch_assoc($customerResult);

if (!$customer) {
    header("Location: my_orders.php?error=Customer not found");
    exit;
}

$customerID = $customer['CustomerID'];

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

if (strtolower($order['Status']) !== 'pending') {
    header("Location: view_order.php?id=$orderID&error=Only pending orders that have not been shipped yet can be cancelled");
    exit;
}

$updateResult = mysqli_query($conn, "
    UPDATE `order`
    SET Status = 'Cancelled'
    WHERE OrderID = $orderID AND CustomerID = $customerID AND Status = 'Pending'
");

if ($updateResult && mysqli_affected_rows($conn) > 0) {
    header("Location: my_orders.php?success=Order #$orderID has been cancelled successfully");
} else {
    header("Location: view_order.php?id=$orderID&error=Failed to cancel order. Please try again.");
}
exit;
?>