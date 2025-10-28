<?php
/**
 * Account Activation Page
 * Handles user account activation from email links
 */

session_start();
require_once __DIR__ . '/globalConfig.php';
require_once __DIR__ . '/api/include/Core/ActivateHandler.php';

use Core\ActivateHandler;

// Get activation token from URL
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

$success = false;
$error = '';
$message = '';

// Process activation if token provided
if (!empty($token) && !empty($email)) {
    $handler = new ActivateHandler();
    $result = $handler->activate($email, $token);
    
    if ($result['success']) {
        $success = true;
        $message = $result['message'] ?? 'Your account has been successfully activated!';
    } else {
        $error = $result['error'] ?? 'Invalid or expired activation link.';
    }
} elseif (!empty($token) || !empty($email)) {
    $error = 'Invalid activation link. Both email and token are required.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activation - Travian</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        
        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        
        .icon.success {
            background: #10b981;
            color: white;
        }
        
        .icon.error {
            background: #ef4444;
            color: white;
        }
        
        .icon.pending {
            background: #f59e0b;
            color: white;
        }
        
        h1 {
            color: #1f2937;
            margin-bottom: 16px;
            font-size: 28px;
        }
        
        p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 24px;
            font-size: 16px;
        }
        
        .message {
            background: #ecfdf5;
            border: 1px solid #10b981;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            color: #065f46;
        }
        
        .error-message {
            background: #fef2f2;
            border: 1px solid #ef4444;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            color: #991b1b;
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            margin: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6b7280;
        }
        
        .btn-secondary:hover {
            box-shadow: 0 8px 16px rgba(107, 114, 128, 0.4);
        }
        
        .info-box {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 20px;
            margin-top: 24px;
            text-align: left;
        }
        
        .info-box h3 {
            color: #1f2937;
            margin-bottom: 12px;
            font-size: 18px;
        }
        
        .info-box ul {
            list-style: none;
            padding-left: 0;
        }
        
        .info-box li {
            padding: 8px 0;
            color: #4b5563;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-box li:last-child {
            border-bottom: none;
        }
        
        .info-box li strong {
            color: #1f2937;
            display: inline-block;
            min-width: 120px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <!-- Success State -->
            <div class="icon success">✓</div>
            <h1>Account Activated!</h1>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <p>Your account is now active. You can log in and start playing!</p>
            <div>
                <a href="/" class="btn">Go to Login</a>
                <a href="/game" class="btn btn-secondary">Start Playing</a>
            </div>
            
        <?php elseif (!empty($error)): ?>
            <!-- Error State -->
            <div class="icon error">✕</div>
            <h1>Activation Failed</h1>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <p>Please check your activation link or request a new one.</p>
            <div>
                <a href="/" class="btn">Go to Login</a>
                <a href="/register" class="btn btn-secondary">Register Again</a>
            </div>
            
        <?php else: ?>
            <!-- Pending State -->
            <div class="icon pending">?</div>
            <h1>Account Activation</h1>
            <p>To activate your account, please click the activation link sent to your email.</p>
            
            <div class="info-box">
                <h3>Didn't receive the email?</h3>
                <ul>
                    <li>✓ Check your spam/junk folder</li>
                    <li>✓ Wait a few minutes for delivery</li>
                    <li>✓ Make sure you entered the correct email</li>
                    <li>✓ Request a new activation email</li>
                </ul>
            </div>
            
            <div style="margin-top: 24px;">
                <a href="/" class="btn">Back to Home</a>
            </div>
        <?php endif; ?>
        
        <!-- Debug Info (Development Only) -->
        <?php if (getenv('APP_DEBUG') === 'true'): ?>
            <div class="info-box" style="margin-top: 24px; border: 2px dashed #f59e0b;">
                <h3>Debug Information</h3>
                <ul>
                    <li><strong>Token:</strong> <?php echo !empty($token) ? substr($token, 0, 20) . '...' : 'Not provided'; ?></li>
                    <li><strong>Email:</strong> <?php echo htmlspecialchars($email ?: 'Not provided'); ?></li>
                    <li><strong>IP Address:</strong> <?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; ?></li>
                    <li><strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
