<?php
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: supplierpage.php");
    exit;
}

$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM supplier WHERE SupplierID = $id");

header("Location: supplierpage.php?success=1");
exit;
?>

