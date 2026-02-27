{{-- /resources/views/reservations/cancel/done.blade.php --}}

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
    <title>{{ $title ?? 'キャンセル結果 | ' . config('app.name') }}</title>
    @vite(['resources/css/base/theme.css', 'resources/css/pages/reservations/cancel.css'])
</head>
<body class="cancel-page">

@php
    // routes/web.php の最小実装（文字列HTML）と互換を持たせるため、変数はすべてフォールバックありにしています
    $home = $home ?? url('/');

    // Controller 側で $alreadyCanceled / $message を渡す想定だが、渡されなくても動くようにする
    $alreadyCanceled = $alreadyCanceled ?? (($reservation->status ?? null) === 'canceled');

    // 表示用メッセージ（routes/web.php の挙動に合わせる）
    $message = $message ?? ($alreadyCanceled ? 'この予約は既にキャンセル済みです。' : '予約をキャンセルしました。');

    // 予約情報を表示したい場合に備えて（渡されなければ表示しない）
    $displayDate = $reservation->date ?? null;
    $displayTime = !empty($reservation->start_time)
        ? \Carbon\Carbon::parse($reservation->start_time)->format('H:i')
        : null;
    $serviceName = $reservation->service->name ?? null;

    // ✅ キャンセル理由（あれば表示）
    $cancelReason = $reservation->cancel_reason ?? null;
@endphp

<div class="cancel-wrap">
    <div class="cancel-card">

        {{-- ヘッダー --}}
        <div class="cancel-card-header">
            <div class="cancel-card-brand">{{ $brandName }}</div>
        </div>

        {{-- 本文 --}}
        <div class="cancel-card-body">
            <h1 class="cancel-page-title">
                {{ $alreadyCanceled ? 'キャンセル済みのご予約' : 'キャンセルが完了しました' }}
            </h1>
            <p class="cancel-message">{{ $message }}</p>

            {{-- 予約情報（あれば表示） --}}
            @if(!empty($displayDate) || !empty($displayTime) || !empty($serviceName) || !empty($cancelReason))
                <div class="cancel-info-box">
                    @if(!empty($reservation->name))
                        <div class="cancel-info-row"><strong>お名前：</strong>{{ $reservation->name }}</div>
                    @endif
                    @if(!empty($displayDate) || !empty($displayTime))
                        <div class="cancel-info-row"><strong>日時：</strong>{{ $displayDate }} {{ $displayTime }}</div>
                    @endif
                    @if(!empty($serviceName))
                        <div class="cancel-info-row"><strong>メニュー：</strong>{{ $serviceName }}</div>
                    @endif
                    @if(!empty($cancelReason))
                        <div class="cancel-info-row"><strong>キャンセル理由：</strong>{{ $cancelReason }}</div>
                    @endif
                </div>
            @endif

            <div class="cancel-buttons">
                <a href="{{ $home }}" class="cancel-btn-primary">
                    トップへ戻る
                </a>
            </div>

            <p class="cancel-note">
                ※ 操作できない場合は、お手数ですが店舗へご連絡ください。
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
