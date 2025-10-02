@extends('layout.app')

@section('title', $product->name . ' | 商品詳細')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/public_show.css') }}">
@endsection

@section('content')
<div class="product-detail-container">
{{-- 商品画像 --}}
<div class="product-image-area">
<img src="{{ asset($product->image_path) }}" alt="{{ $product->name }}" class="product-image">
</div>

    {{-- 商品情報と決済ボタン --}}
    <div class="product-info-area">
        <h1 class="product-name">{{ $product->name }}</h1>
        <p class="product-description">{{ $product->description }}</p>
        <p class="product-price">¥{{ number_format($product->price) }}</p>

        @auth
            {{-- ログイン済みの場合は購入手続きへ（フォームでPOST送信） --}}
            <form action="{{ route('online-store.checkout', $product->id) }}" method="POST">
                @csrf
                <button type="submit" class="buy-button">
                    購入手続きへ
                </button>
            </form>
        @else
            {{-- 未ログインの場合は会員登録へ誘導 --}}
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