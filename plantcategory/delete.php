<?php
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: categorypage.php");
    exit;
}

$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM plantcategory WHERE PlantCategoryID = $id");

header("Location: categorypage.php?success=1");
exit;
?>

