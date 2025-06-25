<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - SalProjects</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #23272b;
            color: #fff;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 480px;
            margin: 0 auto;
            background: #2b2b2b;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background: #1a73e8;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            color: #fff;
            margin: 0;
            font-size: 1.5rem;
        }
        .body {
            padding: 32px 24px;
        }
        .btn {
            display: inline-block;
            background: #1a73e8;
            color: #fff;
            padding: 12px 32px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .footer {
            background: #23272b;
            color: #b0b0b0;
            text-align: center;
            padding: 16px;
            font-size: 0.9rem;
        }
        .url {
            word-break: break-all;
            color: #1a73e8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SalProjects</h1>
        </div>
        <div class="body">
            <h2>Hello from SalProjects!</h2>
            <p>You are receiving this email because we received a password reset request for your account.</p>
            <p style="text-align: center;">
                <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
            </p>
            <p>If you did not request a password reset, no further action is required.</p>
            <p style="margin-top: 32px;">Regards,<br>System Administrator, SalProjects</p>
            <div style="margin-top: 32px; font-size: 0.9rem; color: #b0b0b0;">
                If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:<br>
                <span class="url">{{ $resetUrl }}</span>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} SalProjects. All rights reserved.
        </div>
    </div>
</body>
</html>
