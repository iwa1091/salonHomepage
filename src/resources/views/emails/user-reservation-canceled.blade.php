<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ご予約キャンセルのお知らせ</title>
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

        .title {
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        /* Detail box */
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

        /* Buttons */
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

        /* Footer */
        .footer {
            text-align: center;
            font-size: 12px;
            color: rgba(0, 0, 0, 0.60);
            padding: 14px 16px;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            background: #ffffff;
        }

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
    @include('emails.partials.brand-config')
    <div class="container">
        <div class="header">
            <h1>{{ $brandName }}</h1>
        </div>

        <div class="content">
            <p class="title">ご予約キャンセルのお知らせ</p>

            <p>{{ $reservation->name }} 様</p>

            <p>いつも <strong>{{ $brandName }}</strong> をご利用いただきありがとうございます。</p>

            <p>以下のご予約はお客様のご希望によりキャンセルされました。</p>

            <ul>
                <li><strong>日時：</strong>{{ $reservation->date }} {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }}</li>
                <li><strong>メニュー：</strong>{{ $reservation->service->name ?? '不明' }}</li>
                <li><strong>キャンセル理由：</strong>{{ $reservation->cancel_reason ?? '未入力' }}</li>
            </ul>

            <p class="note">キャンセル内容の確認や、別日のご予約をご希望の場合は、以下のボタンからご確認ください。</p>

            <div class="btn-wrapper">
                <a href="{{ url('/mypage/reservations') }}"
                   class="btn"
                   style="display:inline-block; width:100%; max-width:420px; box-sizing:border-box; background-color:{{ $colorAccent }}; color:#ffffff; padding:12px 18px; font-size:15px; font-weight:700; border-radius:9999px; text-decoration:none; border:1px solid {{ $colorAccent }};">
                    ▶ ご予約一覧を開く
                </a>
            </div>

            <p>またのご利用を心よりお待ちしております。</p>

            <p style="margin-top: 18px;">{{ $brandName }}</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.
        </div>
    </div>
</body>
</html>
