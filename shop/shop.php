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

// Handle Add to Cart
if (isset($_GET['add_to_cart'])) {
    $plantID = (int)$_GET['add_to_cart'];
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
    
    $plantResult = mysqli_query($conn, "SELECT * FROM plant WHERE PlantID = $plantID AND QuantityAvailable > 0");
    $plant = mysqli_fetch_assoc($plantResult);
    
    if ($plant) {
        if ($quantity > $plant['QuantityAvailable']) {
            $quantity = $plant['QuantityAvailable'];
        }
        
        // Check if plant already in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['PlantID'] == $plantID) {
                $item['Quantity'] = min($item['Quantity'] + $quantity, $plant['QuantityAvailable']);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $_SESSION['cart'][] = [
                'PlantID' => $plantID,
                'Name' => $plant['Name'],
                'Price' => $plant['Price'],
                'Quantity' => $quantity,
                'QuantityAvailable' => $plant['QuantityAvailable']
            ];
        }
        
        header("Location: shop.php?added=1");
        exit;
    }
}

// Handle Buy Now
if (isset($_GET['buy_now'])) {
    $plantID = (int)$_GET['buy_now'];
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
    
    // Clear cart and add single item
    $_SESSION['cart'] = [];
    
    $plantResult = mysqli_query($conn, "SELECT * FROM plant WHERE PlantID = $plantID AND QuantityAvailable > 0");
    $plant = mysqli_fetch_assoc($plantResult);
    
    if ($plant) {
        if ($quantity > $plant['QuantityAvailable']) {
            $quantity = $plant['QuantityAvailable'];
        }
        
        $_SESSION['cart'][] = [
            'PlantID' => $plantID,
            'Name' => $plant['Name'],
            'Price' => $plant['Price'],
            'Quantity' => $quantity,
            'QuantityAvailable' => $plant['QuantityAvailable']
        ];
        
        header("Location: checkout.php");
        exit;
    }
}

// Get all available plants
$result = mysqli_query($conn, "
    SELECT p.*, pc.Name as CategoryName, s.Name as SupplierName
    FROM plant p
    LEFT JOIN plantcategory pc ON p.PlantCategoryID = pc.PlantCategoryID
    LEFT JOIN supplier s ON p.SupplierID = s.SupplierID
    WHERE p.QuantityAvailable > 0
    ORDER BY p.Name
");
$plants = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Fetch images and convert to base64 for each plant
foreach ($plants as &$plant) {
    $plantID = $plant['PlantID'];
    $stmt = mysqli_prepare($conn, "SELECT PlantImg FROM plant WHERE PlantID = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $plantID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $imageData);
        if (mysqli_stmt_fetch($stmt) && $imageData !== null && strlen($imageData) > 0) {
            // Detect MIME type
            $imageInfo = @getimagesizefromstring($imageData);
            if ($imageInfo !== false && isset($imageInfo['mime'])) {
                $mimeType = $imageInfo['mime'];
            } else {
                // Detect by file signature
                if (substr($imageData, 0, 2) === "\xFF\xD8") {
                    $mimeType = 'image/jpeg';
                } elseif (substr($imageData, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
                    $mimeType = 'image/png';
                } elseif (substr($imageData, 0, 6) === "GIF87a" || substr($imageData, 0, 6) === "GIF89a") {
                    $mimeType = 'image/gif';
                } elseif (substr($imageData, 0, 4) === "RIFF" && substr($imageData, 8, 4) === "WEBP") {
                    $mimeType = 'image/webp';
                } else {
                    $mimeType = 'image/jpeg'; // default
                }
            }
            // Convert to base64 data URI
            $plant['ImageBase64'] = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        } else {
            // No image - use placeholder SVG
            $plant['ImageBase64'] = 'data:image/svg+xml;base64,' . base64_encode('<?xml version="1.0"?><svg width="200" height="200" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="200" fill="#e0e0e0"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#999" font-family="Arial" font-size="14">No Image</text></svg>');
        }
        mysqli_stmt_close($stmt);
    } else {
        // Error preparing statement - use placeholder
        $plant['ImageBase64'] = 'data:image/svg+xml;base64,' . base64_encode('<?xml version="1.0"?><svg width="200" height="200" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="200" fill="#e0e0e0"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#999" font-family="Arial" font-size="14">No Image</text></svg>');
    }
}
unset($plant); // Break reference

// Get plant categories for filtering
$categoryResult = mysqli_query($conn, "SELECT * FROM plantcategory ORDER BY Name");
$categories = mysqli_fetch_all($categoryResult, MYSQLI_ASSOC);

$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$pageTitle = 'Shop Plants';
include 'includes/header.php';
?>

<style>
    .no-results {
        text-align: center;
        padding: 2rem;
        color: #666;
        display: none;
    }
    
    .plant-image-container {
        cursor: pointer;
        transition: transform 0.2s;
    }
    
    .plant-image-container:hover {
        transform: scale(1.02);
    }
    
    .image-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        align-items: center;
        justify-content: center;
    }
    
    .image-modal.active {
        display: flex;
    }
    
    .modal-content-img {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
        animation: zoomIn 0.3s;
    }
    
    @keyframes zoomIn {
        from {
            transform: scale(0.8);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }
    
    .close-modal {
        position: absolute;
        top: 20px;
        right: 40px;
        color: #fff;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.3s;
    }
    
    .close-modal:hover {
        color: #bbb;
    }
</style>

<div class="page-header mb-4">
    <h2>Plant Shop</h2>
    <p class="mb-0">Find the perfect plants for your garden in our nursery</p>
</div>

<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success">
        Item added to cart successfully! <a href="cart.php">View Cart</a>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label for="searchPlants" class="form-label">Search Plants:</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="searchPlants" placeholder="Search by plant name or price..." onkeyup="filterPlants()">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
            </div>
            <div class="col-md-4">
                <label for="categoryFilter" class="form-label">Filter by Category:</label>
                <select class="form-select" id="categoryFilter" onchange="filterByCategory()">
                    <option value="0" <?php echo $selectedCategory == 0 ? 'selected' : ''; ?>>All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['PlantCategoryID']; ?>" <?php echo $selectedCategory == $category['PlantCategoryID'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['Name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="no-results" id="noResults">
    <p>No plants found matching your search.</p>
</div>

<?php if (count($plants) > 0): ?>
    <div class="row g-4" id="plantsContainer">
        <?php foreach ($plants as $plant): ?>
            <?php if ($selectedCategory == 0 || $plant['PlantCategoryID'] == $selectedCategory): ?>
                <div class="col-md-6 col-lg-4 col-xl-3 plant-card">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="plant-image-container" style="height: 200px; width: 100%; overflow: hidden; border-radius: 8px; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;" onclick="openImageModal('<?php echo htmlspecialchars($plant['ImageBase64'] ?? '../plant/image.php?id=' . $plant['PlantID']); ?>', '<?php echo htmlspecialchars($plant['Name']); ?>')">
                                <img src="<?php echo isset($plant['ImageBase64']) ? htmlspecialchars($plant['ImageBase64']) : '../plant/image.php?id=' . $plant['PlantID']; ?>" alt="<?php echo htmlspecialchars($plant['Name']); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($plant['Name']); ?></h5>
                            <p class="text-muted mb-2"><em><?php echo htmlspecialchars($plant['ScientificName'] ?? 'N/A'); ?></em></p>
                            <p class="text-muted small mb-2">Category: <?php echo htmlspecialchars($plant['CategoryName'] ?? 'N/A'); ?></p>
                            <h4 class="text-primary mb-3">₱<?php echo number_format($plant['Price'], 2); ?></h4>
                            <p class="text-muted small mb-3">Available: <?php echo $plant['QuantityAvailable']; ?> units</p>
                            <form method="GET" class="d-flex flex-column gap-2 mt-auto">
                                <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?php echo $plant['QuantityAvailable']; ?>" required>
                                <button type="submit" name="buy_now" value="<?php echo $plant['PlantID']; ?>" class="btn btn-success">
                                    <i class="fas fa-bolt"></i> Buy Now
                                </button>
                                <button type="submit" name="add_to_cart" value="<?php echo $plant['PlantID']; ?>" class="btn btn-primary">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <p class="text-muted mb-0">No plants available at the moment.</p>
        </div>
    </div>
<?php endif; ?>

<!-- Image Modal -->
<div id="imageModal" class="image-modal" onclick="closeImageModal()">
    <span class="close-modal">&times;</span>
    <img id="modalImage" class="modal-content-img" alt="Plant Image">
</div>

<script>
function openImageModal(imageSrc, plantName) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    modal.classList.add('active');
    modalImg.src = imageSrc;
    modalImg.alt = plantName;
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});

function filterByCategory() {
    const categoryId = document.getElementById('categoryFilter').value;
    window.location.href = 'shop.php?category=' + categoryId;
}

// Search functionality for plants
function filterPlants() {
    const input = document.getElementById('searchPlants');
    const filter = input.value.toLowerCase();
    const plantCards = document.querySelectorAll('.plant-card');
    const noResults = document.getElementById('noResults');
    const plantsContainer = document.getElementById('plantsContainer');
    
    let visibleCount = 0;
    
    // Loop through all plant cards
    for (let i = 0; i < plantCards.length; i++) {
        const card = plantCards[i];
        const cardText = card.textContent.toLowerCase();
        
        if (cardText.includes(filter)) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    }
    
    // Show/hide no results message
    if (visibleCount === 0 && filter !== '') {
        noResults.style.display = 'block';
        if (plantsContainer) plantsContainer.style.display = 'none';
    } else {
        noResults.style.display = 'none';
        if (plantsContainer) plantsContainer.style.display = '';
    }
}
</script>

<?php include 'includes/footer.php'; ?>