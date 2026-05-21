<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartCount = count($_SESSION['cart']);

// Determine active page
$currentFile = basename($_SERVER['PHP_SELF']);
$activePage = '';
if ($currentFile == 'shop.php') {
    $activePage = 'shop';
} elseif ($currentFile == 'cart.php') {
    $activePage = 'cart';
} elseif ($currentFile == 'my_orders.php' || $currentFile == 'view_order.php') {
    $activePage = 'orders';
} elseif ($currentFile == 'account_settings.php') {
    $activePage = 'settings';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Plant Nursery Shop'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="../assets/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <h1><i class="fas fa-seedling"></i> Plant Nursery</h1>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="shop.php" class="<?php echo $activePage == 'shop' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-store"></i></span>
                    <span class="text">Shop</span>
                </a>
            </li>
            <li>
                <a href="cart.php" class="<?php echo $activePage == 'cart' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-shopping-cart"></i></span>
                    <span class="text">Cart</span>
                    <?php if ($cartCount > 0): ?>
                        <span style="margin-left: auto; background: rgba(255,255,255,0.3); border-radius: 12px; padding: 0.2rem 0.5rem; font-size: 0.85rem;">
                            <?php echo $cartCount; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="my_orders.php" class="<?php echo $activePage == 'orders' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-list-alt"></i></span>
                    <span class="text">My Orders</span>
                </a>
            </li>
            <li>
                <a href="account_settings.php" class="<?php echo $activePage == 'settings' ? 'active' : ''; ?>">
                    <span class="icon"><i class="fas fa-cog"></i></span>
                    <span class="text">Account Settings</span>
                </a>
            </li>
        </ul>
        <div style="padding: 1.5rem; border-top: 1px solid rgba(255,255,255,0.2); margin-top: auto;">
            <div style="color: white; margin-bottom: 0.5rem; font-size: 0.9rem;">
                <strong><?php echo htmlspecialchars(strtoupper($_SESSION['username'] ?? 'User')); ?></strong>
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
