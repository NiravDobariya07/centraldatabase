<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Logs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }
        p {
            color: #555;
            line-height: 1.6;
        }
        a {
            display: inline-block;
            background-color: #007BFF;
            color: #ffffff !important;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        a:hover {
            background-color: #0056b3;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #aaa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Access Logs</h1>
        <p>You have received this email to access the logs.</p>
        <p>Your access link is valid for <strong>{{ $expirationMinutes ?? 'N/A' }}</strong> minutes only.</p>
        <p>Click the button below to view the logs:</p>
        <p>
            <a href="{{ $url ?? '#' }}">
                {{ $url ? 'View Logs' : 'Link Not Available' }}
            </a>
        </p>
        <p>Request initiated from IP address: <strong>{{ $request_ip ?? 'N/A' }}</strong></p>
    </div>
    <div class="footer">
        <p>This link will expire after <strong>{{ $expirationMinutes ?? 'N/A' }}</strong> minutes for security purposes.</p>
    </div>
</body>
</html>
