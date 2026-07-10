<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Loan Management') }}</title>
</head>
<body class="antialiased">
    <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; flex-direction: column;">
        <h1>{{ config('app.name', 'Loan Management') }}</h1>
        <p>API Server</p>
    </div>
</body>
</html>
