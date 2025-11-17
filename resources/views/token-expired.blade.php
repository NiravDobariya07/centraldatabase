<!-- resources/views/token-expired.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Expired</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"> <!-- Add your CSS file -->
</head>
<body>
    <div style="text-align: center; padding: 50px;">
        <h1>Link Expired</h1>
        <p>The link you are trying to access is either invalid or has expired. Please request a new access link.</p>
        <a href="{{ url('/') }}">Go back to homepage</a>
    </div>
</body>
</html>
