{{-- Alpine.js で open を管理するルート --}}
<div x-data="{ open: false }">

<header class="main-header">
    <div class="header-container">
        <div class="header-content">

            {{-- ロゴ --}}
            <a href="{{ route('top') }}" class="header-logo-link">
                <img src="{{ asset('img/logo.jpg') }}" alt="lash-brow-ohana ロゴ" class="header-logo">
                <span class="site-title">lash-brow-ohana</span>
            </a>

            {{-- デスクトップナビ --}}
            <nav class="desktop-nav">
                <a href="{{ route('top') }}" class="nav-link {{ request()->routeIs('top') ? 'is-active' : '' }}">ホーム</a>
                <a href="{{ route('menu_price') }}" class="nav-link {{ request()->routeIs('menu_price') ? 'is-active' : '' }}">メニュー・料金</a>
                <a href="{{ route('gallery') }}" class="nav-link {{ request()->routeIs('gallery') ? 'is-active' : '' }}">施術事例・お客様の声</a>
                <a href="{{ route('online-store.index') }}" class="nav-link {{ request()->routeIs('online-store.index') ? 'is-active' : '' }}">商品販売</a>
                <a href="{{ route('contact.form') }}" class="nav-link {{ request()->routeIs('contact.form') ? 'is-active' : '' }}">ご予約・お問い合わせ</a>
                <a href="{{ route('mypage.index') }}">マイページ</a>
            </nav>

            {{-- モバイルメニューボタン --}}
            <button @click="open = true" class="menu-toggle-button mobile-only">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

        </div>
    </div>
</header>


{{-- ================================
      モバイルメニュー Overlay（header外）
=================================== --}}
<div
    x-show="open"
    x-transition.opacity
    @click.self="open = false"
    class="mobile-menu-overlay"
    style="display: none;"
>

    {{-- Drawer --}}
    <div
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="mobile-menu"
    >

        <div class="mobile-menu-header">
            <button @click="open = false" class="menu-close-button">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mobile-nav-list">
            <div class="mobile-logo-wrapper">
                <img src="{{ asset('img/logo.jpg') }}" alt="logo" class="mobile-logo">
                <span class="site-title-mobile">lash-brow-ohana</span>
            </div>

            <a href="{{ route('top') }}" class="mobile-nav-link {{ request()->routeIs('top') ? 'is-active' : '' }}">ホーム</a>
            <a href="{{ route('menu_price') }}" class="mobile-nav-link {{ request()->routeIs('menu_price') ? 'is-active' : '' }}">メニュー・料金</a>
            <a href="{{ route('gallery') }}" class="mobile-nav-link {{ request()->routeIs('gallery') ? 'is-active' : '' }}">施術事例・お客様の声</a>
            <a href="{{ route('online-store.index') }}" class="mobile-nav-link {{ request()->routeIs('online-store.index') ? 'is-active' : '' }}">商品販売</a>
            <a href="{{ route('contact.form') }}" class="mobile-nav-link {{ request()->routeIs('contact.form') ? 'is-active' : '' }}">ご予約・お問い合わせ</a>
        </div>

    </div>
</div>

</div> {{-- Alpine root --}}
