<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    {{-- ページタイトル --}}
    <title inertia>{{ config('app.name') }}</title>

    {{-- Ziggy（Reactで route() を使うために必須） --}}
    @routes

    {{-- Vite（React + app.css のみ） --}}
    @viteReactRefresh
    @vite([
        'resources/js/app.jsx',
        'resources/css/app.css',
    ])

    {{-- Inertia Head --}}
    @inertiaHead
</head>

<body class="bg-[var(--background)] text-[var(--foreground)]">
    @inertia
</body>
</html>
