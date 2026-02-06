<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', $brandName ?? 'Mail')</title>

  @include('emails.partials.brand-config')
  @include('emails.partials.mail-style')
</head>
<body style="margin:0; padding:0; background: {{ $colorBg }};">
  {{-- Preheader（受信箱プレビュー） --}}
  <div class="mail-preheader">@yield('preheader', '')</div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="mail-wrap" style="width:100%; background: {{ $colorBg }}; padding: 24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
               class="mail-container"
               style="width:100%; max-width:640px; background:#ffffff; border:1px solid {{ $colorBorder }}; border-radius:12px; overflow:hidden;">
          {{-- Header --}}
          <tr>
            <td class="mail-header" style="background: {{ $colorMain }}; color:#ffffff; text-align:center; padding:18px 16px;">
              @if(!empty($brandLogoUrl))
                <img src="{{ $brandLogoUrl }}" width="140" alt="{{ $brandName }}" class="mail-logo" style="display:block; margin:0 auto 10px; border:0;">
              @endif

              <h1 class="mail-header-title" style="margin:0; font-size:18px; font-weight:700; letter-spacing:.04em;">
                {{ $brandName }}
              </h1>

              @if(!empty($brandTagline))
                <p class="mail-header-tagline" style="margin:4px 0 0; font-size:12px; color: rgba(255,255,255,0.85);">
                  {{ $brandTagline }}
                </p>
              @endif
            </td>
          </tr>

          {{-- Body --}}
          <tr>
            <td class="mail-body" style="padding:22px 22px 18px;">
              @yield('content')
            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td class="mail-footer" style="text-align:center; font-size:12px; color: {{ $colorSubText }}; padding:14px 16px; border-top:1px solid {{ $colorSoftBorder }}; background:#ffffff;">
              @yield('footer')
              @hasSection('footer')
              @else
                &copy; {{ date('Y') }} {{ $brandName }}<br>
                {{ $brandFooterName }} / {{ $brandFooterAddr }}
              @endif
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
