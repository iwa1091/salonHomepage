@extends('layout.app')

@section('title', '目元・肌・頭皮のトータルケアサロン')

@section('styles')
    @vite(['resources/css/pages/home/home.css'])
@endsection

@section('content')
    <div class="home-page-container">
        <section class="hero-section"
                 x-data="heroSlider()"
                 x-init="startAutoSlide()">

            {{-- スライド画像 --}}
            <template x-for="(slide, index) in slides" :key="index">
                <div class="hero-slide"
                     :class="{ 'hero-slide--active': current === index }"
                     aria-hidden="current !== index">
                    <picture>
                        <source :srcset="slide.sp" media="(max-width: 767px)" type="image/jpeg">
                        <img :src="slide.pc"
                             :alt="slide.alt"
                             :class="'hero-slide__img hero-img-pos-' + index"
                             loading="eager"
                             fetchpriority="high">
                    </picture>
                </div>
            </template>

            {{-- グラデーションオーバーレイ --}}
            <div class="hero-image-overlay"
                 :class="current === 0 ? 'hero-overlay--left' : 'hero-overlay--right'"></div>

            {{-- スライド1 テキスト（左配置・縦書き） --}}
            <div class="hero-slide-content hero-slide-content--left"
                 x-show="current === 0"
                 x-transition.opacity.duration.500ms>
                <div class="hero-text-vertical-group">
                    <p class="hero-text-col">年齢とともに変化する肌、髪</p>
                    <p class="hero-text-col">目元の印象のお悩み</p>
                    <p class="hero-text-col hero-text-col--accent">肌、頭皮、まつ毛、眉毛<br>まるっとラクにお任せ</p>
                </div>
                <div class="hero-slide-buttons">
                    <a href="{{ route('menu_price') }}" class="button button-primary">メニューを見て予約する</a>
                    <a href="{{ route('contact.form') }}" class="button button-secondary">お問い合わせ</a>
                </div>
            </div>

            {{-- スライド2 テキスト（右配置・縦書き） --}}
            <div class="hero-slide-content hero-slide-content--right"
                 x-show="current === 1"
                 x-transition.opacity.duration.500ms>
                <div class="hero-text-vertical-group">
                    <p class="hero-text-col">印象は、仕事の武器になる。</p>
                    <p class="hero-text-col">眉毛・肌・頭皮を整えるだけで、</p>
                    <p class="hero-text-col hero-text-col--accent">「できる人」の清潔感へ。</p>
                </div>
                <div class="hero-slide-buttons">
                    <a href="{{ route('menu_price') }}" class="button button-primary">メニューを見て予約する</a>
                    <a href="{{ route('contact.form') }}" class="button button-secondary">お問い合わせ</a>
                </div>
            </div>

            {{-- インジケーター（ドット） --}}
            <div class="hero-indicators">
                <template x-for="(slide, index) in slides" :key="'dot-' + index">
                    <button class="hero-indicator"
                            :class="{ 'hero-indicator--active': current === index }"
                            @click="goTo(index)"
                            :aria-label="'スライド ' + (index + 1) + ' へ移動'">
                    </button>
                </template>
            </div>
        </section>

        {{-- Features Section --}}
        <section class="features-section">
            <div class="content-container">
                <div class="section-header">
                    <h2 class="section-title">
                        {{ config('app.name') }}が選ばれる理由
                    </h2>
                    <p class="section-description">
                        肌・まつ毛・眉毛・頭皮のお悩みに、ひとつずつ丁寧に向き合います。
                    </p>
                </div>

                <div class="features-grid">
                    @php
                        $features = [
                            [
                                'icon' => 'img/self-introduction.jpeg',
                                'title' => '豊富な経験',
                                'description' => "サロン現場で15年以上、\n多くのお客様の変化に寄り添ってきました。\n\n流行ではなく、\n今のあなたにフィットするデザインを。\n自分を好きになれる印象づくりを大切にしています。"
                            ],
                            [
                                'icon' => 'img/mens.jpeg',
                                'title' => '丁寧なカウンセリング',
                                'description' => "お悩みの背景やご要望を丁寧に伺い、\nその方のペースに合わせたご提案を。\n\n無理に変えるのではなく、\n自然に印象が整うデザインを大切にしています。"
                            ],
                            [
                                'icon' => 'img/high-quality.jpeg',
                                'title' => '品質へのこだわり',
                                'description' => "頭皮・肌・まつ毛の状態やお悩みに合わせて、\n負担が少なく確かな変化を感じられる\n商材と技術を厳選しています。\n\nその日だけでなく、\n1週間後・1ヶ月後と\nより実感が深まる施術を大切にしています。"
                            ]
                        ];
                    @endphp
                    @foreach($features as $feature)
                        <div class="feature-card">
                            <div class="card-content">
                                <div class="feature-icon-wrapper">
                                    @if (!empty($feature['icon']))
                                        <img src="{{ asset($feature['icon']) }}" alt="{{ $feature['title'] }}" class="feature-icon-img">
                                    @endif
                                </div>
                                <h3 class="feature-title">{{ $feature['title'] }}</h3>
                                <p class="feature-description">{!! nl2br(e($feature['description'])) !!}</p>
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
                    <h2 class="section-title">お客様の声</h2>
                    <p class="section-description">Google口コミ 星5獲得 ／ リピーター率9割超え</p>
                </div>

                <div class="reviews-grid">
                    @php
                        $reviews = [
                            ['name' => 'M.S様', 'rating' => 5, 'comment' => '自然な仕上がりで、毎朝のメイク時間が短縮されました。丁寧な施術で安心してお任せできます。'],
                            ['name' => 'A.T様', 'rating' => 5, 'comment' => 'カウンセリングが丁寧で、希望通りの仕上がりになりました。持ちも良く、とても満足しています。'],
                            ['name' => 'R.K様', 'rating' => 5, 'comment' => '初めての施術でしたが、丁寧に説明していただき、リラックスして受けることができました。']
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
                    <a href="{{ route('gallery') }}" class="button outline-button">もっと見る</a>
                </div>
            </div>
        </section>

        {{-- CTA Section --}}
        <section class="cta-section">
            <div class="content-container">
                <h2 class="cta-title">あなたらしい印象で、毎日をもっと輝かせませんか？</h2>
                <p class="cta-description">
                    ご希望に合わせた施術プランをご提案します。どうぞお気軽にご相談ください。
                </p>
                <a href="{{ route('menu_price') }}" class="button white-button">メニュー・料金を見る</a>
            </div>
        </section>
    </div>
@endsection

@section('scripts')
<script>
    function heroSlider() {
        return {
            current: 0,
            timer: null,
            slides: [
                {
                    pc: "{{ asset('img/hero-slide1-pc.jpg') }}",
                    sp: "{{ asset('img/hero-slide1-pc.jpg') }}",
                    alt: "まつげ・眉毛サロン Lash Brow Ohana のイメージ",
                },
                {
                    pc: "{{ asset('img/hero-slide2-pc.jpg') }}",
                    sp: "{{ asset('img/hero-slide2-pc.jpg') }}",
                    alt: "メンズ眉スタイリングのイメージ",
                },
            ],
            startAutoSlide() {
                this.timer = setInterval(() => {
                    this.current = (this.current + 1) % this.slides.length;
                }, 5000);
            },
            goTo(index) {
                this.current = index;
                // 手動操作時はタイマーをリセット
                clearInterval(this.timer);
                this.startAutoSlide();
            },
        };
    }
</script>
@endsection
