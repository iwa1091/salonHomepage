{{-- /resources/views/reservations/cancel/done.blade.php --}}

<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'キャンセル結果 | ' . config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background:{{ $colorBg }}; color:{{ $colorText }}; font-family:-apple-system, BlinkMacSystemFont, 'Hiragino Kaku Gothic ProN', Meiryo, Arial, sans-serif; line-height:1.7;">
@include('emails.partials.brand-config')

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

<div style="max-width:720px; margin:24px auto; padding:0 16px;">
    <div style="background:#fff; border:1px solid rgba(0,0,0,0.10); border-radius:12px; overflow:hidden; box-shadow:0 10px 20px rgba(0,0,0,0.08);">

        {{-- ヘッダー --}}
        <div style="background:{{ $colorMain }}; color:#fff; padding:16px 18px;">
            <div style="font-weight:700; letter-spacing:.03em;">{{ $brandName }}</div>
        </div>

        {{-- 本文 --}}
        <div style="padding:18px;">
            <p style="margin:0 0 12px 0;">{{ $message }}</p>

            {{-- 予約情報（あれば表示） --}}
            @if(!empty($displayDate) || !empty($displayTime) || !empty($serviceName) || !empty($cancelReason))
                <div style="background:#F7F6F2; border:1px solid rgba(0,0,0,0.08); border-radius:10px; padding:14px; margin:0 0 14px 0;">
                    @if(!empty($reservation->name))
                        <div style="margin:6px 0;"><strong>お名前：</strong>{{ $reservation->name }}</div>
                    @endif
                    @if(!empty($displayDate) || !empty($displayTime))
                        <div style="margin:6px 0;"><strong>日時：</strong>{{ $displayDate }} {{ $displayTime }}</div>
                    @endif
                    @if(!empty($serviceName))
                        <div style="margin:6px 0;"><strong>メニュー：</strong>{{ $serviceName }}</div>
                    @endif
                    @if(!empty($cancelReason))
                        <div style="margin:6px 0;"><strong>キャンセル理由：</strong>{{ $cancelReason }}</div>
                    @endif
                </div>
            @endif

            <a href="{{ $home }}"
               style="display:inline-block; padding:12px 16px; border-radius:9999px; background:{{ $colorAccent }}; color:#fff; text-decoration:none; font-weight:700;">
                トップへ戻る
            </a>

            <p style="margin:14px 0 0 0; font-size:12.5px; color:rgba(0,0,0,0.65);">
                ※ 操作できない場合は、お手数ですが店舗へご連絡ください。
            </p>
        </div>

        {{-- フッター --}}
        <div style="padding:14px 18px; border-top:1px solid rgba(0,0,0,0.06); color:rgba(0,0,0,0.60); font-size:12px; text-align:center;">
            &copy; {{ date('Y') }} {{ $brandName }}
        </div>
    </div>
</div>

</body>
</html>
