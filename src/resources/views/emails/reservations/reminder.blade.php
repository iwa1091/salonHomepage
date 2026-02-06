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

@section('title', 'ご予約リマインド')
@section('preheader', "ご来店日が{$daysBefore}日前となりました。")

@section('content')
  <p>{{ $reservation->name }} 様</p>

  <p>
    いつもご利用いただきありがとうございます。<br>
    眉・まつげ専門サロン「{{ $brandName }}」でございます。
  </p>

  <p>
    ご予約いただいておりますご来店日が、<strong>{{ $daysBefore }}日前</strong>となりましたのでご案内いたします。
  </p>

  <hr class="mail-divider">

  <div class="mail-box">
    <ul class="mail-list">
      <li><strong>ご予約内容</strong></li>
      <li><strong>日時：</strong>{{ $dateText }} {{ $timeText }}〜</li>
      <li><strong>メニュー：</strong>{{ $reservation->service->name ?? 'ご予約メニュー' }}</li>
      <li><strong>ご予約番号：</strong>{{ $reservation->reservation_code ?? '－' }}</li>
    </ul>
  </div>

  <p>
    当日は、ご予約時刻の5分前を目安にご来店いただけますとスムーズにご案内が可能です。<br>
    もしご都合が悪くなった場合や、ご予約内容の変更をご希望の際は、お早めにご連絡いただけますと幸いです。
  </p>

  <p>
    それでは、{{ $dateText }}のご来店を心よりお待ちしております。
  </p>

  <p class="mail-note">
    {{ $brandFooterName }}<br>
    {{ $brandFooterAddr }}
  </p>
@endsection
