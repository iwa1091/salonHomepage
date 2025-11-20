@extends('layout.app')

@section('title', $product->name . ' | 商品詳細')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/public_show.css') }}">
@endsection

@section('content')
<div class="product-detail-container">
    {{-- 商品画像 --}}
    <div class="product-image-area">
        <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="product-image">
    </div>

    {{-- 商品情報と決済ボタン --}}
    <div class="product-info-area">
        <h1 class="product-name">{{ $product->name }}</h1>
        <p class="product-description">{{ $product->description }}</p>
        <p class="product-price">¥{{ number_format($product->price) }}</p>

        {{-- 在庫表示 --}}
        @if ($product->stock > 0)
            <p class="product-stock text-green-600">在庫: {{ $product->stock }} 点</p>
        @else
            <p class="product-stock text-red-500 font-bold">売り切れ</p>
        @endif

        {{-- 購入ボタンエリア --}}
        @auth
            @if($product->stock > 0)
                {{-- 在庫がある場合：購入可能 --}}
                <form action="{{ route('online-store.checkout', $product->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="buy-button button button-primary">
                        購入手続きへ
                    </button>
                </form>
            @else
                {{-- 在庫ゼロ：購入不可 --}}
                <button class="buy-button button button-disabled" disabled>売り切れ</button>
            @endif
        @else
            {{-- 未ログイン時：登録促進 --}}
            <a href="{{ route('register') }}" class="buy-button">
                購入には会員登録が必要です
            </a>
        @endauth
        
        <a href="{{ route('online-store.index') }}" class="back-link">
            商品一覧へ戻る
        </a>
    </div>
</div>
@endsection
