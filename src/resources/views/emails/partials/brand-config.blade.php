@php
/**
 * ブランド差分は基本ここだけ直せば全メールに反映されます。
 * 他店舗へコピー運用する場合は、ここを店舗用に調整するのが最短です。
 *
 * Mailable 側から $brand を渡せば動的にも切替できます:
 * 例 ->with(['brand' => ['name' => 'Salon X', 'colors' => ['main' => '#000' ...]]])
 */
$brand = $brand ?? [];

$brandName    = $brand['name']    ?? ($appName ?? config('app.name', 'Lash Brow Ohana'));
$brandTagline = $brand['tagline'] ?? '眉・まつげ専門サロン｜市原市';

// フッター表示（必要なら店舗に合わせて調整）
$brandFooterName = $brand['footer_name'] ?? 'Lash Brow Ohana（ラッシュブロウ オハナ）';
$brandFooterAddr = $brand['footer_addr'] ?? '千葉県市原市';

// ロゴ（任意・絶対URL推奨）※メールクライアントで画像ブロックされることはあります
$brandLogoUrl = $brand['logo_url'] ?? null;

// 色（メールは CSS 変数が使えないことが多いので“固定値”で扱います）
$colors = $brand['colors'] ?? [];
$colorMain   = $colors['main']   ?? '#2F4F3E';
$colorAccent = $colors['accent'] ?? '#CDAF63';
$colorBg     = $colors['bg']     ?? '#F1F1EF';
$colorText   = $colors['text']   ?? '#3A2F29';
$colorBoxBg  = $colors['box_bg'] ?? '#F7F6F2';

// 罫線・薄文字
$colorBorder     = $colors['border']     ?? 'rgba(0,0,0,0.10)';
$colorSubText    = $colors['sub_text']   ?? 'rgba(0,0,0,0.60)';
$colorSoftBorder = $colors['soft_border']?? 'rgba(0,0,0,0.06)';
@endphp
