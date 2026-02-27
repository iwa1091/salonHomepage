<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- ✅ CSRF（Inertia/axios/fetch のPOST/PUT/DELETE安定化） --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- ホーム画面追加用（PWA） --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#3c3228">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('img/icon-192x192.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Ohana">

    <title>@yield('title', config('app.name'))</title>

    {{-- Font Awesome --}}
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          crossorigin="anonymous" />

    {{-- 共通CSS（テーマ → グローバル → レイアウト） --}}
    @vite([
        'resources/css/base/theme.css',
        'resources/css/base/global.css',
        'resources/css/layout/header.css',
        'resources/css/layout/footer.css',
    ])

    {{-- ページ専用CSS --}}
    @yield('styles')

    {{-- Alpine.js（モバイルナビ用） --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[var(--background)] text-[var(--foreground)] antialiased" x-data="{ open: false }">

    @include('layout.header')

    <main>
        @yield('content')
    </main>

    @include('layout.footer')

    @yield('scripts')

</body>
</html>
