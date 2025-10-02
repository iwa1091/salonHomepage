@extends('layout.app')

@section('title', '施術事例・お客様の声')

@section('styles')
    <link href="{{ asset('css/gallery.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="container">
        {{-- Header --}}
        <div class="header-section">
            <h1 class="page-title">
                施術事例・お客様の声
            </h1>
            <p class="page-description">
                実際の施術事例とお客様からいただいた喜びの声をご紹介します。<br>
                あなたの理想の目元づくりの参考にしてください。
            </p>
        </div>

        {{-- Before/After Cases --}}
        <section class="section-case">
            <h2 class="section-title">
                ビフォー・アフター
            </h2>
            <div class="grid-case">
                @foreach($beforeAfterCases as $case)
                    <div class="card card-before-after">
                        <div class="card-content">
                            <div class="card-grid-images">
                                <div class="image-container">
                                    <img src="{{ asset('img/before.png') }}" alt="{{ $case->title }} - Before" class="image-card">
                                    <div class="badge badge-before">
                                        Before
                                    </div>
                                </div>
                                <div class="image-container">
                                    <img src="{{ asset('img/after.png') }}" alt="{{ $case->title }} - After" class="image-card">
                                    <div class="badge badge-after">
                                        After
                                    </div>
                                </div>
                            </div>
                            <div class="card-text-content">
                                <h3 class="card-title">{{ $case->title }}</h3>
                                <p class="card-description">{{ $case->description }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Gallery --}}
        <section class="section-gallery">
            <h2 class="section-title">
                施術事例ギャラリー
            </h2>
            <div class="grid-gallery">
                @foreach($galleryImages as $image)
                    <div class="card card-gallery">
                        <div class="card-content">
                            <div class="image-container">
                                <img src="{{ asset('img/brow-img.png') }}" alt="{{ $image->title }}" class="image-card">
                                <div class="badge badge-category {{ $image->category === 'まつげ' ? 'badge-lash' : 'badge-brow' }}">
                                    {{ $image->category }}
                                </div>
                            </div>
                            <div class="card-text-content">
                                <h3 class="card-title">{{ $image->title }}</h3>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Customer Reviews --}}
        <section class="section-reviews">
            <h2 class="section-title">
                お客様の声
            </h2>
            <div class="grid-reviews">
                @foreach($reviews as $review)
                    <div class="card card-review">
                        <div class="card-content">
                            <div class="review-header">
                                <div>
                                    <div class="review-name-age">
                                        <span class="review-name">{{ $review->name }}</span>
                                        <span class="review-age">({{ $review->age }})</span>
                                    </div>
                                    <div class="review-rating">
                                        @for ($i = 0; $i < $review->rating; $i++)
                                            <img src="{{ asset('img/star.png') }}" alt="Star rating" class="star">
                                        @endfor
                                    </div>
                                </div>
                                <img src="{{ asset('img/quote.png') }}" alt="Quote icon" class="quote">
                            </div>
                            <p class="review-comment">
                                {{ $review->comment }}
                            </p>
                            <div class="review-details">
                                <span class="review-service">{{ $review->service }}</span>
                                <span class="review-date">{{ $review->date }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Stats --}}
        <section class="section-stats">
            <div class="stats-container">
                <h2 class="section-title-white">
                    お客様満足度
                </h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value">98%</div>
                        <p class="stat-label">リピート率</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">1,200+</div>
                        <p class="stat-label">施術実績</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">4.9★</div>
                        <p class="stat-label">平均評価</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection