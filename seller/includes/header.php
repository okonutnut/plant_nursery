<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Check if user is logged in and is a seller/staff/employee
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'seller' && $_SESSION['role'] !== 'employee' && $_SESSION['role'] !== 'staff')) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Seller Dashboard'; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <?php
    // Get employee name for display
    // Database connection should already be loaded by the calling page
    if (!isset($conn)) {
        require_once '../../config/database.php';
    }
    
    $employeeName = 'Seller';
    if (isset($_SESSION['user_id'])) {
        $userID = $_SESSION['user_id'];
        $userResult = mysqli_query($conn, "SELECT EmployeeID, Username FROM user WHERE UserID = $userID");
        if ($userResult) {
            $user = mysqli_fetch_assoc($userResult);
            $employeeID = $user['EmployeeID'] ?? null;
            
            if ($employeeID) {
                $empResult = mysqli_query($conn, "SELECT Name FROM employee WHERE EmployeeID = $employeeID");
                if ($empResult) {
                    $emp = mysqli_fetch_assoc($empResult);
                    $employeeName = $emp['Name'] ?? $user['Username'];
                } else {
                    $employeeName = $user['Username'] ?? 'Seller';
                }
            } else {
                $employeeName = $user['Username'] ?? 'Seller';
            }
        }
    }
    
    // Determine active page
    $currentFile = basename($_SERVER['PHP_SELF']);
    $activePage = '';
    if ($currentFile == 'sellerpage.php') {
        $activePage = 'dashboard';
    } elseif ($currentFile == 'orders.php' || $currentFile == 'view_order.php' || $currentFile == 'approve_order.php') {
        $activePage = 'orders';
    } elseif ($currentFile == 'refunds.php' || $currentFile == 'view_refund.php' || $currentFile == 'process_refund.php') {
        $activePage = 'refunds';
    }
    ?>
    <div class="sidebar">
        <div class="sidebar-brand">
            <h1><i class="fas fa-seedling"></i> Plant Nursery</h1>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="sellerpage.php" class="<?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-chart-bar"></i></span>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="orders.php" class="<?php echo $activePage == 'orders' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-shopping-cart"></i></span>
                    <span class="text">Orders</span>
                </a>
            </li>
            <li>
                <a href="refunds.php" class="<?php echo $activePage == 'refunds' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-dollar-sign"></i></span>
                    <span class="text">Refunds</span>
                </a>
            </li>
        </ul>
        <div style="padding: 1.5rem; border-top: 1px solid rgba(255,255,255,0.2); margin-top: auto;">
            <div style="color: white; margin-bottom: 0.5rem; font-size: 0.9rem;">
                <strong><?php echo htmlspecialchars(strtoupper($employeeName)); ?></strong>
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

