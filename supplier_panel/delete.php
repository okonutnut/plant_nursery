<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$userID = $_SESSION['user_id'];
$userResult = mysqli_query($conn, "SELECT SupplierID FROM user WHERE UserID = $userID");
$user = mysqli_fetch_assoc($userResult);
$supplierID = $user['SupplierID'] ?? null;

$id = (int)$_GET['id'];

// Only allow deleting own products
$stmt = mysqli_prepare($conn, "DELETE FROM plant WHERE PlantID = ? AND SupplierID = ?");
mysqli_stmt_bind_param($stmt, "ii", $id, $supplierID);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header("Location: products.php?deleted=1");
exit;
