<?php
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: plantpage.php");
    exit;
}

$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM plant WHERE PlantID = $id");

header("Location: plantpage.php?success=1");
exit;
?>

