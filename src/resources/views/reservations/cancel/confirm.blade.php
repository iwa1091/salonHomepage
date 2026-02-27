{{-- /resources/views/reservations/cancel/confirm.blade.php --}} 

@php
$brand        = $brand ?? [];
$brandName    = $brand['name']        ?? ($appName ?? config('app.name', 'Lash Brow Ohana'));
$brandTagline = $brand['tagline']     ?? '眉・まつげ専門サロン｜市原市';
$brandFooterName = $brand['footer_name'] ?? 'Lash Brow Ohana（ラッシュブロウ オハナ）';
$brandFooterAddr = $brand['footer_addr'] ?? '千葉県市原市';
$brandLogoUrl    = $brand['logo_url']    ?? null;
$colors       = $brand['colors'] ?? [];
$colorMain    = $colors['main']    ?? '#2F4F3E';
$colorAccent  = $colors['accent']  ?? '#CDAF63';
$colorBg      = $colors['bg']      ?? '#F1F1EF';
$colorText    = $colors['text']    ?? '#3A2F29';
$colorBoxBg   = $colors['box_bg']  ?? '#F7F6F2';
$colorBorder     = $colors['border']      ?? 'rgba(0,0,0,0.10)';
$colorSubText    = $colors['sub_text']    ?? 'rgba(0,0,0,0.60)';
$colorSoftBorder = $colors['soft_border'] ?? 'rgba(0,0,0,0.06)';
@endphp

<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'キャンセル確認 | ' . config('app.name') }}</title>
    @vite(['resources/css/base/theme.css', 'resources/css/pages/reservations/cancel.css'])
</head>
<body class="cancel-page">

@php
    // Controller/route から渡されても、渡されなくても動くようにフォールバック
    $isCanceled = $isCanceled ?? (($reservation->status ?? null) === 'canceled');
    $home = $home ?? url('/');
    $action = $action ?? \Illuminate\Support\Facades\URL::signedRoute(
        'reservations.public.cancel.perform',
        ['reservation' => $reservation->id]
    );

    $displayDate = $reservation->date ?? '';
    $displayTime = !empty($reservation->start_time)
        ? \Carbon\Carbon::parse($reservation->start_time)->format('H:i')
        : '';
    $serviceName = $reservation->service->name ?? '不明';

    $bodyMessage = $bodyMessage ?? (
        $isCanceled
            ? 'この予約は既にキャンセル済みです。'
            : '以下の予約をキャンセルします。よろしいですか？'
    );
@endphp

<div class="cancel-wrap">
    <div class="cancel-card">

        {{-- ヘッダー --}}
        <div class="cancel-card-header">
            <div class="cancel-card-brand">{{ $brandName }}</div>
        </div>

        {{-- 本文 --}}
        <div class="cancel-card-body">
            <h1 class="cancel-page-title">予約キャンセルのご確認</h1>
            <p class="cancel-message">{{ $bodyMessage }}</p>

            {{-- 予約情報 --}}
            <div class="cancel-info-box">
                <div class="cancel-info-row">
                    <strong>お名前：</strong>{{ $reservation->name ?? '' }}
                </div>
                <div class="cancel-info-row">
                    <strong>日時：</strong>{{ $displayDate }} {{ $displayTime }}
                </div>
                <div class="cancel-info-row">
                    <strong>メニュー：</strong>{{ $serviceName }}
                </div>
            </div>

            {{-- ボタン --}}
            <div class="cancel-buttons">
                @if ($isCanceled)
                    <a href="{{ $home }}" class="cancel-btn-primary">
                        トップへ戻る
                    </a>
                @else
                    <form method="POST" action="{{ $action }}" class="cancel-form">
                        @csrf

                        {{-- ✅ キャンセル理由（任意） --}}
                        <div style="margin:0 0 16px 0;">
                            <label for="cancel_reason" class="cancel-textarea-label">
                                キャンセル理由（任意）
                            </label>
                            <textarea
                                id="cancel_reason"
                                name="cancel_reason"
                                rows="4"
                                maxlength="500"
                                placeholder="例：体調不良のため／予定が変更になったため など"
                                class="cancel-textarea"
                            >{{ old('cancel_reason') }}</textarea>

                            @error('cancel_reason')
                                <div class="cancel-error">{{ $message }}</div>
                            @enderror

                            <div class="cancel-char-limit">※ 500文字以内</div>
                        </div>

                        <button type="submit" class="cancel-btn-primary">
                            この予約をキャンセルする
                        </button>
                    </form>

                    <a href="{{ $home }}" class="cancel-btn-secondary">
                        キャンセルしない（トップへ）
                    </a>
                @endif
            </div>

            <p class="cancel-note">
                ※ リンクが無効の場合や操作できない場合は、お手数ですが店舗へご連絡ください。
            </p>
        </div>

        {{-- フッター --}}
        <div class="cancel-card-footer">
            &copy; {{ date('Y') }} {{ $brandName }}
        </div>
    </div>
</div>

</body>
</html>
