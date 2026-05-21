<?php
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: orderpage.php");
    exit;
}

$id = $_GET['id'];

$result1 = mysqli_query($conn, "SELECT * FROM customer ORDER BY Name");
$customers = mysqli_fetch_all($result1, MYSQLI_ASSOC);
$result2 = mysqli_query($conn, "SELECT * FROM employee ORDER BY Name");
$employees = mysqli_fetch_all($result2, MYSQLI_ASSOC);
$result3 = mysqli_query($conn, "SELECT * FROM plant ORDER BY Name");
$plants = mysqli_fetch_all($result3, MYSQLI_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerID = $_POST['CustomerID'] ? $_POST['CustomerID'] : 'NULL';
    $employeeID = $_POST['EmployeeID'] ? $_POST['EmployeeID'] : 'NULL';
    $orderDate = mysqli_real_escape_string($conn, $_POST['OrderDate']);
    $status = mysqli_real_escape_string($conn, $_POST['Status']);
    $paymentMethod = mysqli_real_escape_string($conn, $_POST['PaymentMethod'] ?? '');
    
    // Get old order status and items
    $oldOrder = mysqli_query($conn, "SELECT Status FROM `order` WHERE OrderID = $id");
    $oldOrderData = mysqli_fetch_assoc($oldOrder);
    $oldStatus = $oldOrderData['Status'];
    
    $oldItemsData = [];
    $oldItems = mysqli_query($conn, "SELECT PlantID, Quantity FROM orderitem WHERE OrderID = $id");
    while ($oldItem = mysqli_fetch_assoc($oldItems)) {
        $oldItemsData[$oldItem['PlantID']] = $oldItem['Quantity'];
    }
    
    // Check if new quantities are available
    if (isset($_POST['items']) && is_array($_POST['items'])) {
        foreach ($_POST['items'] as $item) {
            if (!empty($item['PlantID']) && !empty($item['Quantity'])) {
                $plantID = mysqli_real_escape_string($conn, $item['PlantID']);
                $quantity = (int)$item['Quantity'];
                $plantResult = mysqli_query($conn, "SELECT Name, QuantityAvailable FROM plant WHERE PlantID = $plantID");
                $plant = mysqli_fetch_assoc($plantResult);
                
                if (!$plant) {
                    $error = "Plant not found.";
                    break;
                }
                
                $availableQty = $plant['QuantityAvailable'];
                if ($oldStatus === 'Completed' && isset($oldItemsData[$plantID])) {
                    $availableQty += $oldItemsData[$plantID];
                }
                
                if ($availableQty < $quantity) {
                    $error = "Insufficient quantity for {$plant['Name']}. Available: $availableQty, Requested: $quantity";
                    break;
                }
            }
        }
    }
    
    if (empty($error)) {
        // Restore old quantities if old status was 'Completed'
        if ($oldStatus === 'Completed') {
            foreach ($oldItemsData as $plantID => $oldQty) {
                mysqli_query($conn, "UPDATE plant SET QuantityAvailable = QuantityAvailable + $oldQty WHERE PlantID = $plantID");
            }
        }
        
        // Calculate total from order items
        $totalAmount = 0;
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['PlantID']) && !empty($item['Quantity']) && !empty($item['Price'])) {
                    $totalAmount += $item['Quantity'] * $item['Price'];
                }
            }
        }
        
        $sql = "UPDATE `order` SET CustomerID = $customerID, EmployeeID = $employeeID, OrderDate = '$orderDate', TotalAmount = $totalAmount, Status = '$status', PaymentMethod = " . ($paymentMethod ? "'$paymentMethod'" : "NULL") . " WHERE OrderID = $id";
        mysqli_query($conn, $sql);
        
        // Delete existing items
        mysqli_query($conn, "DELETE FROM orderitem WHERE OrderID = $id");
        
        // Insert new items
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['PlantID']) && !empty($item['Quantity']) && !empty($item['Price'])) {
                    $plantID = $item['PlantID'];
                    $quantity = $item['Quantity'];
                    $price = $item['Price'];
                    
                    mysqli_query($conn, "INSERT INTO orderitem (OrderID, PlantID, Quantity, Price) VALUES ($id, $plantID, $quantity, $price)");
                    
                    if ($status === 'Completed') {
                        mysqli_query($conn, "UPDATE plant SET QuantityAvailable = QuantityAvailable - $quantity WHERE PlantID = $plantID");
                    }
                }
            }
        }
        
        header("Location: view.php?id=$id&success=1");
        exit;
    }
}

$result = mysqli_query($conn, "SELECT * FROM `order` WHERE OrderID = $id");
$order = mysqli_fetch_assoc($result);

if (!$order) {
    header("Location: orderpage.php");
    exit;
}

$result4 = mysqli_query($conn, "
    SELECT oi.*, p.Name as PlantName
    FROM orderitem oi
    LEFT JOIN plant p ON oi.PlantID = p.PlantID
    WHERE oi.OrderID = $id
");
$orderItems = mysqli_fetch_all($result4, MYSQLI_ASSOC);

$pageTitle = 'Edit Order';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Edit Order #<?php echo $id; ?></h2>
    <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">Back to View</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" id="orderForm">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="CustomerID" class="form-label">Customer</label>
                    <select class="form-select" id="CustomerID" name="CustomerID">
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer['CustomerID']; ?>" <?php echo $customer['CustomerID'] == $order['CustomerID'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($customer['Name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="EmployeeID" class="form-label">Employee</label>
                    <select class="form-select" id="EmployeeID" name="EmployeeID">
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['EmployeeID']; ?>" <?php echo $employee['EmployeeID'] == $order['EmployeeID'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($employee['Name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="OrderDate" class="form-label">Order Date</label>
                    <input type="date" class="form-control" id="OrderDate" name="OrderDate" value="<?php echo $order['OrderDate']; ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="Status" class="form-label">Status</label>
                    <select class="form-select" id="Status" name="Status">
                        <option value="Pending" <?php echo $order['Status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Processing" <?php echo $order['Status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="Completed" <?php echo $order['Status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo $order['Status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="PaymentMethod" class="form-label">Payment Method</label>
                    <select class="form-select" id="PaymentMethod" name="PaymentMethod">
                        <option value="">Select Payment Method</option>
                        <option value="Cash on Delivery" <?php echo ($order['PaymentMethod'] ?? '') == 'Cash on Delivery' ? 'selected' : ''; ?>>Cash on Delivery</option>
                        <option value="GCash" <?php echo ($order['PaymentMethod'] ?? '') == 'GCash' ? 'selected' : ''; ?>>GCash</option>
                        <option value="Bank Transfer" <?php echo ($order['PaymentMethod'] ?? '') == 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    </select>
                </div>
            </div>
            
            <h3 class="mt-4 mb-3" style="color: var(--primary-color);">Order Items</h3>
            <div id="orderItems">
                <?php if (count($orderItems) > 0): ?>
                    <?php foreach ($orderItems as $index => $item): 
                        $availableQty = 0;
                        foreach ($plants as $plant) {
                            if ($plant['PlantID'] == $item['PlantID']) {
                                $availableQty = $plant['QuantityAvailable'];
                                if ($order['Status'] === 'Completed') {
                                    $availableQty += $item['Quantity'];
                                }
                                break;
                            }
                        }
                    ?>
                        <div class="order-item card mb-3">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label">Plant</label>
                                        <select name="items[<?php echo $index; ?>][PlantID]" class="form-select plant-select" required>
                                            <option value="">Select Plant</option>
                                            <?php foreach ($plants as $plant): 
                                                $itemAvailableQty = $plant['QuantityAvailable'];
                                                if ($order['Status'] === 'Completed' && $plant['PlantID'] == $item['PlantID']) {
                                                    $itemAvailableQty += $item['Quantity'];
                                                }
                                            ?>
                                                <option value="<?php echo $plant['PlantID']; ?>" data-price="<?php echo $plant['Price']; ?>" data-quantity="<?php echo $itemAvailableQty; ?>" <?php echo $plant['PlantID'] == $item['PlantID'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($plant['Name'] . ' - $' . number_format($plant['Price'], 2) . ' (Available: ' . $itemAvailableQty . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="items[<?php echo $index; ?>][Quantity]" class="form-control quantity-input" min="1" value="<?php echo $item['Quantity']; ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Price</label>
                                        <input type="number" name="items[<?php echo $index; ?>][Price]" class="form-control price-input" step="0.01" min="0" value="<?php echo $item['Price']; ?>" required readonly>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="order-item card mb-3">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label">Plant</label>
                                    <select name="items[0][PlantID]" class="form-select plant-select" required>
                                        <option value="">Select Plant</option>
                                        <?php foreach ($plants as $plant): 
                                            $itemAvailableQty = $plant['QuantityAvailable'];
                                            if ($order['Status'] === 'Completed') {
                                                foreach ($orderItems as $oldItem) {
                                                    if ($oldItem['PlantID'] == $plant['PlantID']) {
                                                        $itemAvailableQty += $oldItem['Quantity'];
                                                        break;
                                                    }
                                                }
                                            }
                                        ?>
                                            <option value="<?php echo $plant['PlantID']; ?>" data-price="<?php echo $plant['Price']; ?>" data-quantity="<?php echo $itemAvailableQty; ?>">
                                                <?php echo htmlspecialchars($plant['Name'] . ' - $' . number_format($plant['Price'], 2) . ' (Available: ' . $itemAvailableQty . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" name="items[0][Quantity]" class="form-control quantity-input" min="1" value="1" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" name="items[0][Price]" class="form-control price-input" step="0.01" min="0" required readonly>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger btn-sm remove-item" style="display: none;">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <button type="button" id="addItem" class="btn btn-secondary mb-3">Add Item</button>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Order</button>
                <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
let itemCount = <?php echo count($orderItems); ?>;

function updatePlantDropdowns() {
    const allItems = document.querySelectorAll('.order-item');
    const selectedPlants = new Set();
    
    allItems.forEach(item => {
        const plantSelect = item.querySelector('.plant-select');
        if (plantSelect && plantSelect.value) {
            selectedPlants.add(plantSelect.value);
        }
    });
    
    allItems.forEach(item => {
        const plantSelect = item.querySelector('.plant-select');
        if (plantSelect) {
            const currentValue = plantSelect.value;
            Array.from(plantSelect.options).forEach(option => {
                if (option.value === '' || option.value === currentValue) {
                    option.disabled = false;
                } else if (selectedPlants.has(option.value)) {
                    option.disabled = true;
                } else {
                    option.disabled = false;
                }
            });
        }
    });
}

document.getElementById('addItem').addEventListener('click', function() {
    const itemsContainer = document.getElementById('orderItems');
    const newItem = itemsContainer.firstElementChild.cloneNode(true);
    
    newItem.querySelectorAll('select, input').forEach(input => {
        if (input.name) {
            input.name = input.name.replace(/\[\d+\]/, '[' + itemCount + ']');
        }
        if (input.classList.contains('plant-select')) {
            input.value = '';
        }
        if (input.classList.contains('quantity-input')) {
            input.value = 1;
        }
        if (input.classList.contains('price-input')) {
            input.value = '';
        }
    });
    
    newItem.querySelector('.remove-item').style.display = 'block';
    itemsContainer.appendChild(newItem);
    itemCount++;
    
    attachEventListeners(newItem);
    updatePlantDropdowns();
});

function attachEventListeners(item) {
    const plantSelect = item.querySelector('.plant-select');
    const priceInput = item.querySelector('.price-input');
    const quantityInput = item.querySelector('.quantity-input');
    
    function validateQuantity() {
        const selectedOption = plantSelect.options[plantSelect.selectedIndex];
        const availableQty = selectedOption ? parseInt(selectedOption.getAttribute('data-quantity') || 0) : 0;
        const requestedQty = parseInt(quantityInput.value || 0);
        
        if (plantSelect.value && requestedQty > availableQty) {
            quantityInput.classList.add('is-invalid');
            quantityInput.setCustomValidity(`Maximum available: ${availableQty}`);
        } else {
            quantityInput.classList.remove('is-invalid');
            quantityInput.setCustomValidity('');
        }
    }
    
    plantSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        priceInput.value = price || '';
        validateQuantity();
        updatePlantDropdowns();
    });
    
    quantityInput.addEventListener('input', validateQuantity);
    quantityInput.addEventListener('change', validateQuantity);
    
    item.querySelector('.remove-item').addEventListener('click', function() {
        item.remove();
        updatePlantDropdowns();
    });
}

document.querySelectorAll('.order-item').forEach(item => {
    attachEventListeners(item);
});

updatePlantDropdowns();

document.getElementById('orderForm').addEventListener('submit', function(e) {
    let hasError = false;
    const items = document.querySelectorAll('.order-item');
    
    items.forEach(item => {
        const plantSelect = item.querySelector('.plant-select');
        const quantityInput = item.querySelector('.quantity-input');
        
        if (plantSelect.value) {
            const selectedOption = plantSelect.options[plantSelect.selectedIndex];
            const availableQty = parseInt(selectedOption.getAttribute('data-quantity') || 0);
            const requestedQty = parseInt(quantityInput.value || 0);
            
            if (requestedQty > availableQty) {
                hasError = true;
                quantityInput.classList.add('is-invalid');
                quantityInput.focus();
            }
        }
    });
    
    const plantIds = [];
    items.forEach(item => {
        const plantSelect = item.querySelector('.plant-select');
        if (plantSelect && plantSelect.value) {
            if (plantIds.includes(plantSelect.value)) {
                hasError = true;
                plantSelect.classList.add('is-invalid');
                alert('Each plant can only be selected once. Please remove duplicate selections.');
            } else {
                plantIds.push(plantSelect.value);
            }
        }
    });
    
    if (hasError) {
        e.preventDefault();
        if (!plantIds.length || plantIds.length === new Set(plantIds).size) {
            alert('Please ensure all quantities do not exceed available stock.');
        }
        return false;
    }
});
</script>

<?php include '../includes/footer.php'; ?>

