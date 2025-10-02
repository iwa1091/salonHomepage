@extends('layout.app')

@section('title', 'まつげと眉の専門サロン')

@section('styles')
    <link href="{{ asset('css/home.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="home-page-container">

        {{-- Hero Section --}}
        <section class="hero-section">
            <div class="hero-image-overlay"></div>
            <div class="hero-content-container">
                <div class="hero-text-wrapper">
                    <div class="hero-text-group">
                        <h1 class="hero-title">
                            美しいまつげと眉で<br />
                            <span class="highlight-text">自然な美しさを</span>
                        </h1>
                        <p class="hero-subtitle">
                            経験豊富なアイリストが、お客様一人ひとりの目元に合わせて<br />
                            最適な施術をご提供いたします
                        </p>
                    </div>
                    <div class="hero-buttons-wrapper">
                        <a href="{{ route('contact.form') }}" class="button button-primary">
                            ご予約はこちら
                        </a>
                        <a href="{{ route('menu_price') }}" class="button button-secondary">
                            メニューを見る
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features Section --}}
        <section class="features-section">
            <div class="content-container">
                <div class="section-header">
                    <h2 class="section-title">
                        ohanaが選ばれる理由
                    </h2>
                    <p class="section-description">
                        お客様に安心してご利用いただけるよう、こだわりのポイントをご紹介します
                    </p>
                </div>

                <div class="features-grid">
                    @php
                        $features = [
                            [
                                'icon' => 'Eye',
                                'title' => '豊富な経験',
                                'description' => '10年以上の実績を持つ経験豊富なアイリストが、お客様の魅力を最大限に引き出します'
                            ],
                            [
                                'icon' => 'Sparkles',
                                'title' => '高品質な材料',
                                'description' => '厳選した高品質な材料のみを使用し、安全で美しい仕上がりをお約束します'
                            ],
                            [
                                'icon' => 'Heart',
                                'title' => '丁寧なカウンセリング',
                                'description' => 'お客様のご希望を丁寧にお聞きし、ライフスタイルに合わせた最適なデザインをご提案'
                            ]
                        ];
                    @endphp
                    @foreach($features as $feature)
                        <div class="feature-card">
                            <div class="card-content">
                                <div class="feature-icon-wrapper">
                                    {{-- アイコンはSVGまたはアイコンフォントとして別途用意 --}}
                                </div>
                                <h3 class="feature-title">{{ $feature['title'] }}</h3>
                                <p class="feature-description">{{ $feature['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Reviews Preview --}}
        <section class="reviews-section">
            <div class="content-container">
                <div class="section-header">
                    <h2 class="section-title">
                        お客様の声
                    </h2>
                    <p class="section-description">
                        たくさんの嬉しいお声をいただいております
                    </p>
                </div>

                <div class="reviews-grid">
                    @php
                        $reviews = [
                            [
                                'name' => 'M.S様',
                                'rating' => 5,
                                'comment' => '自然な仕上がりで、毎朝のメイク時間が短縮されました。丁寧な施術で安心してお任せできます。'
                            ],
                            [
                                'name' => 'A.T様',
                                'rating' => 5,
                                'comment' => 'カウンセリングが丁寧で、希望通りの仕上がりになりました。持ちも良く、とても満足しています。'
                            ],
                            [
                                'name' => 'R.K様',
                                'rating' => 5,
                                'comment' => '初めてのまつげエクステでしたが、わかりやすく説明していただき、安心して施術を受けられました。'
                            ]
                        ];
                    @endphp
                    @foreach($reviews as $review)
                        <div class="review-card">
                            <div class="card-content">
                                <div class="review-rating-wrapper">
                                    <div class="star-icons-wrapper">
                                        @for ($i = 0; $i < $review['rating']; $i++)
                                            <img src="{{ asset('img/star.png') }}" alt="星の評価" class="star-icon">
                                        @endfor
                                    </div>
                                    <span class="review-author">{{ $review['name'] }}</span>
                                </div>
                                <p class="review-comment">{{ $review['comment'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="button-wrapper">
                    <a href="{{ route('gallery') }}" class="button outline-button">
                        もっと見る
                    </a>
                </div>
            </div>
        </section>

        {{-- CTA Section --}}
        <section class="cta-section">
            <div class="content-container">
                <h2 class="cta-title">
                    美しい目元で毎日をもっと輝かせませんか？
                </h2>
                <p class="cta-description">
                    お客様のご希望に合わせて、最適な施術プランをご提案いたします。
                    お気軽にお問い合わせください。
                </p>
                <a href="{{ route('contact.form') }}" class="button white-button">
                    今すぐご予約
                </a>
            </div>
        </section>
    </div>
@endsection