<header class="main-header">
<div class="header-container">
<div class="header-content">
{{-- ロゴのリンク先を 'home' から 'top' に変更 --}}
<a href="{{ route('top') }}" class="header-logo-link">
{{-- ロゴ画像はpublicディレクトリに配置 --}}
<img src="{{ asset('img/logo.jpg') }}" alt="lash-brow-ohana ロゴ" class="header-logo" />
<span class="site-title">
lash-brow-ohana
</span>
</a>

        {{-- デスクトップナビゲーション --}}
        <nav class="desktop-nav">
            {{-- ナビゲーションの「ホーム」リンク先を 'home' から 'top' に変更 --}}
            <a href="{{ route('top') }}" class="nav-link {{ request()->routeIs('top') ? 'is-active' : '' }}">
                ホーム
            </a>
            {{-- ここを修正 --}}
            <a href="{{ route('menu_price') }}" class="nav-link {{ request()->routeIs('menu_price') ? 'is-active' : '' }}">
                メニュー・料金
            </a>
            <a href="{{ route('gallery') }}" class="nav-link {{ request()->routeIs('gallery') ? 'is-active' : '' }}">
                施術事例・お客様の声
            </a>
            {{-- リンクをstoreからproductsに変更 --}}
            <a href="{{ route('online-store.index') }}" class="nav-link {{ request()->routeIs('online-store.index') ? 'is-active' : '' }}">
                商品販売
            </a>
            <a href="{{ route('contact.form') }}" class="nav-link {{ request()->routeIs('contact.form') ? 'is-active' : '' }}">
                ご予約・お問い合わせ
            </a>
        </nav>

        {{-- モバイルナビゲーション（JavaScriptで制御） --}}
        <div x-data="{ open: false }" class="mobile-menu-wrapper">
            <button @click="open = true" class="menu-toggle-button">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <div x-show="open" @click.away="open = false" class="mobile-menu-overlay">
                <div class="mobile-menu">
                    <div class="mobile-menu-header">
                        <button @click="open = false" class="menu-close-button">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="mobile-nav-list">
                        <div class="mobile-logo-wrapper">
                            <img src="{{ asset('images/logo.png') }}" alt="lash-brow-ohana ロゴ" class="mobile-logo" />
                            <span class="site-title-mobile">
                                lash-brow-ohana
                            </span>
                        </div>
                        {{-- モバイルナビゲーションの「ホーム」リンク先を 'home' から 'top' に変更 --}}
                        <a href="{{ route('top') }}" class="mobile-nav-link {{ request()->routeIs('top') ? 'is-active' : '' }}">ホーム</a>
                        {{-- ここを修正 --}}
                        <a href="{{ route('menu_price') }}" class="mobile-nav-link {{ request()->routeIs('menu_price') ? 'is-active' : '' }}">メニュー・料金</a>
                        <a href="{{ route('gallery') }}" class="mobile-nav-link {{ request()->routeIs('gallery') ? 'is-active' : '' }}">施術事例・お客様の声</a>
                        <a href="{{ route('online-store.index') }}" class="mobile-nav-link {{ request()->routeIs('online-store.index') ? 'is-active' : '' }}">商品販売</a>
                        <a href="{{ route('contact.form') }}" class="mobile-nav-link {{ request()->routeIs('contact.form') ? 'is-active' : '' }}">ご予約・お問い合わせ</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</header>