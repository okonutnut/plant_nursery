<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Check if user is logged in (except for login page)
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Plant Nursery Management System'; ?></title>
    <?php
    // Determine paths for assets
    $cssPath = 'assets/css/style.css';
    $bootstrapCssPath = 'assets/dist/css/bootstrap.min.css';
    $scriptPath = $_SERVER['PHP_SELF'];
    if (strpos($scriptPath, '/customer/') !== false || strpos($scriptPath, '/supplier/') !== false || 
        strpos($scriptPath, '/plantcategory/') !== false || strpos($scriptPath, '/planttype/') !== false || 
        strpos($scriptPath, '/plant/') !== false || strpos($scriptPath, '/employee/') !== false || 
        strpos($scriptPath, '/order/') !== false || strpos($scriptPath, '/refund/') !== false ||
        strpos($scriptPath, '/admin/') !== false || strpos($scriptPath, '/user/') !== false) {
        $cssPath = '../' . $cssPath;
        $bootstrapCssPath = '../' . $bootstrapCssPath;
    }
    ?>
    <!-- Bootstrap 5 CSS -->
    <link href="<?php echo $bootstrapCssPath; ?>" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <?php
    // Determine base path for sidebar links
    $basePath = '';
    $currentFile = basename($_SERVER['PHP_SELF']);
    $scriptPath = $_SERVER['PHP_SELF'];
    
    // Check if we're in a subdirectory
    if (strpos($scriptPath, '/customer/') !== false || strpos($scriptPath, '/supplier/') !== false || 
        strpos($scriptPath, '/plantcategory/') !== false || strpos($scriptPath, '/planttype/') !== false || 
        strpos($scriptPath, '/plant/') !== false || strpos($scriptPath, '/employee/') !== false || 
        strpos($scriptPath, '/order/') !== false || strpos($scriptPath, '/refund/') !== false ||
        strpos($scriptPath, '/admin/') !== false || strpos($scriptPath, '/user/') !== false) {
        $basePath = '../';
    }
    
    // Determine active page
    $activePage = '';
    if ($currentFile == 'index.php' && ($basePath == '' || strpos($scriptPath, '/index.php') !== false)) {
        $activePage = 'dashboard';
    } elseif (strpos($scriptPath, 'plantcategory') !== false || $currentFile == 'categorypage.php') {
        $activePage = 'categories';
    } elseif (strpos($scriptPath, 'planttype') !== false || $currentFile == 'typepage.php') {
        $activePage = 'planttypes';
    } elseif (strpos($scriptPath, 'supplier') !== false || $currentFile == 'supplierpage.php') {
        $activePage = 'suppliers';
    } elseif ((strpos($scriptPath, '/plant/') !== false && strpos($scriptPath, 'plantcategory') === false) || $currentFile == 'plantpage.php') {
        $activePage = 'plants';
    } elseif (strpos($scriptPath, 'customer') !== false || $currentFile == 'costumerpage.php') {
        $activePage = 'customers';
    } elseif (strpos($scriptPath, '/user/') !== false || $currentFile == 'userspage.php') {
        $activePage = 'users';
    } elseif (strpos($scriptPath, 'employee') !== false || $currentFile == 'employeepage.php') {
        $activePage = 'employees';
    } elseif (strpos($scriptPath, 'order') !== false || $currentFile == 'orderpage.php') {
        $activePage = 'orders';
    } elseif (strpos($scriptPath, 'refund') !== false || $currentFile == 'refundpage.php') {
        $activePage = 'refunds';
    }
    ?>
    <div class="sidebar">
        <div class="sidebar-brand">
            <h1><i class="fas fa-seedling"></i> Plant Nursery</h1>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="<?php echo $basePath; ?>index.php" class="<?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-chart-bar"></i></span>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>plantcategory/categorypage.php" class="<?php echo $activePage == 'categories' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-folder"></i></span>
                    <span class="text">Categories</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>planttype/typepage.php" class="<?php echo $activePage == 'planttypes' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-leaf"></i></span>
                    <span class="text">Plant Types</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>supplier/supplierpage.php" class="<?php echo $activePage == 'suppliers' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-building"></i></span>
                    <span class="text">Suppliers</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>plant/plantpage.php" class="<?php echo $activePage == 'plants' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-seedling"></i></span>
                    <span class="text">Plants</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>customer/costumerpage.php" class="<?php echo $activePage == 'customers' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-shopping-cart"></i></span>
                    <span class="text">Customers</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>user/userspage.php" class="<?php echo $activePage == 'users' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-users"></i></span>
                    <span class="text">Users</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>employee/employeepage.php" class="<?php echo $activePage == 'employees' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-user-tie"></i></span>
                    <span class="text">Employees</span>
                </a>
            </li>
        </ul>
        <div style="padding: 1.5rem; border-top: 1px solid rgba(255,255,255,0.2); margin-top: auto;">
            <div style="color: white; margin-bottom: 0.5rem; font-size: 0.9rem;">
                <strong><?php echo htmlspecialchars(strtoupper($_SESSION['username'] ?? 'User')); ?></strong>
                <div><span class="badge" style="background: rgba(255,255,255,0.2); color: white; font-size: 0.75rem; margin-top: 0.25rem;"><?php echo htmlspecialchars(strtoupper($_SESSION['role'] ?? '')); ?></span></div>
            </div>
            <a href="<?php echo $basePath; ?>logout.php" style="display: block; color: white; text-decoration: none; padding: 0.5rem; border-radius: 5px; background: rgba(255,255,255,0.1); text-align: center; transition: background 0.3s;">
                <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                <span class="text">Logout</span>
            </a>
        </div>
    </div>
    <div class="main-content">
        <div class="container-fluid py-4 px-4">

