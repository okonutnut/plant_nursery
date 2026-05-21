<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Check if user is logged in and is admin/employee
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'employee')) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: refundpage.php");
    exit;
}

$refundID = (int)$_GET['id'];
$action = mysqli_real_escape_string($conn, $_GET['action']);

if ($action !== 'approve' && $action !== 'reject') {
    header("Location: refundpage.php?error=Invalid action");
    exit;
}

// Get refund details
$result = mysqli_query($conn, "
    SELECT wr.*, o.OrderDate
    FROM warranty_refund wr
    LEFT JOIN `order` o ON wr.OrderID = o.OrderID
    WHERE wr.RefundID = $refundID
");
$refund = mysqli_fetch_assoc($result);

if (!$refund) {
    header("Location: refundpage.php?error=Refund request not found");
    exit;
}

if ($refund['Status'] !== 'Pending') {
    header("Location: refundpage.php?error=This refund request has already been processed");
    exit;
}

// Get employee ID from user
$userID = $_SESSION['user_id'];
$userResult = mysqli_query($conn, "SELECT EmployeeID FROM user WHERE UserID = $userID");
$user = mysqli_fetch_assoc($userResult);
$employeeID = $user['EmployeeID'] ?? null;

// Get notes if provided
$notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';

// Update refund status
$status = $action === 'approve' ? 'Approved' : 'Rejected';
$sql = "UPDATE warranty_refund 
        SET Status = '$status', 
            ProcessedBy = " . ($employeeID ? $employeeID : 'NULL') . ",
            ProcessedDate = NOW(),
            Notes = " . ($notes ? "'$notes'" : 'NULL') . "
        WHERE RefundID = $refundID";

if (mysqli_query($conn, $sql)) {
    // If approved, decrease plant inventory (replacement plants being sent)
    if ($action === 'approve') {
        // Get plant ID and quantity from order item
        $itemResult = mysqli_query($conn, "
            SELECT oi.PlantID, wr.Quantity, p.QuantityAvailable, p.Name as PlantName
            FROM warranty_refund wr
            LEFT JOIN orderitem oi ON wr.OrderItemID = oi.OrderItemID
            LEFT JOIN plant p ON oi.PlantID = p.PlantID
            WHERE wr.RefundID = $refundID
        ");
        $item = mysqli_fetch_assoc($itemResult);
        
        if ($item && $item['PlantID']) {
            $plantID = (int)$item['PlantID'];
            $refundQuantity = (int)$item['Quantity'];
            $currentQuantity = (int)$item['QuantityAvailable'];
            $plantName = $item['PlantName'];
            
            // Check if there's enough quantity available for replacement
            if ($currentQuantity < $refundQuantity) {
                // Rollback the refund approval
                mysqli_query($conn, "UPDATE warranty_refund SET Status = 'Pending', ProcessedBy = NULL, ProcessedDate = NULL, Notes = NULL WHERE RefundID = $refundID");
                header("Location: refundpage.php?error=Insufficient inventory for replacement. Only {$currentQuantity} units available for {$plantName}, but {$refundQuantity} units requested.");
                exit;
            }
            
            // Decrease plant quantity (replacement being sent)
            $updateResult = mysqli_query($conn, "
                UPDATE plant 
                SET QuantityAvailable = QuantityAvailable - $refundQuantity 
                WHERE PlantID = $plantID AND QuantityAvailable >= $refundQuantity
            ");
            
            if (!$updateResult || mysqli_affected_rows($conn) == 0) {
                // Rollback the refund approval if inventory update failed
                mysqli_query($conn, "UPDATE warranty_refund SET Status = 'Pending', ProcessedBy = NULL, ProcessedDate = NULL, Notes = NULL WHERE RefundID = $refundID");
                header("Location: refundpage.php?error=Failed to update plant inventory. Please check stock availability.");
                exit;
            }
        }
    }
    
    header("Location: refundpage.php?success=Refund request " . ($action === 'approve' ? 'approved and replacement processed' : 'rejected') . " successfully");
} else {
    header("Location: refundpage.php?error=Error processing refund request: " . mysqli_error($conn));
}
exit;

