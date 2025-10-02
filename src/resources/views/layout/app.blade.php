<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>lash-brow-ohana | @yield('title')</title>

    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Shippori+Mincho:wght@400;500;600;700&family=Zen+Maru+Gothic:wght@400;500;700&display=swap" rel="stylesheet">
    
    @yield('styles')
</head>
<body>
    @include('layout.header')

    <main>
        @yield('content')
    </main>
    
    @include('layout.footer')

    @yield('scripts')
</body>
</html>