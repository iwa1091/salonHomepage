@php
    use Illuminate\Support\Carbon;

    $dateText = method_exists($reservation, 'getFormattedDateAttribute')
        ? $reservation->formatted_date
        : Carbon::parse($reservation->date)->format('Y年m月d日');

    $timeText = method_exists($reservation, 'getFormattedTimeAttribute')
        ? $reservation->formatted_time
        : Carbon::parse($reservation->start_time)->format('H:i');
@endphp

@extends('emails.layouts.mail')

@section('title', 'ご来店ありがとうございます')
@section('preheader', 'ご来店ありがとうございました。')

@section('content')
  <p>{{ $reservation->name }} 様</p>

  <p>
    先日は「{{ $brandName }}」へご来店いただき、誠にありがとうございました。
  </p>

  <hr class="mail-divider">

  <div class="mail-box">
    <ul class="mail-list">
      <li><strong>ご利用内容</strong></li>
      <li><strong>日時：</strong>{{ $dateText }} {{ $timeText }}〜</li>
      <li><strong>メニュー：</strong>{{ $reservation->service->name ?? 'ご利用メニュー' }}</li>
      <li><strong>ご予約番号：</strong>{{ $reservation->reservation_code ?? '－' }}</li>
    </ul>
  </div>

  @if ($pattern === 'after_3days')
    <p>
      施術後のお肌・眉・まつげの状態はいかがでしょうか。<br>
      気になる点やご不明な点がございましたら、些細なことでもお気軽にご相談ください。
    </p>

    <p>
      今後も{{ $reservation->name }}様の「若々しさと清潔感」を引き出すお手伝いができましたら幸いです。
    </p>
  @elseif ($pattern === 'after_1month')
    <p>
      前回のご来店から、おおよそ<strong>1か月</strong>が経過いたしました。<br>
      そろそろラインの乱れや、眉・まつげのデザインの崩れが気になり始めるタイミングかと思います。
    </p>

    <p>
      当サロンでは、<strong>1か月〜1か月半程度</strong>の周期でのご来店をおすすめしております。<br>
      次回のご予約やメニューのご相談などございましたら、ぜひお気軽にお問い合わせください。
    </p>
  @endif

  <p>
    今後とも「{{ $brandName }}」をどうぞよろしくお願いいたします。
  </p>

  <p class="mail-note">
    {{ $brandFooterName }}<br>
    {{ $brandFooterAddr }}
  </p>
@endsection
