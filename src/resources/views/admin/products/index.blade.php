@extends('layout.app')

@section('title', '商品管理') {{-- 管理画面向けにタイトルを変更 --}}

@section('styles')
    {{-- CSSファイルをproducts.cssに集約します --}}
    <link href="{{ asset('css/products_index.css') }}" rel="stylesheet">
    {{-- 検索ボタンにアイコンを使用するためFont Awesomeを読み込みます --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection

@section('content')
    <div class="store-page">
        <div class="store-container">
            {{-- ヘッダーセクション --}}
            <div class="store-header">
                <h1 class="store-title">商品管理</h1> {{-- 見出しを「商品管理」に変更 --}}
                <p class="store-subtitle">
                    オンラインストアで販売する商品の追加、編集、削除を行います。<br>
                    在庫情報や価格設定を最新の状態に保ちましょう。
                </p>
            </div>

            {{-- 検索・並び替え・追加ボタン --}}
            <div class="search-sort-container">
                {{-- ルート名を admin.products.index に修正 --}}
                <form action="{{ route('admin.products.index') }}" method="GET" class="search-form">
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
                    <button type="submit" class="search-button">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        検索
                    </button>
                </form>

                {{-- 新規商品追加ボタン --}}
                {{-- ルート名を admin.products.create に修正 --}}
                <a href="{{ route('admin.products.create') }}" class="button button-primary add-product-button">
                    <i class="fa-solid fa-plus-circle"></i>
                    新しい商品を追加
                </a>
            </div>
            
            {{-- 商品一覧 --}}
            <section class="products-section">
                @if (session('message'))
                    <div class="message-container">
                        <p class="message">{{ session('message') }}</p>
                    </div>
                @endif
                
                <div class="product-grid">
                    @forelse ($products as $product)
                        <div class="product-card">
                            {{-- ルート名を admin.products.show に修正 --}}
                            <a href="{{ route('admin.products.show', $product->id) }}" class="product-link">
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
