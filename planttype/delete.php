<?php
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: typepage.php");
    exit;
}

$id = (int)$_GET['id'];

// Check if this plant type is being used by any plants
$checkResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM plant WHERE PlantTypeID = $id");
$check = mysqli_fetch_assoc($checkResult);

if ($check['count'] > 0) {
    header("Location: typepage.php?error=This plant type cannot be deleted because it is being used by " . $check['count'] . " plant(s).");
    exit;
}

$sql = "DELETE FROM planttype WHERE PlantTypeID = $id";
mysqli_query($conn, $sql);

header("Location: typepage.php?success=1");
exit;

