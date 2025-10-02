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

            <div class="form-group">
                <label for="name" class="entry__name">お名前</label>
                <input name="name" id="name" type="text" class="input" value="{{ old('name') }}" required autocomplete="name">
                @error('name')
                    <span class="form__error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="entry__name">メールアドレス</label>
                <input name="email" id="email" type="email" class="input" value="{{ old('email') }}" required autocomplete="email">
                @error('email')
                    <span class="form__error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="entry__name">パスワード</label>
                <input name="password" id="password" type="password" class="input" required autocomplete="new-password">
                @error('password')
                    <span class="form__error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="entry__name">確認用パスワード</label>
                <input name="password_confirmation" id="password_confirmation" type="password" class="input" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn--big">登録する</button>

            <a href="{{ route('login') }}" class="link">ログインはこちら</a>
        </form>
    </div>
</div>
@endsection
