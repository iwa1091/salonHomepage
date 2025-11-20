<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'Lash Brow Ohana') }}</title>

    {{-- Google Fonts（必要なら） --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- Inertia.js route helper --}}
    @routes

    {{-- ============================================
         Vite - 全ページ共通CSS & JS を読み込む
       ============================================ --}}
    @vite([
        'resources/css/base/global.css',
        'resources/css/base/theme.css',
        'resources/css/layout/header.css',
        'resources/css/layout/footer.css',
        'resources/js/app.jsx',
    ])

    @inertiaHead
</head>
<body class="font-sans antialiased">

    @inertia

</body>
</html>
