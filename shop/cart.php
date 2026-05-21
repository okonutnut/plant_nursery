<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $plantID = (int)$_GET['remove'];
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['PlantID'] == $plantID) {
            unset($_SESSION['cart'][$key]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
            break;
        }
    }
    header("Location: cart.php");
    exit;
}

// Handle update quantity
if (isset($_POST['update_quantity'])) {
    $plantID = (int)$_POST['plant_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Get current available quantity
    $plantResult = mysqli_query($conn, "SELECT QuantityAvailable FROM plant WHERE PlantID = $plantID");
    $plant = mysqli_fetch_assoc($plantResult);
    
    if ($plant && $quantity > 0 && $quantity <= $plant['QuantityAvailable']) {
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['PlantID'] == $plantID) {
                $item['Quantity'] = $quantity;
                break;
            }
        }
    }
    header("Location: cart.php");
    exit;
}

// Calculate totals
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['Price'] * $item['Quantity'];
}

$pageTitle = 'Shopping Cart';
include 'includes/header.php';
?>

<style>
    .cart-table {
        background: white;
        padding: 1rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    .cart-table table {
        width: 100%;
        border-collapse: collapse;
    }
    .cart-table th {
        text-align: left;
        padding: 1rem;
        border-bottom: 2px solid #ddd;
        color: white;
        background-color: var(--primary-color);
    }
    .cart-table th:last-child {
        text-align: end;
    }
    .cart-table td {
        padding: 1rem;
        border-bottom: 1px solid #eee;
    }
    .cart-table td:last-child {
        text-align: end;
    }
    .quantity-input {
        width: 60px;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        text-align: center;
    }
    .cart-summary {
        background: white;
        border-radius: 10px;
        padding: 1rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #eee;
    }
    .summary-row.total {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--primary-color);
        border-bottom: none;
        margin-top: 1rem;
    }
    .empty-cart {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .no-results {
        text-align: center;
        padding: 2rem;
        color: #666;
        display: none;
    }
</style>

<div class="page-header mb-4">
    <h2><i class="fas fa-shopping-cart"></i> Shopping Cart</h2>
    <p class="mb-0">Review and manage your cart items</p>
</div>
        <?php if (count($_SESSION['cart']) > 0): ?>
            <div class="cart-table">
                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchCart" placeholder="Search by plant name or price..." onkeyup="filterCartItems()">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                    </div>
                </div>
                <div class="no-results" id="noResults" style="padding: 1rem;">
                    <p>No items found matching your search.</p>
                </div>
                <table id="cartTable">
                    <thead>
                        <tr>
                            <th>Plant</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="cartTableBody">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['Name']); ?></strong>
                                </td>
                                <td>$<?php echo number_format($item['Price'], 2); ?></td>
                                <td>
                                    <form method="POST" style="display: inline-flex; gap: 0.5rem; align-items: center;">
                                        <input type="hidden" name="plant_id" value="<?php echo $item['PlantID']; ?>">
                                        <input type="number" name="quantity" class="quantity-input form-control" style="width: 60px;" value="<?php echo $item['Quantity']; ?>" min="1" max="<?php echo $item['QuantityAvailable']; ?>" required>
                                        <button type="submit" name="update_quantity" class="btn btn-primary">Update</button>
                                    </form>
                                </td>
                                <td>$<?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></td>
                                <td>
                                    <a href="?remove=<?php echo $item['PlantID']; ?>" class="btn btn-danger" onclick="return confirm('Remove this item from cart?')">Remove</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="cart-summary">
                <h2 style="margin-top: 0; color: var(--primary-color);">Order Summary</h2>
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <a href="checkout.php" class="btn btn-success w-100 mt-3" style="font-size: 1.2rem; padding: 1rem;">Proceed to Checkout</a>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Start shopping to add items to your cart!</p>
                <a href="shop.php" class="btn btn-primary mt-3">Browse Plants</a>
            </div>
        <?php endif; ?>

<script>
    // Search functionality for cart items
    function filterCartItems() {
        const input = document.getElementById('searchCart');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('cartTable');
        const tbody = document.getElementById('cartTableBody');
        const rows = tbody ? tbody.getElementsByTagName('tr') : [];
        const noResults = document.getElementById('noResults');
        
        let visibleCount = 0;
        
        // Loop through all rows
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const plantName = row.cells[0] ? row.cells[0].textContent.toLowerCase() : '';
            const price = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
            const subtotal = row.cells[3] ? row.cells[3].textContent.toLowerCase() : '';
            
            if (plantName.includes(filter) || price.includes(filter) || subtotal.includes(filter)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }
        
        // Show/hide no results message
        if (visibleCount === 0 && filter !== '') {
            noResults.style.display = 'block';
            if (table) table.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            if (table) table.style.display = 'table';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>

