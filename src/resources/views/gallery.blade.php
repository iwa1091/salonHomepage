@extends('layout.app')

@section('title', '施術事例・お客様の声')

{{-- ページ専用CSS --}}
@section('styles')
    @vite(['resources/css/pages/gallery/gallery.css'])
@endsection

@section('content')
<div class="gallery-page-container">

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

    {{-- Before / After --}}
    <section class="mb-20">
        <h2 class="page-title text-center mb-8">
            ビフォー・アフター
        </h2>

        <div class="grid-case">
            @foreach($beforeAfterCases as $case)
                <div class="card">
                    <div class="card-grid-images">
                        <div class="image-container">
                            <img src="{{ asset('img/before.png') }}"
                                 alt="{{ $case->title }} - Before"
                                 class="image-card">
                            <div class="badge badge-before">Before</div>
                        </div>

                        <div class="image-container">
                            <img src="{{ asset('img/after.png') }}"
                                 alt="{{ $case->title }} - After"
                                 class="image-card">
                            <div class="badge badge-after">After</div>
                        </div>
                    </div>

                    <div class="card-content">
                        <h3 class="card-title">{{ $case->title }}</h3>
                        <p class="card-description">{{ $case->description }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Gallery --}}
    <section class="mb-20">
        <h2 class="page-title text-center mb-8">
            施術事例ギャラリー
        </h2>

        <div class="grid-gallery">
            @foreach($galleryImages as $image)
                <div class="card image-wrapper">
                    <div class="relative">
                        <img src="{{ asset('img/brow-img.png') }}"
                             alt="{{ $image->title }}"
                             class="image-card">

                        <span class="badge-category
                            {{ $image->category === 'まつげ' ? 'badge-lash' : 'badge-brow' }}">
                            {{ $image->category }}
                        </span>
                    </div>

                    <div class="card-content">
                        <h3 class="card-title">{{ $image->title }}</h3>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Customer Reviews --}}
    <section class="mb-16">
        <h2 class="page-title text-center mb-8">
            お客様の声
        </h2>

        <div class="grid-reviews">
            @foreach($reviews as $review)
                <div class="card card-review">
                    <div class="review-header">
                        <div>
                            <div class="review-name-age">
                                {{ $review->name }}
                                <span class="review-age">({{ $review->age }})</span>
                            </div>

                            <div class="review-rating">
                                @for ($i = 0; $i < $review->rating; $i++)
                                    <img src="{{ asset('img/star.png') }}"
                                         class="star"
                                         alt="★">
                                @endfor
                            </div>
                        </div>

                        <img src="{{ asset('img/quote.png') }}"
                             alt="Quote"
                             class="quote">
                    </div>

                    <p class="review-comment">{{ $review->comment }}</p>

                    <div class="review-details">
                        <span>{{ $review->service }}</span>
                        <span>{{ $review->date }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Stats --}}
    <section class="section-stats">
        <div class="stats-container">
            <h2 class="section-title-white text-center mb-12">
                Google口コミ お客様満足度
            </h2>

            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value">9割越え</div>
                    <div class="stat-label">リピート率</div>
                </div>

                <div class="stat-item">
                    <div class="stat-value">1,200+</div>
                    <div class="stat-label">施術実績</div>
                </div>

                <div class="stat-item">
                    <div class="stat-value">★5</div>
                    <div class="stat-label">平均評価</div>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
