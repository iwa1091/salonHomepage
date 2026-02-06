{{-- /resources/views/reservations/cancel/confirm.blade.php --}} 

<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'キャンセル確認 | Lash Brow Ohana' }}</title>
</head>
<body style="margin:0; padding:0; background:#F1F1EF; color:#3A2F29; font-family:-apple-system, BlinkMacSystemFont, 'Hiragino Kaku Gothic ProN', Meiryo, Arial, sans-serif; line-height:1.7;">

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

<div style="max-width:720px; margin:24px auto; padding:0 16px;">
    <div style="background:#fff; border:1px solid rgba(0,0,0,0.10); border-radius:12px; overflow:hidden; box-shadow:0 10px 20px rgba(0,0,0,0.08);">

        {{-- ヘッダー --}}
        <div style="background:#2F4F3E; color:#fff; padding:16px 18px;">
            <div style="font-weight:700; letter-spacing:.03em;">Lash Brow Ohana</div>
        </div>

        {{-- 本文 --}}
        <div style="padding:18px;">
            <p style="margin:0 0 12px 0;">{{ $bodyMessage }}</p>

            {{-- 予約情報 --}}
            <div style="background:#F7F6F2; border:1px solid rgba(0,0,0,0.08); border-radius:10px; padding:14px;">
                <div style="margin:6px 0;">
                    <strong>お名前：</strong>{{ $reservation->name ?? '' }}
                </div>
                <div style="margin:6px 0;">
                    <strong>日時：</strong>{{ $displayDate }} {{ $displayTime }}
                </div>
                <div style="margin:6px 0;">
                    <strong>メニュー：</strong>{{ $serviceName }}
                </div>
            </div>

            {{-- ボタン --}}
            <div style="margin-top:14px;">
                @if ($isCanceled)
                    <a href="{{ $home }}"
                       style="display:inline-block; padding:12px 16px; border-radius:9999px; background:#CDAF63; color:#fff; text-decoration:none; font-weight:700;">
                        トップへ戻る
                    </a>
                @else
                    <form method="POST" action="{{ $action }}" style="margin:0;">
                        @csrf

                        {{-- ✅ キャンセル理由（任意） --}}
                        <div style="margin:0 0 10px;">
                            <label for="cancel_reason" style="display:block; font-weight:700; margin-bottom:6px;">
                                キャンセル理由（任意）
                            </label>
                            <textarea
                                id="cancel_reason"
                                name="cancel_reason"
                                rows="4"
                                maxlength="500"
                                placeholder="例：体調不良のため／予定が変更になったため など"
                                style="width:100%; box-sizing:border-box; padding:10px 12px; border-radius:10px; border:1px solid rgba(0,0,0,0.18); background:#fff; font-family:inherit;"
                            >{{ old('cancel_reason') }}</textarea>

                            @error('cancel_reason')
                                <div style="margin-top:6px; color:#b91c1c; font-size:13px;">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div style="margin-top:6px; color:rgba(0,0,0,0.65); font-size:12.5px;">
                                ※ 500文字以内
                            </div>
                        </div>

                        <button type="submit"
                                style="width:100%; padding:12px 16px; border-radius:9999px; background:#CDAF63; color:#fff; border:1px solid #CDAF63; font-weight:700; cursor:pointer;">
                            この予約をキャンセルする
                        </button>
                    </form>

                    <div style="height:10px;"></div>

                    <a href="{{ $home }}"
                       style="display:inline-block; width:100%; text-align:center; padding:12px 16px; border-radius:9999px; background:#fff; color:#3A2F29; border:1px solid rgba(0,0,0,0.18); text-decoration:none; font-weight:700;">
                        キャンセルしない（トップへ）
                    </a>
                @endif
            </div>

            <p style="margin:14px 0 0 0; font-size:12.5px; color:rgba(0,0,0,0.65);">
                ※ リンクが無効の場合や操作できない場合は、お手数ですが店舗へご連絡ください。
            </p>
        </div>

        {{-- フッター --}}
        <div style="padding:14px 18px; border-top:1px solid rgba(0,0,0,0.06); color:rgba(0,0,0,0.60); font-size:12px; text-align:center;">
            &copy; {{ date('Y') }} Lash Brow Ohana
        </div>
    </div>
</div>

</body>
</html>
