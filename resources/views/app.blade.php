<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#7C3AED">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="Coin">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

        <title inertia>{{ config('app.name', 'Coin') }}</title>

        <!-- Favicon -->
        <link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml">
        <link rel="alternate icon" href="/favicon.ico?v=2">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
