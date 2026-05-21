<?php
session_start();
require_once 'config/database.php';

$message = '';
$type = '';

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);

    $result = mysqli_query($conn, "SELECT UserID, Email, Username FROM user WHERE VerificationToken = '$token' AND EmailVerified = 0");
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        mysqli_query($conn, "UPDATE user SET EmailVerified = 1, VerificationToken = NULL WHERE UserID = " . $user['UserID']);

        if (mysqli_affected_rows($conn) > 0) {
            $message = 'Email verified successfully! Your account is now pending approval by an administrator. You will be notified once your account is approved.';
            $type = 'success';
        } else {
            $message = 'Could not verify your email. Please try again.';
            $type = 'danger';
        }
    } else {
        $message = 'Invalid or expired verification link. Your email may already be verified.';
        $type = 'danger';
    }
} else {
    $message = 'Missing verification token.';
    $type = 'danger';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Plant Nursery</title>
    <link href="assets/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: url('picture/login-bg.jpg') no-repeat center center fixed, linear-gradient(135deg, #667eea 0%, #8d7c9dff 100%);
            background-size: cover;
        }
        .verify-container {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
            text-align: center;
            margin: 1rem;
        }
        .verify-container h1 {
            color: #667eea;
            margin-bottom: 1.5rem;
        }
        .verify-container .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="icon">
            <?php if ($type === 'success'): ?>
                <i class="fas fa-check-circle" style="color: #28a745;"></i>
            <?php else: ?>
                <i class="fas fa-exclamation-circle" style="color: #dc3545;"></i>
            <?php endif; ?>
        </div>
        <h1>Email Verification</h1>
        <div class="alert alert-<?php echo $type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <a href="login.php" class="btn btn-primary">Go to Login</a>
    </div>
    <script src="assets/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>
