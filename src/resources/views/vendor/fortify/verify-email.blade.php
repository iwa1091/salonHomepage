@extends('layout.app')

@section('title', 'メール認証')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/verify.css') }}">
@endsection

@section('content')
<div class="verify-page">
    <div class="verify-container">
        <div class="verify-header">
            <h1 class="page__title">メール認証はお済みですか？</h1>
        </div>
        <div class="verify-content">
            @if (session('resent'))
                <div class="alert alert--success" role="alert">
                    新しい認証リンクが、メールアドレスに送信されました。
                </div>
            @endif

            <p class="verify-text">
                このページを閲覧するには、メールアドレスの認証が必要です。
                もし認証用のメールが届いていない場合は、以下のボタンをクリックして、新しい認証メールを再送信してください。
            </p>

            <form class="resend-form" method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn--big btn--resend">認証メールを再送信する</button>
            </form>
        </div>
    </div>
</div>
@endsection
