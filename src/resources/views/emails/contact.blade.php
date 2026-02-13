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
    <title>お問い合わせが届きました</title>
    <style>
        body {
            font-family: Arial, 'Hiragino Sans', Meiryo, sans-serif;
            background: #f8f8f8;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 640px;
            margin: auto;
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        h2 {
            margin-top: 0;
            font-size: 20px;
            border-left: 4px solid {{ $colorAccent }};
            padding-left: 10px;
        }
        p, li {
            font-size: 15px;
            line-height: 1.7;
        }
        ul {
            padding: 0;
            list-style: none;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>📩 お問い合わせが届きました</h2>

    <p><strong>お名前：</strong>{{ $data['name'] }}</p>
    <p><strong>メール：</strong>{{ $data['email'] }}</p>
    <p><strong>電話番号：</strong>{{ $data['phone'] }}</p>

    <p><strong>件名：</strong>{{ $data['subject'] }}</p>

    <p><strong>内容：</strong><br>
        {!! nl2br(e($data['message'])) !!}
    </p>
</div>

</body>
</html>
