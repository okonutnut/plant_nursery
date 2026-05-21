<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header("Location: ../login.php");
    exit;
}

if (!isset($conn)) {
    require_once '../config/database.php';
}

$supplierName = 'Supplier';
if (isset($_SESSION['user_id'])) {
    $userID = $_SESSION['user_id'];
    $userResult = mysqli_query($conn, "SELECT SupplierID, Username FROM user WHERE UserID = $userID");
    if ($userResult) {
        $user = mysqli_fetch_assoc($userResult);
        $supplierID = $user['SupplierID'] ?? null;
        if ($supplierID) {
            $supResult = mysqli_query($conn, "SELECT Name FROM supplier WHERE SupplierID = $supplierID");
            if ($supResult && mysqli_num_rows($supResult) > 0) {
                $sup = mysqli_fetch_assoc($supResult);
                $supplierName = $sup['Name'] ?? $user['Username'];
            } else {
                $supplierName = $user['Username'] ?? 'Supplier';
            }
        } else {
            $supplierName = $user['Username'] ?? 'Supplier';
        }
    }
}

$currentFile = basename($_SERVER['PHP_SELF']);
$activePage = '';
if ($currentFile == 'dashboard.php') {
    $activePage = 'dashboard';
} elseif ($currentFile == 'products.php' || $currentFile == 'create.php' || $currentFile == 'edit.php' || $currentFile == 'restock.php') {
    $activePage = 'products';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Supplier Dashboard'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="../assets/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <h1><i class="fas fa-seedling"></i> Plant Nursery</h1>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php" class="<?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-chart-bar"></i></span>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="products.php" class="<?php echo $activePage == 'products' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-seedling"></i></span>
                    <span class="text">My Products</span>
                </a>
            </li>
        </ul>
        <div style="padding: 1.5rem; border-top: 1px solid rgba(255,255,255,0.2); margin-top: auto;">
            <div style="color: white; margin-bottom: 0.5rem; font-size: 0.9rem;">
                <strong><?php echo htmlspecialchars(strtoupper($supplierName)); ?></strong>
                <div><span class="badge" style="background: rgba(255,255,255,0.2); color: white; font-size: 0.75rem; margin-top: 0.25rem;"><?php echo htmlspecialchars(strtoupper($_SESSION['role'] ?? '')); ?></span></div>
            </div>
            <a href="../logout.php" style="display: block; color: white; text-decoration: none; padding: 0.5rem; border-radius: 5px; background: rgba(255,255,255,0.1); text-align: center; transition: background 0.3s;">
                <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                <span class="text">Logout</span>
            </a>
        </div>
    </div>
    <div class="main-content">
        <div class="container-fluid py-4 px-4">
