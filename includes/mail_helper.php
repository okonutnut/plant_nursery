<?php

function sendVerificationEmail($to, $username, $token)
{
    $apiKey = 're_g9kX831M_Akpi3wFpCkpveUxAq7WyEwEP';

    $verifyLink = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/verify_email.php?token=' . urlencode($token);

    $subject = 'Verify your email - Plant Nursery';
    $html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea, #8d7c9d); padding: 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; }
        .header i { font-size: 40px; display: block; margin-bottom: 10px; }
        .body { padding: 30px; color: #333; }
        .body h2 { color: #667eea; }
        .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea, #8d7c9d); color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; color: #999; font-size: 12px; border-top: 1px solid #eee; }
        .note { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 15px 0; color: #856404; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i>🌱</i>
            <h1>Plant Nursery Management</h1>
        </div>
        <div class="body">
            <h2>Welcome, ' . htmlspecialchars($username) . '!</h2>
            <p>Thank you for registering. Please verify your email address by clicking the button below:</p>
            <p style="text-align: center;">
                <a href="' . $verifyLink . '" class="btn">Verify Email Address</a>
            </p>
            <p>Or copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #667eea; font-size: 13px;">' . $verifyLink . '</p>
            <div class="note">
                After verifying your email, an administrator will review and approve your account. You will receive access once your account is approved.
            </div>
            <p>If you did not create this account, please ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' Plant Nursery Management System</p>
        </div>
    </div>
</body>
</html>';

    $data = [
        'from' => 'onboarding@resend.dev',
        'to' => $to,
        'subject' => $subject,
        'html' => $html
    ];

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}
