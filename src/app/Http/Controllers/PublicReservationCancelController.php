<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Mail\AdminReservationCanceledMail;
use App\Mail\UserReservationCanceledMail;

class PublicReservationCancelController extends Controller
{
    public function show(Request $request, Reservation $reservation)
    {
        $reservation->load('service');

        $title = 'キャンセル確認 | Lash Brow Ohana';
        $isCanceled = ($reservation->status === 'canceled');

        $name = e($reservation->name ?? '');
        $date = e($reservation->date ?? '');
        $time = $reservation->start_time
            ? e(\Carbon\Carbon::parse($reservation->start_time)->format('H:i'))
            : '';
        $serviceName = e($reservation->service->name ?? '不明');

        $action = URL::signedRoute('reservations.public.cancel.perform', ['reservation' => $reservation->id]);

        $csrf = csrf_token();
        $home = url('/');

        $bodyMessage = $isCanceled
            ? 'この予約は既にキャンセル済みです。'
            : '以下の予約をキャンセルします。よろしいですか？';

        $buttonHtml = $isCanceled
            ? '<a href="' . $home . '" style="display:inline-block; padding:12px 16px; border-radius:9999px; background:#CDAF63; color:#fff; text-decoration:none; font-weight:700;">トップへ戻る</a>'
            : '
                <form method="POST" action="' . $action . '" style="margin:0;">
                    <input type="hidden" name="_token" value="' . $csrf . '">
                    <button type="submit" style="width:100%; padding:12px 16px; border-radius:9999px; background:#CDAF63; color:#fff; border:1px solid #CDAF63; font-weight:700; cursor:pointer;">
                        この予約をキャンセルする
                    </button>
                </form>
                <div style="height:10px;"></div>
                <a href="' . $home . '" style="display:inline-block; width:100%; text-align:center; padding:12px 16px; border-radius:9999px; background:#fff; color:#3A2F29; border:1px solid rgba(0,0,0,0.18); text-decoration:none; font-weight:700;">
                    キャンセルしない（トップへ）
                </a>
            ';

        $html = '<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' . e($title) . '</title>
</head>
<body style="margin:0; padding:0; background:#F1F1EF; color:#3A2F29; font-family:-apple-system, BlinkMacSystemFont, \'Hiragino Kaku Gothic ProN\', Meiryo, Arial, sans-serif; line-height:1.7;">
<div style="max-width:720px; margin:24px auto; padding:0 16px;">
  <div style="background:#fff; border:1px solid rgba(0,0,0,0.10); border-radius:12px; overflow:hidden; box-shadow:0 10px 20px rgba(0,0,0,0.08);">
    <div style="background:#2F4F3E; color:#fff; padding:16px 18px;">
      <div style="font-weight:700; letter-spacing:.03em;">Lash Brow Ohana</div>
    </div>
    <div style="padding:18px;">
      <p style="margin:0 0 12px 0;">' . e($bodyMessage) . '</p>

      <div style="background:#F7F6F2; border:1px solid rgba(0,0,0,0.08); border-radius:10px; padding:14px;">
        <div style="margin:6px 0;"><strong>お名前：</strong>' . $name . '</div>
        <div style="margin:6px 0;"><strong>日時：</strong>' . $date . ' ' . $time . '</div>
        <div style="margin:6px 0;"><strong>メニュー：</strong>' . $serviceName . '</div>
      </div>

      <div style="margin-top:14px;">' . $buttonHtml . '</div>

      <p style="margin:14px 0 0 0; font-size:12.5px; color:rgba(0,0,0,0.65);">
        ※ リンクが無効の場合や操作できない場合は、お手数ですが店舗へご連絡ください。
      </p>
    </div>
    <div style="padding:14px 18px; border-top:1px solid rgba(0,0,0,0.06); color:rgba(0,0,0,0.60); font-size:12px; text-align:center;">
      &copy; ' . date('Y') . ' Lash Brow Ohana
    </div>
  </div>
</div>
</body>
</html>';

        return response($html);
    }

    public function perform(Request $request, Reservation $reservation)
    {
        $reservation->load('service');

        $alreadyCanceled = ($reservation->status === 'canceled');

        if (!$alreadyCanceled) {
            $reservation->update(['status' => 'canceled']);

            try {
                $adminEmail = env('MAIL_ADMIN_ADDRESS', 'admin@lash-brow-ohana.local');
                Mail::to($adminEmail)->send(new AdminReservationCanceledMail($reservation));

                if (!empty($reservation->email)) {
                    Mail::to($reservation->email)->send(new UserReservationCanceledMail($reservation));
                }
            } catch (\Throwable $e) { // ✅ Exception → Throwable
                Log::error('[公開キャンセル通知メール送信エラー]', [
                    'reservation_id' => $reservation->id,
                    'email' => $reservation->email ?? '不明',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $home = url('/');
        $message = $alreadyCanceled ? 'この予約は既にキャンセル済みです。' : '予約をキャンセルしました。';

        $html = '<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>キャンセル結果 | Lash Brow Ohana</title>
</head>
<body style="margin:0; padding:0; background:#F1F1EF; color:#3A2F29; font-family:-apple-system, BlinkMacSystemFont, \'Hiragino Kaku Gothic ProN\', Meiryo, Arial, sans-serif; line-height:1.7;">
<div style="max-width:720px; margin:24px auto; padding:0 16px;">
  <div style="background:#fff; border:1px solid rgba(0,0,0,0.10); border-radius:12px; overflow:hidden; box-shadow:0 10px 20px rgba(0,0,0,0.08);">
    <div style="background:#2F4F3E; color:#fff; padding:16px 18px;">
      <div style="font-weight:700; letter-spacing:.03em;">Lash Brow Ohana</div>
    </div>
    <div style="padding:18px;">
      <p style="margin:0 0 12px 0;">' . e($message) . '</p>
      <a href="' . $home . '" style="display:inline-block; padding:12px 16px; border-radius:9999px; background:#CDAF63; color:#fff; text-decoration:none; font-weight:700;">
        トップへ戻る
      </a>
    </div>
    <div style="padding:14px 18px; border-top:1px solid rgba(0,0,0,0.06); color:rgba(0,0,0,0.60); font-size:12px; text-align:center;">
      &copy; ' . date('Y') . ' Lash Brow Ohana
    </div>
  </div>
</div>
</body>
</html>';

        return response($html);
    }
}
