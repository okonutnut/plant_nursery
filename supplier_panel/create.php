<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header("Location: ../login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$userResult = mysqli_query($conn, "SELECT SupplierID FROM user WHERE UserID = $userID");
$user = mysqli_fetch_assoc($userResult);
$supplierID = $user['SupplierID'] ?? null;

if (!$supplierID) {
    header("Location: dashboard.php?error=no_supplier");
    exit;
}

$supResult = mysqli_query($conn, "SELECT Name FROM supplier WHERE SupplierID = $supplierID");
$supplierName = 'Your Company';
if ($supResult && mysqli_num_rows($supResult) > 0) {
    $supplierRow = mysqli_fetch_assoc($supResult);
    $supplierName = $supplierRow['Name'];
}

$result1 = mysqli_query($conn, "SELECT * FROM plantcategory ORDER BY Name");
$categories = mysqli_fetch_all($result1, MYSQLI_ASSOC);
$result2 = mysqli_query($conn, "SELECT * FROM planttype ORDER BY Name");
$plantTypes = mysqli_fetch_all($result2, MYSQLI_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['Name']);
    $scientificName = mysqli_real_escape_string($conn, $_POST['ScientificName']);
    $categoryID = $_POST['PlantCategoryID'] ? $_POST['PlantCategoryID'] : 'NULL';
    $plantTypeID = $_POST['PlantTypeID'] ? $_POST['PlantTypeID'] : 'NULL';
    $quantity = $_POST['QuantityAvailable'] ?? 0;
    $price = $_POST['Price'] ?? 0;

    $imageData = null;
    if (isset($_FILES['PlantImg']) && $_FILES['PlantImg']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['PlantImg'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            $error = 'Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'File size too large. Maximum size is 5MB.';
        } else {
            $imageData = file_get_contents($file['tmp_name']);
        }
    } elseif (isset($_FILES['PlantImg']) && $_FILES['PlantImg']['error'] !== UPLOAD_ERR_NO_FILE) {
        $error = 'Error uploading image: ' . $_FILES['PlantImg']['error'];
    }

    if (empty($name) || empty($price)) {
        $error = 'Name and Price are required';
    } elseif (empty($error)) {
        if ($imageData !== null) {
            $stmt = mysqli_prepare($conn, "INSERT INTO plant (Name, ScientificName, PlantCategoryID, PlantTypeID, SupplierID, QuantityAvailable, Price, PlantImg) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $catID = ($categoryID === 'NULL') ? null : (int)$categoryID;
            $typeID = ($plantTypeID === 'NULL') ? null : (int)$plantTypeID;
            mysqli_stmt_bind_param($stmt, "ssiiidss", $name, $scientificName, $catID, $typeID, $supplierID, $quantity, $price, $imageData);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO plant (Name, ScientificName, PlantCategoryID, PlantTypeID, SupplierID, QuantityAvailable, Price) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $catID = ($categoryID === 'NULL') ? null : (int)$categoryID;
            $typeID = ($plantTypeID === 'NULL') ? null : (int)$plantTypeID;
            mysqli_stmt_bind_param($stmt, "ssiiidd", $name, $scientificName, $catID, $typeID, $supplierID, $quantity, $price);
        }

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: products.php?success=1");
            exit;
        } else {
            $error = 'Error: ' . mysqli_error($conn);
            mysqli_stmt_close($stmt);
        }
    }
}

$pageTitle = 'Add Product';
include 'includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Add New Product</h2>
    <a href="products.php" class="btn btn-secondary">Back to Products</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="Name" class="form-label">Name *</label>
                    <input type="text" class="form-control" id="Name" name="Name" required value="<?php echo htmlspecialchars($_POST['Name'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="ScientificName" class="form-label">Scientific Name</label>
                    <input type="text" class="form-control" id="ScientificName" name="ScientificName" value="<?php echo htmlspecialchars($_POST['ScientificName'] ?? ''); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="PlantCategoryID" class="form-label">Category</label>
                    <select class="form-select" id="PlantCategoryID" name="PlantCategoryID">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['PlantCategoryID']; ?>" <?php echo (isset($_POST['PlantCategoryID']) && $_POST['PlantCategoryID'] == $cat['PlantCategoryID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['Name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="PlantTypeID" class="form-label">Plant Type</label>
                    <select class="form-select" id="PlantTypeID" name="PlantTypeID">
                        <option value="">Select Plant Type</option>
                        <?php foreach ($plantTypes as $type): ?>
                            <option value="<?php echo $type['PlantTypeID']; ?>" <?php echo (isset($_POST['PlantTypeID']) && $_POST['PlantTypeID'] == $type['PlantTypeID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['Name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Supplier</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($supplierName ?? 'Your Company'); ?>" disabled>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="QuantityAvailable" class="form-label">Quantity Available</label>
                    <input type="number" class="form-control" id="QuantityAvailable" name="QuantityAvailable" min="0" value="<?php echo htmlspecialchars($_POST['QuantityAvailable'] ?? '0'); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="Price" class="form-label">Price *</label>
                    <input type="number" class="form-control" id="Price" name="Price" step="0.01" min="0" required value="<?php echo htmlspecialchars($_POST['Price'] ?? ''); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="PlantImg" class="form-label">Product Image</label>
                    <input type="file" class="form-control" id="PlantImg" name="PlantImg" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                    <small class="form-text text-muted">Upload an image (JPEG, PNG, GIF, or WebP). Max 5MB.</small>
                    <div id="imagePreview" class="mt-2" style="display: none;">
                        <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Add Product</button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('PlantImg').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('imagePreview').style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
