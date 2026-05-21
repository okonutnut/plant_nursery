<?php
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: employeepage.php");
    exit;
}

$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM employee WHERE EmployeeID = $id");

header("Location: employeepage.php?success=1");
exit;
?>

