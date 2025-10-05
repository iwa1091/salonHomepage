@extends('layout.app')

@section('title', 'メニュー・料金')

@section('content')
<div class="menu-container">

    @php
        // カテゴリごとに分割
        $eyelashServices = $services->where('category', 'まつげエクステンション');
        $eyebrowServices = $services->where('category', '眉メニュー');
        $setServices     = $services->where('category', 'お得なセットメニュー');
    @endphp

    {{-- まつげエクステンション --}}
    <section class="menu-section">
        <h2 class="section-title">まつげエクステンション</h2>
        <div class="menu-grid">
            @forelse ($eyelashServices as $service)
                <div class="menu-card @if($service->is_popular) menu-card-popular @endif">
                    @if($service->is_popular)
                        <span class="popular-badge">人気No.1</span>
                    @endif

                    @if($service->image)
                        <div class="card-image">
                            <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->name }}">
                        </div>
                    @endif

                    <div class="card-header">
                        <h3 class="card-title">{{ $service->name }}</h3>
                        <p class="card-description">{{ $service->description }}</p>
                    </div>

                    <div class="card-content">
                        <div class="card-price-info">
                            <span class="card-price">¥{{ number_format($service->price) }}</span>
                            <div class="card-duration">
                                <span class="duration-text">{{ $service->duration_minutes }}分</span>
                            </div>
                        </div>

                        @if($service->features)
                            <ul class="feature-list">
                                @foreach(json_decode($service->features, true) as $feature)
                                    <li class="feature-item">{{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif

                        {{-- 予約フォームに service_id を渡す --}}
                        <a href="{{ route('reservation.form', ['service_id' => $service->id]) }}" class="button btn-reserve btn-primary">予約する</a>
                    </div>
                </div>
            @empty
                <p class="no-service">現在、登録されているサービスはありません。</p>
            @endforelse
        </div>
    </section>

    {{-- 眉メニュー --}}
    <section class="menu-section">
        <h2 class="section-title">眉メニュー</h2>
        <div class="menu-grid">
            @forelse ($eyebrowServices as $service)
                <div class="menu-card @if($service->is_popular) menu-card-popular @endif">
                    @if($service->is_popular)
                        <span class="popular-badge">人気No.1</span>
                    @endif

                    @if($service->image)
                        <div class="card-image">
                            <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->name }}">
                        </div>
                    @endif

                    <div class="card-header">
                        <h3 class="card-title">{{ $service->name }}</h3>
                        <p class="card-description">{{ $service->description }}</p>
                    </div>

                    <div class="card-content">
                        <div class="card-price-info">
                            <span class="card-price">¥{{ number_format($service->price) }}</span>
                            <div class="card-duration">
                                <span class="duration-text">{{ $service->duration_minutes }}分</span>
                            </div>
                        </div>

                        @if($service->features)
                            <ul class="feature-list">
                                @foreach(json_decode($service->features, true) as $feature)
                                    <li class="feature-item">{{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <a href="{{ route('reservation.form', ['service_id' => $service->id]) }}" class="button btn-reserve btn-primary">予約する</a>
                    </div>
                </div>
            @empty
                <p class="no-service">現在、登録されているサービスはありません。</p>
            @endforelse
        </div>
    </section>

    {{-- お得なセットメニュー --}}
    <section class="menu-section">
        <h2 class="section-title">お得なセットメニュー</h2>
        <div class="menu-grid">
            @forelse ($setServices as $service)
                <div class="menu-card @if($service->is_popular) menu-card-popular @endif">
                    @if($service->is_popular)
                        <span class="popular-badge">人気No.1</span>
                    @endif

                    @if($service->image)
                        <div class="card-image">
                            <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->name }}">
                        </div>
                    @endif

                    <div class="card-header">
                        <h3 class="card-title">{{ $service->name }}</h3>
                        <p class="card-description">{{ $service->description }}</p>
                    </div>

                    <div class="card-content">
                        <div class="card-price-info">
                            <span class="card-price">¥{{ number_format($service->price) }}</span>
                            <div class="card-duration">
                                <span class="duration-text">{{ $service->duration_minutes }}分</span>
                            </div>
                        </div>

                        @if($service->features)
                            <ul class="feature-list">
                                @foreach(json_decode($service->features, true) as $feature)
                                    <li class="feature-item">{{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <a href="{{ route('reservation.form', ['service_id' => $service->id]) }}" class="button btn-reserve btn-primary">予約する</a>
                    </div>
                </div>
            @empty
                <p class="no-service">現在、登録されているサービスはありません。</p>
            @endforelse
        </div>
    </section>

</div>
@endsection
