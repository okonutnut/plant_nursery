<?php
require_once '../config/database.php';

$error = '';
$category = null;

if (!isset($_GET['id'])) {
    header("Location: categorypage.php");
    exit;
}

$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['Name']);
    $description = mysqli_real_escape_string($conn, $_POST['Description']);
    
    if (empty($name)) {
        $error = 'Name is required';
    } else {
        $sql = "UPDATE plantcategory SET Name = '$name', Description = '$description' WHERE PlantCategoryID = $id";
        mysqli_query($conn, $sql);
        header("Location: categorypage.php?success=1");
        exit;
    }
}

$result = mysqli_query($conn, "SELECT * FROM plantcategory WHERE PlantCategoryID = $id");
$category = mysqli_fetch_assoc($result);

if (!$category) {
    header("Location: categorypage.php");
    exit;
}

$pageTitle = 'Edit Plant Category';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Edit Plant Category</h2>
    <a href="categorypage.php" class="btn btn-secondary">Back to List</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="Name" class="form-label">Name *</label>
                <input type="text" class="form-control" id="Name" name="Name" required value="<?php echo htmlspecialchars($category['Name']); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Description" class="form-label">Description</label>
                <textarea class="form-control" id="Description" name="Description" rows="4"><?php echo htmlspecialchars($category['Description'] ?? ''); ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Category</button>
                <a href="categorypage.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

