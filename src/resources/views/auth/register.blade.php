@extends('layout.app')

@section('title', '会員登録')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/authentication.css') }}">
@endsection

@section('content')
<div class="authentication-page">
    <div class="authentication-container">
        <form action="{{ route('register') }}" method="post" class="authenticate-form">
            @csrf
            <h1 class="page__title">会員登録</h1>

            {{-- 名前 --}}
            <div class="form-group">
                <label for="name" class="entry__name">お名前</label>
                <input
                    name="name"
                    id="name"
                    type="text"
                    class="input"
                    value="{{ old('name') }}"
                    required
                    autocomplete="name"
                >
                @error('name')
                    <span class="form__error">{{ $message }}</span>
                @enderror
            </div>

            {{-- メール --}}
            <div class="form-group">
                <label for="email" class="entry__name">メールアドレス</label>
                <input
                    name="email"
                    id="email"
                    type="email"
                    class="input"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                >
                @error('email')
                    <span class="form__error">{{ $message }}</span>
                @enderror
            </div>

            {{-- ✅ 電話番号（追加部分） --}}
            <div class="form-group">
                <label for="phone" class="entry__name">電話番号（任意）</label>
                <input
                    name="phone"
                    id="phone"
                    type="tel"
                    class="input"
                    value="{{ old('phone') }}"
                    placeholder="例：09012345678"
                    pattern="[0-9]{10,11}"
                    inputmode="numeric"
                >
                <small class="text-gray-500" style="font-size: 0.85rem;">※ハイフンなしで入力してください</small>
                @error('phone')
                    <span class="form__error">{{ $message }}</span>
                @enderror
            </div>

            {{-- パスワード --}}
            <div class="form-group">
                <label for="password" class="entry__name">パスワード</label>
                <input
                    name="password"
                    id="password"
                    type="password"
                    class="input"
                    required
                    autocomplete="new-password"
                >
                @error('password')
                    <span class="form__error">{{ $message }}</span>
                @enderror
            </div>

            {{-- 確認用パスワード --}}
            <div class="form-group">
                <label for="password_confirmation" class="entry__name">確認用パスワード</label>
                <input
                    name="password_confirmation"
                    id="password_confirmation"
                    type="password"
                    class="input"
                    required
                    autocomplete="new-password"
                >
            </div>

            {{-- 登録ボタン --}}
            <button type="submit" class="btn btn--big">登録する</button>

            <a href="{{ route('login') }}" class="link">ログインはこちら</a>
        </form>
    </div>
</div>
@endsection
