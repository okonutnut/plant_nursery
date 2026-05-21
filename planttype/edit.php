<?php
require_once '../config/database.php';

$error = '';

if (!isset($_GET['id'])) {
    header("Location: typepage.php");
    exit;
}

$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['Name']);
    $description = mysqli_real_escape_string($conn, $_POST['Description']);
    
    $sql = "UPDATE planttype SET Name = '$name', Description = '$description' WHERE PlantTypeID = $id";
    mysqli_query($conn, $sql);
    
    header("Location: typepage.php?success=1");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM planttype WHERE PlantTypeID = $id");
$type = mysqli_fetch_assoc($result);

if (!$type) {
    header("Location: typepage.php");
    exit;
}

$pageTitle = 'Edit Plant Type';
include '../includes/header.php';
?>

<div class="page-header mb-4">
    <h2>Edit Plant Type (Propagation Method)</h2>
    <a href="typepage.php" class="btn btn-secondary">Back to List</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="Name" class="form-label">Name *</label>
                <input type="text" class="form-control" id="Name" name="Name" required value="<?php echo htmlspecialchars($type['Name']); ?>" placeholder="e.g., Seed, Marcot, Grafting">
                <small class="form-text text-muted">Enter the propagation method name</small>
            </div>
            
            <div class="mb-3">
                <label for="Description" class="form-label">Description</label>
                <textarea class="form-control" id="Description" name="Description" rows="5" placeholder="Describe the propagation method, its benefits, and when to use it..."><?php echo htmlspecialchars($type['Description'] ?? ''); ?></textarea>
                <small class="form-text text-muted">Provide detailed information about this propagation method</small>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Plant Type</button>
                <a href="typepage.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

