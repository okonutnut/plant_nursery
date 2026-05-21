<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: my_orders.php");
    exit;
}

// Get customer ID from user email
$userEmail = $_SESSION['email'];
$customerResult = mysqli_query($conn, "SELECT CustomerID FROM customer WHERE Email = '$userEmail' LIMIT 1");
$customer = mysqli_fetch_assoc($customerResult);

if (!$customer) {
    header("Location: my_orders.php?error=Customer not found");
    exit;
}

$customerID = $customer['CustomerID'];
$orderID = (int)$_POST['order_id'];
$orderItemID = (int)$_POST['order_item_id'];
$quantity = (int)$_POST['quantity'];
$reason = mysqli_real_escape_string($conn, $_POST['reason']);

// Validate inputs
if (empty($reason) || $quantity < 1) {
    header("Location: view_order.php?id=$orderID&error=Invalid input");
    exit;
}

// Verify order belongs to customer
$orderResult = mysqli_query($conn, "
    SELECT o.*, DATEDIFF(CURDATE(), o.OrderDate) as DaysSinceOrder
    FROM `order` o
    WHERE o.OrderID = $orderID AND o.CustomerID = $customerID
");
$order = mysqli_fetch_assoc($orderResult);

if (!$order) {
    header("Location: my_orders.php?error=Order not found");
    exit;
}

// Check if order status is 'Completed' (required for refund requests)
if (strtolower($order['Status']) !== 'completed') {
    header("Location: view_order.php?id=$orderID&error=Refund requests can only be made for orders with 'Completed' status. Your order status is currently: " . htmlspecialchars($order['Status']));
    exit;
}

// Check if order is marked as successful (prevents refund requests)
if (isset($order['IsSuccessful']) && $order['IsSuccessful'] == 1) {
    header("Location: view_order.php?id=$orderID&error=This order has been marked as successful. Refund requests are no longer available for successful orders.");
    exit;
}

// Check if order is within 30-day warranty period
if ($order['DaysSinceOrder'] > 30) {
    header("Location: view_order.php?id=$orderID&error=Warranty period has expired. Refunds are only available within 30 days of purchase.");
    exit;
}

// Verify order item exists and belongs to this order
$itemResult = mysqli_query($conn, "
    SELECT oi.*, p.Name as PlantName
    FROM orderitem oi
    LEFT JOIN plant p ON oi.PlantID = p.PlantID
    WHERE oi.OrderItemID = $orderItemID AND oi.OrderID = $orderID
");
$item = mysqli_fetch_assoc($itemResult);

if (!$item) {
    header("Location: view_order.php?id=$orderID&error=Order item not found");
    exit;
}

// Check if quantity requested doesn't exceed item quantity
if ($quantity > $item['Quantity']) {
    header("Location: view_order.php?id=$orderID&error=Quantity requested exceeds order quantity");
    exit;
}

// Check if there's already a pending or approved refund request for this item
$existingRefund = mysqli_query($conn, "
    SELECT * FROM warranty_refund 
    WHERE OrderItemID = $orderItemID 
    AND Status IN ('Pending', 'Approved')
");
if (mysqli_num_rows($existingRefund) > 0) {
    header("Location: view_order.php?id=$orderID&error=A refund request already exists for this item");
    exit;
}

// Insert refund request
$sql = "INSERT INTO warranty_refund (OrderID, OrderItemID, CustomerID, Quantity, Reason, Status) 
        VALUES ($orderID, $orderItemID, $customerID, $quantity, '$reason', 'Pending')";

if (mysqli_query($conn, $sql)) {
    header("Location: view_order.php?id=$orderID&success=Refund request submitted successfully. We will review your request and get back to you soon.");
} else {
    header("Location: view_order.php?id=$orderID&error=Error submitting refund request: " . mysqli_error($conn));
}
exit;

