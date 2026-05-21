<?php
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: orderpage.php");
    exit;
}

$id = $_GET['id'];

// Get order status
$orderResult = mysqli_query($conn, "SELECT Status FROM `order` WHERE OrderID = $id");
$order = mysqli_fetch_assoc($orderResult);

// Restore plant quantities only if order status was 'Completed'
if ($order && $order['Status'] === 'Completed') {
    $orderItems = mysqli_query($conn, "SELECT PlantID, Quantity FROM orderitem WHERE OrderID = $id");
    while ($item = mysqli_fetch_assoc($orderItems)) {
        // Restore plant quantities
        mysqli_query($conn, "UPDATE plant SET QuantityAvailable = QuantityAvailable + {$item['Quantity']} WHERE PlantID = {$item['PlantID']}");
    }
}

// Delete order items first (due to foreign key)
mysqli_query($conn, "DELETE FROM orderitem WHERE OrderID = $id");

// Delete order
mysqli_query($conn, "DELETE FROM `order` WHERE OrderID = $id");

header("Location: orderpage.php?success=1");
exit;
?>

