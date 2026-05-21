<?php
require_once '../config/database.php';

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
    
    // Check if quantities are available (always validate regardless of status)
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
                if ($plant['QuantityAvailable'] < $quantity) {
                    $error = "Insufficient quantity for {$plant['Name']}. Available: {$plant['QuantityAvailable']}, Requested: $quantity";
                    break;
                }
            }
        }
    }
    
    if (empty($error)) {
        // Calculate total from order items
        $totalAmount = 0;
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['PlantID']) && !empty($item['Quantity']) && !empty($item['Price'])) {
                    $totalAmount += $item['Quantity'] * $item['Price'];
                }
            }
        }
        
        $sql = "INSERT INTO `order` (CustomerID, EmployeeID, OrderDate, TotalAmount, Status) VALUES ($customerID, $employeeID, '$orderDate', $totalAmount, '$status')";
        if (mysqli_query($conn, $sql)) {
            $orderID = mysqli_insert_id($conn);
            
            // Insert order items
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['PlantID']) && !empty($item['Quantity']) && !empty($item['Price'])) {
                        $plantID = $item['PlantID'];
                        $quantity = $item['Quantity'];
                        $price = $item['Price'];
                        
                        // Insert order item
                        mysqli_query($conn, "INSERT INTO orderitem (OrderID, PlantID, Quantity, Price) VALUES ($orderID, $plantID, $quantity, $price)");
                        
                        // Decrease plant quantity only if status is 'Completed'
                        if ($status === 'Completed') {
                            mysqli_query($conn, "UPDATE plant SET QuantityAvailable = QuantityAvailable - $quantity WHERE PlantID = $plantID");
                        }
                    }
                }
            }
            
            header("Location: orderpage.php?success=1");
            exit;
        } else {
            $error = 'Error: ' . mysqli_error($conn);
        }
    }
}

$pageTitle = 'Create Order';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Create Order</h2>
    <a href="orderpage.php" class="btn btn-secondary">Back to List</a>
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
                            <option value="<?php echo $customer['CustomerID']; ?>"><?php echo htmlspecialchars($customer['Name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="EmployeeID" class="form-label">Employee</label>
                    <select class="form-select" id="EmployeeID" name="EmployeeID">
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['EmployeeID']; ?>"><?php echo htmlspecialchars($employee['Name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="OrderDate" class="form-label">Order Date</label>
                    <input type="date" class="form-control" id="OrderDate" name="OrderDate" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="Status" class="form-label">Status</label>
                    <select class="form-select" id="Status" name="Status">
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            
            <h3 class="mt-4 mb-3" style="color: var(--primary-color);">Order Items</h3>
            <div id="orderItems">
                <div class="order-item card mb-3">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Plant</label>
                                <select name="items[0][PlantID]" class="form-select plant-select" required>
                                    <option value="">Select Plant</option>
                                    <?php foreach ($plants as $plant): ?>
                                        <option value="<?php echo $plant['PlantID']; ?>" data-price="<?php echo $plant['Price']; ?>" data-quantity="<?php echo $plant['QuantityAvailable']; ?>">
                                            <?php echo htmlspecialchars($plant['Name'] . ' - $' . number_format($plant['Price'], 2) . ' (Available: ' . $plant['QuantityAvailable'] . ')'); ?>
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
            </div>
            
            <button type="button" id="addItem" class="btn btn-secondary mb-3">Add Item</button>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Order</button>
                <a href="orderpage.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
let itemCount = 1;

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
    const quantityLabel = quantityInput.previousElementSibling;
    
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

