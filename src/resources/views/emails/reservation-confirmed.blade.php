@php
$brand = $brand ?? [];

$brandName    = $brand['name']    ?? ($appName ?? config('app.name', 'Lash Brow Ohana'));
$brandTagline = $brand['tagline'] ?? '眉・まつげ専門サロン｜市原市';

$brandFooterName = $brand['footer_name'] ?? 'Lash Brow Ohana（ラッシュブロウ オハナ）';
$brandFooterAddr = $brand['footer_addr'] ?? '千葉県市原市';

$brandLogoUrl = $brand['logo_url'] ?? null;

$colors = $brand['colors'] ?? [];
$colorMain   = $colors['main']   ?? '#2F4F3E';
$colorAccent = $colors['accent'] ?? '#CDAF63';
$colorBg     = $colors['bg']     ?? '#F1F1EF';
$colorText   = $colors['text']   ?? '#3A2F29';
$colorBoxBg  = $colors['box_bg'] ?? '#F7F6F2';

$colorBorder     = $colors['border']     ?? 'rgba(0,0,0,0.10)';
$colorSubText    = $colors['sub_text']   ?? 'rgba(0,0,0,0.60)';
$colorSoftBorder = $colors['soft_border']?? 'rgba(0,0,0,0.06)';
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ご予約完了のお知らせ</title>
    <style>
        /* ===============================
           Base Layout (Brand Theme)
           ※ メールは theme.css のCSS変数が使えないため、色は固定値で合わせます
        =============================== */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Hiragino Mincho ProN", "Hiragino Kaku Gothic ProN", Meiryo, "Noto Serif JP", serif;
            background-color: {{ $colorBg }};
            margin: 0;
            padding: 0;
            color: {{ $colorText }};
            line-height: 1.8;
        }

        .container {
            max-width: 640px;
            margin: 28px auto;
            background-color: #ffffff;
            border-radius: 12px; /* --radius-large */
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.10); /* --border-color */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08); /* --shadow-soft */
        }

        .header {
            background-color: {{ $colorMain }};
            color: #ffffff;
            text-align: center;
            padding: 18px 16px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            letter-spacing: 0.04em;
            font-weight: 700;
        }

        .content {
            padding: 22px 22px 18px;
        }

        .content p {
            margin: 12px 0;
            font-size: 15px;
        }

        /* Reservation detail box */
        ul {
            margin: 14px 0 0;
            padding: 14px 16px;
            border-radius: 10px;
            list-style: none;
            background: {{ $colorBoxBg }};
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        li {
            margin: 8px 0;
            font-size: 15px;
            color: {{ $colorText }};
        }

        .note {
            font-size: 12.5px;
            color: rgba(0, 0, 0, 0.65);
            margin-top: 10px;
        }

        /* ===============================
           Buttons
        =============================== */
        .btn-wrapper {
            text-align: center;
            margin: 18px 0 10px;
        }

        .btn {
            display: inline-block;
            width: 100%;
            max-width: 420px;
            box-sizing: border-box;
            background-color: {{ $colorAccent }};
            color: #ffffff !important;
            padding: 12px 18px;
            font-size: 15px;
            font-weight: 700;
            border-radius: 9999px; /* --radius-full */
            text-decoration: none;
            border: 1px solid {{ $colorAccent }};
        }

        .btn-secondary {
            display: inline-block;
            width: 100%;
            max-width: 420px;
            box-sizing: border-box;
            background-color: #ffffff;
            color: {{ $colorText }} !important;
            padding: 12px 18px;
            font-size: 15px;
            font-weight: 700;
            border-radius: 9999px;
            text-decoration: none;
            border: 1px solid rgba(0, 0, 0, 0.18);
            margin-top: 10px;
        }

        /* ===============================
           Footer
        =============================== */
        .footer {
            text-align: center;
            font-size: 12px;
            color: rgba(0, 0, 0, 0.60);
            padding: 14px 16px;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            background: #ffffff;
        }

        /* ===============================
           Mobile
        =============================== */
        @media (max-width: 600px) {
            .container {
                margin: 10px;
            }
            .content {
                padding: 18px 16px 14px;
            }
            .header h1 {
                font-size: 17px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $brandName }}</h1>
        </div>

        <div class="content">
            <p>{{ $reservation->name }} 様</p>

            <p>この度は <strong>{{ $brandName }}</strong> にご予約いただき、誠にありがとうございます。</p>

            <p>以下の内容でご予約を承りました。</p>

            <ul>
                <li><strong>【予約番号】:</strong>{{ $reservation->reservation_code }}<strong>※この予約をマイページに紐付ける際に入力して下さい。</strong></li>
                <li><strong>日時：</strong>{{ $reservation->date }} {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }}</li>
                <li><strong>メニュー：</strong>{{ $reservation->service->name ?? '不明' }}</li>
                <li><strong>ご要望：</strong>{{ $reservation->notes ?? 'なし' }}</li>
            </ul>

            <p class="note">ご予約内容の確認やキャンセルをご希望の場合は、下記のボタンからご確認ください。</p>

            @php
                // ✅ cancelUrl が渡ってこない場合でも、署名付きURLを自動生成（route名は web.php と一致）
                $signedCancelUrl = $cancelUrl ?? \Illuminate\Support\Facades\URL::signedRoute(
                    'reservations.public.cancel.show',
                    ['reservation' => $reservation->id]
                );
            @endphp

            <div class="btn-wrapper">
                <a href="{{ url('/mypage/reservations') }}" class="btn">▶ ご予約内容を確認する</a>

                {{-- 署名付きキャンセルURLが渡ってくる場合のみ表示（未定義でもエラーになりません） --}}
                @if(!empty($signedCancelUrl ?? null))
                    <a href="{{ $signedCancelUrl }}" class="btn-secondary">キャンセル手続きへ</a>
                @endif
            </div>

            <p>ご来店を心よりお待ちしております。</p>

            <p style="margin-top: 18px;">{{ $brandName }}</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.
        </div>
    </div>
</body>
</html>
