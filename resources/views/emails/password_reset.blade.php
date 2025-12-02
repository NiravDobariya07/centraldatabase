<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%);
            padding: 40px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 2px;
        }
        .content {
            padding: 40px 30px;
        }
        h2 {
            color: #1f2937;
            margin-top: 0;
            font-size: 24px;
            font-weight: 600;
        }
        p {
            color: #6b7280;
            line-height: 1.6;
            margin: 15px 0;
            font-size: 15px;
        }
        .reset-button {
            text-align: center;
            margin: 35px 0;
        }
        .reset-button a {
            display: inline-block;
            background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 15px 40px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s;
        }
        .reset-button a:hover {
            transform: translateY(-2px);
        }
        .reset-link {
            background-color: #f9fafb;
            border: 2px dashed #7c3aed;
            border-radius: 6px;
            padding: 15px;
            margin: 25px 0;
            word-break: break-all;
            font-size: 13px;
            color: #6b7280;
        }
        .expiry {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .expiry p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
        }
        .warning {
            background-color: #fce7f3;
            border-left: 4px solid #ec4899;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .warning p {
            margin: 0;
            color: #831843;
            font-size: 14px;
        }
        .footer {
            background-color: #f9fafb;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 5px 0;
            font-size: 13px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>FLM CDB</h1>
        </div>

        <div class="content">
            <h2>Reset Your Password</h2>
            <p>Dear {{ $name }},</p>
            <p>We received a request to reset your password for your FLM CDB account. Click the button below to create a new password:</p>

            <div class="reset-button">
                <a href="{{ $reset_url }}">Reset Password</a>
            </div>

            <p>Or copy and paste this link into your browser:</p>
            <div class="reset-link">
                {{ $reset_url }}
            </div>

            <div class="expiry">
                <p>This password reset link will expire in <strong>{{ $expiry_time }} minutes</strong>.</p>
            </div>

            <div class="warning">
                <p>If you did not request this password reset, please ignore this email and ensure your account is secure. Your password will remain unchanged.</p>
            </div>

            <p>For security reasons, we recommend:</p>
            <ul style="color: #6b7280; line-height: 1.8;">
                <li>Using a strong, unique password</li>
                <li>Not sharing your password with anyone</li>
                <li>Enabling two-factor authentication if available</li>
            </ul>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} FLM CDB. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

