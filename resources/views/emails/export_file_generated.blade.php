<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export File Ready for Download</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            padding: 20px;
        }
        .container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }
        h2 {
            color: #2c3e50;
        }
        .details {
            font-size: 16px;
            margin: 15px 0;
            text-align: left;
        }
        .file-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: left;
        }
        .highlight {
            font-weight: bold;
            color: #e74c3c;
        }
        .button {
            display: inline-block;
            padding: 12px 20px;
            margin: 20px 0;
            font-size: 16px;
            color: #fff !important;
            background-color: #3498db;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }
        .button:hover {
            background-color: #2980b9;
        }
        .footer {
            font-size: 12px;
            color: #777;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Your Export File is Ready! 📁</h2>

    <p>Dear <strong>{{ $exportFile->user->name ?? 'User' }}</strong>,</p>

    <p>Your requested export file has been successfully generated and is ready for download.</p>

    <div class="file-info">
        <p><strong>Export Title:</strong> {{ $exportFile->export->title ?? 'No Title Provided' }}</p>
        <p><strong>Description:</strong> {{ $exportFile->export->description ?: 'No description available' }}</p>
        <p><strong>File Name:</strong> {{ $exportFile->file_name ?? 'Unknown File' }}</p>
        <p><strong>Format:</strong> {{ strtoupper($exportFile->file_format ?? 'N/A') }}</p>
        <p><strong>File Size:</strong> {{ $exportFile->file_size_mb ?? '0' }} MB ({{ $exportFile->file_size_kb ?? '0' }} KB)</p>
        <p><strong>Generated On:</strong> {{ optional($exportFile->generated_at)->format('Y-m-d H:i:s') ?? 'Unknown Date' }}</p>
        <p><strong>Expires On:</strong> {{ optional($exportFile->expires_at)->format('Y-m-d H:i:s') ?? 'No Expiry' }}</p>
    </div>

    <p>To download your file, please log into your account.</p>

    <a href="{{ route('leads.export.exports-files-listing') }}" class="button">Download File</a>

    <p>If you did not request this file, please contact our support team immediately.</p>

    <div class="footer">
        <p>Thank you for using our services! 🚀</p>
        <p><em>This is an automated email. Please do not reply.</em></p>
    </div>
</div>

</body>
</html>
