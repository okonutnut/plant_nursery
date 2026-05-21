<?php
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['Name']);
    $description = mysqli_real_escape_string($conn, $_POST['Description']);
    
    if (empty($name)) {
        $error = 'Name is required';
    } else {
        $sql = "INSERT INTO plantcategory (Name, Description) VALUES ('$name', '$description')";
        if (mysqli_query($conn, $sql)) {
            header("Location: categorypage.php?success=1");
            exit;
        } else {
            $error = 'Error: ' . mysqli_error($conn);
        }
    }
}

$pageTitle = 'Create Plant Category';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Create Plant Category</h2>
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
                <input type="text" class="form-control" id="Name" name="Name" required value="<?php echo htmlspecialchars($_POST['Name'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="Description" class="form-label">Description</label>
                <textarea class="form-control" id="Description" name="Description" rows="4"><?php echo htmlspecialchars($_POST['Description'] ?? ''); ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Category</button>
                <a href="categorypage.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

