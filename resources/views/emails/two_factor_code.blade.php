<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Two-Factor Authentication Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            padding: 20px;
        }
        .container {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
        }
        h2 {
            color: #2c3e50;
        }
        p {
            font-size: 16px;
            color: #555;
        }
        .code {
            font-size: 24px;
            font-weight: bold;
            color: #e74c3c;
            padding: 10px;
            background-color: #f2f2f2;
            border-radius: 5px;
        }
        .footer {
            font-size: 12px;
            color: #aaa;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Your 2FA Verification Code</h2>
    <p>Dear User,</p>
    <p>We have received a request to verify your identity. Please use the following code to complete your authentication:</p>
    
    <div class="code">{{ $code }}</div>

    <p>This code will expire in <strong>{{ $expiry_time }} minutes</strong>.</p>
    
    <p>If you did not request this code, please ignore this email.</p>

    <div class="footer">
        <p>This is an automated message. Please do not reply.</p>
    </div>
</div>

</body>
</html>
