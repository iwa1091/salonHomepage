@extends('layout.app')

@section('title', 'ご予約・お問い合わせ')

@section('styles')
    <link href="{{ asset('css/contact.css') }}" rel="stylesheet">
@endsection

@section('content')

<div class="contact-page">
<div class="contact-container">
<!-- ヘッダー -->
<div class="page-header">
<h1>ご予約・お問い合わせ</h1>
<p>
ご予約やご質問など、お気軽にお問い合わせください。
お客様に最適なプランをご提案させていただきます。
</p>
</div>

    <div class="contact-layout">
        <!-- お問い合わせフォーム -->
        <div class="form-section">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">ご予約フォーム</h2>
                    <p class="card-subtitle">
                        必要事項をご記入の上、送信してください。
                    </p>
                </div>
                <div class="card-content">
                    <form method="POST" action="{{ route('contact.send') }}" class="contact-form">
                        @csrf
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="name">お名前 *</label>
                                <input type="text" id="name" name="name" placeholder="山田 太郎" required class="input-field" />
                            </div>
                            <div class="form-field">
                                <label for="phone">電話番号 *</label>
                                <input type="tel" id="phone" name="phone" placeholder="090-1234-5678" required class="input-field" />
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="email">メールアドレス *</label>
                            <input type="email" id="email" name="email" placeholder="example@email.com" required class="input-field" />
                        </div>

                        <div class="form-field">
                            <label for="service">ご希望のメニュー *</label>
                            <select id="service" name="service" required class="select-field">
                                <option value="" disabled selected>メニューを選択してください</option>
                                <option value="ナチュラルエクステ">ナチュラルエクステ</option>
                                <option value="ボリュームエクステ">ボリュームエクステ</option>
                                <option value="カラーエクステ">カラーエクステ</option>
                                <option value="眉毛ワックス脱毛">眉毛ワックス脱毛</option>
                                <option value="眉毛エクステ">眉毛エクステ</option>
                                <option value="眉ティント">眉ティント</option>
                                <option value="まつげ＋眉セット">まつげ＋眉セット</option>
                                <option value="フルメンテナンスセット">フルメンテナンスセット</option>
                                <option value="商品購入のみ">商品購入のみ</option>
                                <option value="その他・相談">その他・相談</option>
                            </select>
                        </div>

                        <div class="form-grid">
                            <div class="form-field">
                                <label for="date">ご希望日 *</label>
                                <input type="date" id="date" name="date" min="{{ date('Y-m-d') }}" required class="input-field" />
                            </div>
                            <div class="form-field">
                                <label for="time">ご希望時間</label>
                                <select id="time" name="time" class="select-field">
                                    <option value="" disabled selected>時間を選択</option>
                                    <option value="10:00">10:00</option>
                                    <option value="10:30">10:30</option>
                                    <option value="11:00">11:00</option>
                                    <option value="11:30">11:30</option>
                                    <option value="12:00">12:00</option>
                                    <option value="12:30">12:30</option>
                                    <option value="13:30">13:30</option>
                                    <option value="14:00">14:00</option>
                                    <option value="14:30">14:30</option>
                                    <option value="15:00">15:00</option>
                                    <option value="15:30">15:30</option>
                                    <option value="16:00">16:00</option>
                                    <option value="16:30">16:30</option>
                                    <option value="17:00">17:00</option>
                                    <option value="17:30">17:30</option>
                                    <option value="18:00">18:00</option>
                                    <option value="18:30">18:30</option>
                                    <option value="19:00">19:00</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="message">ご質問・ご要望</label>
                            <textarea id="message" name="message" placeholder="アレルギーの有無、ご質問、ご要望などございましたらお書きください" rows="4" class="textarea-field"></textarea>
                        </div>

                        <button type="submit" class="submit-button">
                            予約を申し込む
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- サイドバー -->
        <div class="sidebar">
            <!-- サロン情報 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">サロン情報</h3>
                </div>
                <div class="card-content contact-info">
                    <div class="contact-item">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8c0 4.5-6 9-6 9s-6-4.5-6-9a6 6 0 0 1 12 0z"/><circle cx="12" cy="8" r="2"/></svg>
                        </span>
                        <div>
                            <p class="info-label">住所</p>
                            <p class="info-text">
                                〒123-4567<br />
                                東京都渋谷区●●●1-2-3<br />
                                ●●●ビル 2F
                            </p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2.08L16 21.95A19.95 19.95 0 0 1 2.05 6L2.08 4.18A2 2 0 0 1 4.18 2h3a2 2 0 0 1 2 2.18A15.96 15.96 0 0 0 14.5 14.5A15.96 15.96 0 0 0 19.82 19.82a2 2 0 0 1 2.18 2.18z"/></svg>
                        </span>
                        <div>
                            <p class="info-label">電話番号</p>
                            <p class="info-text">03-1234-5678</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6L12 13L2 6"/></svg>
                        </span>
                        <div>
                            <p class="info-label">メール</p>
                            <p class="info-text">info@lash-brow-ohana.com</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.5" y2="6.5"/></svg>
                        </span>
                        <div>
                            <p class="info-label">Instagram</p>
                            <p class="info-text">@lash_brow_ohana</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 営業時間 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title with-icon">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        </span>
                        営業時間
                    </h3>
                </div>
                <div class="card-content">
                    <div class="hours-list">
                        <div class="hours-item">
                            <span class="day">月曜日</span>
                            <span class="time">定休日</span>
                        </div>
                        <div class="hours-item">
                            <span class="day">火〜金</span>
                            <span class="time">10:00〜19:00</span>
                        </div>
                        <div class="hours-item">
                            <span class="day">土曜日</span>
                            <span class="time">10:00〜18:00</span>
                        </div>
                        <div class="hours-item">
                            <span class="day">日曜日</span>
                            <span class="time">10:00〜17:00</span>
                        </div>
                    </div>
                    <div class="notice-box">
                        <p>※ 最終受付は営業終了時間の1時間前となります</p>
                    </div>
                </div>
            </div>

            <!-- お急ぎの方 -->
            <div class="quick-contact-card">
                <div class="quick-contact-content">
                    <h3>お急ぎの方は</h3>
                    <p>
                        お電話でのご予約も承っております
                    </p>
                    <a href="tel:03-1234-5678" class="phone-button">
                        <span class="icon-small">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2.08L16 21.95A19.95 19.95 0 0 1 2.05 6L2.08 4.18A2 2 0 0 1 4.18 2h3a2 2 0 0 1 2 2.18A15.96 15.96 0 0 0 14.5 14.5A15.96 15.96 0 0 0 19.82 19.82a2 2 0 0 1 2.18 2.18z"/></svg>
                        </span>
                        電話で予約
                    </a>
                </div>
            </div>

            <!-- ご予約について -->
            <div class="booking-notice-card">
                <div class="booking-notice-content">
                    <h3>ご予約について</h3>
                    <div class="notice-list">
                        <p>• ご予約確定後、確認のご連絡をいたします</p>
                        <p>• キャンセルは前日までにお願いします</p>
                        <p>• 当日キャンセルはキャンセル料が発生する場合があります</p>
                        <p>• 遅刻の場合、施術時間が短くなる可能性があります</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
@endsection