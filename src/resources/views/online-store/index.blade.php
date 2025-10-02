@extends('layout.app')

@section('title', 'オンラインストア')

@section('styles')
    {{-- 公開用CSSファイルを読み込み --}}
    <link href="{{ asset('css/public_index.css') }}" rel="stylesheet">
    {{-- アイコンを使用するためFont Awesomeを読み込みます --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection

@section('content')
    <div class="store-page container">
        <div class="store-container">
            {{-- ヘッダーセクション --}}
            <div class="store-header">
                <h1 class="store-title">オンラインストア</h1>
                <p class="store-subtitle">
                    自宅でサロンのクオリティを。厳選したアイテムをお届けします。<br>
                    お客様の美容と健康をサポートする商品を多数取り揃えております。
                </p>
            </div>
            
            {{-- 検索・並び替え機能 --}}
            <div class="search-sort-container">
                <form action="{{ route('online-store.index') }}" method="GET" class="search-form">
                    <div class="input-group">
                        <input type="text" name="keyword" class="keyword-input" placeholder="商品名で検索" value="{{ request('keyword') }}">
                    </div>
                    <div class="input-group">
                        <select class="sort-select" name="sort">
                            <option value="">価格で並び替え</option>
                            <option value="high_price" {{ request('sort') == 'high_price' ? 'selected' : '' }}>高い順に表示</option>
                            <option value="low_price" {{ request('sort') == 'low_price' ? 'selected' : '' }}>低い順に表示</option>
                        </select>
                    </div>
                    <button type="submit" class="search-button button button-primary">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        検索
                    </button>
                </form>
            </div>
            
            {{-- 商品一覧 --}}
            <section class="products-section">
                <div class="product-grid">
                    @forelse ($products as $product)
                        <div class="product-card">
                            <a href="{{ route('online-store.show', $product->id) }}" class="product-link">
                                <div class="product-image-container">
                                    <img src="{{ asset($product->image_path) }}" alt="{{ $product->name }}" class="product-image">
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name">{{ $product->name }}</h3>
                                    <p class="product-description">{{ Str::limit($product->description, 50) }}</p>
                                    <span class="product-price">¥{{ number_format($product->price) }}</span>
                                </div>
                            </a>
                        </div>
                    @empty
                        <p class="no-products-message">商品が見つかりませんでした。</p>
                    @endforelse
                </div>
            </section>
            
            {{-- ページネーション --}}
            <div class="pagination-content">
                {{ $products->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
@endsection
