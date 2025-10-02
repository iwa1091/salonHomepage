@extends('layout.app')

@section('title', 'ログイン')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/authentication.css') }}">
@endsection

@section('content')
<div class="authentication-page">
    <div class="authentication-container">
        <form action="{{ route('login') }}" method="post" class="authenticate-form">
            @csrf
            <h1 class="page__title">ログイン</h1>

            <div class="form-group">
                <label for="email" class="entry__name">メールアドレス</label>
                <input name="email" id="email" type="email" class="input" value="{{ old('email') }}" required autocomplete="email" autofocus>
                @error('email')
                    <span class="form__error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="entry__name">パスワード</label>
                <input name="password" id="password" type="password" class="input" required autocomplete="current-password">
                @error('password')
                    <span class="form__error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn--big">ログインする</button>

            <a href="{{ route('register') }}" class="link">会員登録はこちら</a>
        </form>
    </div>
</div>
@endsection
