<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#081B6A">
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    @inertiaHead
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="bg-[#F6F6F6] text-[#081B6A]">
    @inertia
</body>
</html>
