<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Report</title>
</head>
<body>
    <h1>Error Report</h1>
    
    <p><strong>Error Message:</strong> {{ $errorDetails['message'] ?? 'No message available' }}</p>
    <p><strong>File:</strong> {{ $errorDetails['file'] ?? 'No file information available' }}</p>
    <p><strong>Line:</strong> {{ $errorDetails['line'] ?? 'No line information available' }}</p>
    
    <p><strong>Stack Trace:</strong></p>
    <pre>{{ $errorDetails['trace'] ?? 'No trace available' }}</pre>
</body>
</html>
