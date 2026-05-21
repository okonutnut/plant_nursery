<?php
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: costumerpage.php");
    exit;
}

$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM customer WHERE CustomerID = $id");

header("Location: costumerpage.php?success=1");
exit;
?>

