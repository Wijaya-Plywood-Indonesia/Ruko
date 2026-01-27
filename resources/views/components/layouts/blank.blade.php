<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Custom Full Page</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{ $slot }}
</body>
</html>