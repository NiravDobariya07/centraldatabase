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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center !important;
            width: 100%;
            box-sizing: border-box;
        }
        .logo-text {
            color: #ffffff;
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            text-align: center !important;
            display: inline-block;
        }
        .content {
            padding: 40px 30px;
        }
        h2 {
            color: #333333;
            margin-top: 0;
            font-size: 24px;
        }
        p {
            color: #666666;
            line-height: 1.6;
            margin: 15px 0;
        }
        .code {
            background-color: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 8px;
            margin: 30px 0;
            font-family: 'Courier New', monospace;
        }
        .expiry {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .expiry strong {
            color: #856404;
        }
        .warning {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 12px 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #721c24;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 0;
            font-size: 12px;
            color: #999999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header" style="text-align: center; padding: 30px;">
            <h1 class="logo-text" style="color: #ffffff; font-size: 28px; font-weight: bold; margin: 0; text-align: center; display: inline-block;">FLM CDB</h1>
        </div>

        <div class="content">
            <h2>Your 2FA Verification Code</h2>
            <p>Dear {{ $user_name }},</p>
            <p>We have received a request to verify your identity. Please use the following code to complete your authentication:</p>

            <div class="code">{{ $code }}</div>

            <div class="expiry">
                <p style="margin: 0;">This code will expire in <strong>{{ $expiry_time }} minutes</strong>.</p>
            </div>

            <div class="warning">
                <p style="margin: 0;">If you did not request this code, please ignore this email and ensure your account is secure.</p>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} FLM CDB. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
