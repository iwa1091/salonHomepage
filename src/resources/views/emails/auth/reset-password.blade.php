<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>パスワード再設定のご案内</title>
</head>
<body style="margin:0; padding:0; background-color:{{ $colorBg }};">
@include('emails.partials.brand-config')
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center" style="padding:24px 12px;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                   style="max-width:600px; background-color:#ffffff; border-radius:12px;
                          overflow:hidden; box-shadow:0 8px 18px rgba(0,0,0,0.15);">
                {{-- ヘッダー --}}
                <tr>
                    <td style="background-color:{{ $colorMain }}; padding:18px 24px;">
                        <h1 style="margin:0; font-size:20px; color:#fdfaf5;
                                   font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Noto Sans JP',sans-serif;">
                            {{ $brandName }}
                        </h1>
                        <p style="margin:4px 0 0; font-size:12px; color:#e7dac6;">
                            {{ $brandTagline }}
                        </p>
                    </td>
                </tr>

                {{-- 本文 --}}
                <tr>
                    <td style="padding:24px 24px 16px;
                               font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Noto Sans JP',sans-serif;
                               color:#333333; font-size:14px; line-height:1.8;">
                        <p style="margin:0 0 12px;">
                            {{ $user->name ?? 'お客様' }} 様
                        </p>

                        <p style="margin:0 0 12px;">
                            いつも「{{ $brandName }}」をご利用いただき、誠にありがとうございます。
                        </p>

                        <p style="margin:0 0 12px;">
                            パスワード再設定のご依頼を受け付けました。<br>
                            下のボタンをクリックし、新しいパスワードの設定を完了してください。
                        </p>

                        <p style="margin:0 0 12px; font-size:12px; color:#777777;">
                            ※本メール送信から一定時間が経過すると、リンクは無効になります。<br>
                            その場合は、お手数ですが再度「パスワード再発行」画面からお手続きをお願いします。
                        </p>

                        {{-- ボタン部分 --}}
                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                            <tr>
                                <td align="center">
                                    <a href="{{ $url }}"
                                       style="display:inline-block; padding:12px 28px;
                                              background-color:{{ $colorAccent }}; color:#ffffff;
                                              text-decoration:none; border-radius:999px;
                                              font-size:14px; font-weight:bold;">
                                        パスワードを再設定する
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 12px; font-size:12px; color:#777777;">
                            ※このメールに心当たりがない場合は、このメールは破棄してください。<br>
                            第三者による誤った操作の可能性がありますが、リンクを開かない限りパスワードは変更されません。
                        </p>
                    </td>
                </tr>

                {{-- フッター --}}
                <tr>
                    <td style="padding:16px 24px 20px; background-color:{{ $colorBoxBg }};
                               font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Noto Sans JP',sans-serif;
                               font-size:11px; color:#777777;">
                        <p style="margin:0 0 4px;">
                            {{ $brandFooterName }}
                        </p>
                        <p style="margin:0;">
                            {{ $brandFooterAddr }}｜{{ $brandTagline }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
