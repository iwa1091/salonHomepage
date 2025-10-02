@extends('layout.app')

@section('title', 'メニュー・料金')

@section('styles')
    <link href="{{ asset('css/menu_price.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="menu-page">
        <div class="menu-container">
            {{-- Header --}}
            <div class="menu-header">
                <h1 class="menu-title">メニュー・料金</h1>
                <p class="menu-subtitle">
                    お客様のご希望に合わせて、様々なメニューをご用意しております。<br>
                    すべて丁寧なカウンセリング付きです。
                </p>
            </div>

            {{-- Eyelash Extension Section --}}
            <section class="menu-section">
                <h2 class="section-title">まつげエクステンション</h2>
                <div class="menu-grid">
                    @foreach ($eyelashMenus as $menu)
                        <div class="menu-card @if($menu->is_popular) menu-card-popular @endif">
                            @if($menu->is_popular)
                                <span class="popular-badge">人気No.1</span>
                            @endif
                            <div class="card-header">
                                <h3 class="card-title">{{ $menu->name }}</h3>
                                <p class="card-description">{{ $menu->description }}</p>
                            </div>
                            <div class="card-content">
                                <div class="card-price-info">
                                    <span class="card-price">¥{{ number_format($menu->price) }}</span>
                                    <div class="card-duration">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <span class="duration-text">{{ $menu->duration }}分</span>
                                    </div>
                                </div>
                                @if($menu->features)
                                    <ul class="feature-list">
                                        @foreach(json_decode($menu->features) as $feature)
                                            <li class="feature-item">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sparkles" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M21 21L12 12M12 12L3 3"></path>
                                                    <path d="M3 21L12 12M12 12L21 3"></path>
                                                    <path d="M16 16L12 12M12 12L8 8"></path>
                                                    <path d="M8 16L12 12M12 12L16 8"></path>
                                                </svg>
                                                <span>{{ $feature }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <a href="#" class="button btn-reserve btn-primary">予約する</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Eyebrow Section --}}
            <section class="menu-section">
                <h2 class="section-title">眉メニュー</h2>
                <div class="menu-grid">
                    @foreach ($eyebrowMenus as $menu)
                        <div class="menu-card @if($menu->is_popular) menu-card-popular @endif">
                            @if($menu->is_popular)
                                <span class="popular-badge">人気No.1</span>
                            @endif
                            <div class="card-header">
                                <h3 class="card-title">{{ $menu->name }}</h3>
                                <p class="card-description">{{ $menu->description }}</p>
                            </div>
                            <div class="card-content">
                                <div class="card-price-info">
                                    <span class="card-price">¥{{ number_format($menu->price) }}</span>
                                    <div class="card-duration">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <span class="duration-text">{{ $menu->duration }}分</span>
                                    </div>
                                </div>
                                @if($menu->features)
                                    <ul class="feature-list">
                                        @foreach(json_decode($menu->features) as $feature)
                                            <li class="feature-item">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sparkles" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M21 21L12 12M12 12L3 3"></path>
                                                    <path d="M3 21L12 12M12 12L21 3"></path>
                                                    <path d="M16 16L12 12M12 12L8 8"></path>
                                                    <path d="M8 16L12 12M12 12L16 8"></path>
                                                </svg>
                                                <span>{{ $feature }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <a href="#" class="button btn-reserve btn-primary">予約する</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Set Menu Section --}}
            <section class="menu-section">
                <h2 class="section-title">お得なセットメニュー</h2>
                <div class="set-menu-grid">
                    @foreach ($setMenus as $menu)
                        <div class="menu-card @if($menu->is_popular) menu-card-popular @endif">
                            @if($menu->is_popular)
                                <span class="popular-badge">人気No.1</span>
                            @endif
                            <div class="card-header">
                                <h3 class="card-title">{{ $menu->name }}</h3>
                                <p class="card-description">{{ $menu->description }}</p>
                            </div>
                            <div class="card-content">
                                <div class="card-price-info">
                                    <span class="card-price">¥{{ number_format($menu->price) }}</span>
                                    <div class="card-duration">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <span class="duration-text">{{ $menu->duration }}分</span>
                                    </div>
                                </div>
                                @if($menu->features)
                                    <ul class="feature-list">
                                        @foreach(json_decode($menu->features) as $feature)
                                            <li class="feature-item">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sparkles" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M21 21L12 12M12 12L3 3"></path>
                                                    <path d="M3 21L12 12M12 12L21 3"></path>
                                                    <path d="M16 16L12 12M12 12L8 8"></path>
                                                    <path d="M8 16L12 12M12 12L16 8"></path>
                                                </svg>
                                                <span>{{ $feature }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <a href="#" class="button btn-reserve btn-primary">予約する</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Notes Section --}}
            <section class="notes-section">
                <h3 class="notes-title">ご注意事項</h3>
                <div class="notes-grid">
                    <div class="note-item">
                        <p>• 初回ご利用の方は、カウンセリング時間を含めて+30分程度お時間をいただきます</p>
                        <p>• アレルギーやお肌の敏感な方は、事前にパッチテストをおすすめします</p>
                    </div>
                    <div class="note-item">
                        <p>• 妊娠中・授乳中の方は、事前にご相談ください</p>
                        <p>• キャンセルは前日までにご連絡をお願いいたします</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection