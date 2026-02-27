// /resources/js/Pages/Mypage/Index.jsx
import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { useEffect, useMemo, useState } from "react"; // ✅ 追加：予約フォームの○×制御など

// モジュール化した CSS をインポート
import "../../../css/pages/admin/mypage/index.css";

// ✅ 追加：マイページ専用CSS（はみ出し防止 / date押しやすさ改善）
import "../../../css/pages/mypage/mypage.css";

// ✅ 追加：時間グリッド（○×表示）用のCSSを流用（reservation- 系クラスのみ使用）
import "../../../css/pages/reservation/reservation-form.css";

/**
 * 15分刻みで時間スロットを生成（ReservationForm.jsx と同等）
 */
function generateTimeSlots(start, end, interval = 15) {
    const slots = [];
    if (!start || !end) return slots;

    let [hour, minute] = String(start).split(":").map(Number);
    const [endHour, endMinute] = String(end).split(":").map(Number);

    while (hour < endHour || (hour === endHour && minute <= endMinute)) {
        const time = `${String(hour).padStart(2, "0")}:${String(minute).padStart(2, "0")}`;
        slots.push(time);

        minute += interval;
        if (minute >= 60) {
            hour += 1;
            minute -= 60;
        }
    }
    return slots;
}

/**
 * YYYY-MM-DD をローカルDateにする（new Date("YYYY-MM-DD")のズレ回避）
 */
function parseYmdToLocalDate(ymd) {
    const s = String(ymd || "");
    const [y, m, d] = s.split("-").map((v) => Number(v));
    if (!y || !m || !d) return new Date();
    return new Date(y, m - 1, d, 0, 0, 0);
}

/**
 * BusinessHour::getWeekOfMonth(Carbon) と同じ計算
 * PHP: ceil((day + firstDay->dayOfWeekIso - 1) / 7)
 */
function getWeekOfMonthLikeLaravel(d) {
    const day = d.getDate();
    const first = new Date(d.getFullYear(), d.getMonth(), 1);

    // JS: Sun=0..Sat=6 -> ISO: Mon=1..Sun=7
    const firstIso = first.getDay() === 0 ? 7 : first.getDay();
    return Math.ceil((day + firstIso - 1) / 7);
}

/**
 * 現在時刻から12時間以降かどうか（UI判定）
 */
function isAfter12HoursFromNow(selectedDate, timeHHmm) {
    if (!timeHHmm) return false;
    const [hh, mm] = String(timeHHmm).split(":").map((v) => Number(v));
    const dt = new Date(
        selectedDate.getFullYear(),
        selectedDate.getMonth(),
        selectedDate.getDate(),
        hh,
        mm,
        0
    );
    const limit = new Date(Date.now() + 12 * 60 * 60 * 1000);
    return dt.getTime() >= limit.getTime();
}

export default function Mypage({
    user,
    pastReservations,
    pastOrders,
    upcomingReservations,
}) {
    // Inertia から flash メッセージ & バリデーションエラーを取得
    const { flash, errors } = usePage().props;

    // 予約番号紐付けフォーム用の useForm
    const { data, setData, post, processing } = useForm({
        reservation_code: "",
    });

    // ✅ キャンセル用（別フォーム）
    const cancelForm = useForm({ cancel_reason: "" });

    // ✅ マイページから予約（別フォーム）
    const todayYmd = useMemo(() => {
        const d = new Date();
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, "0");
        const day = String(d.getDate()).padStart(2, "0");
        return `${y}-${m}-${day}`;
    }, []);

    const reserveForm = useForm({
        service_id: "",
        date: todayYmd,
        start_time: "",
        phone: user?.phone ?? "",
        notes: "",
    });

    const [reserveOpen, setReserveOpen] = useState(true);
    const [linkOpen, setLinkOpen] = useState(false);

    // ✅ 追加：マイページ予約の○×表示用 state
    const [bhLoading, setBhLoading] = useState(false);
    const [businessHours, setBusinessHours] = useState([]); // /api/business-hours/weekly
    const [availableTimes, setAvailableTimes] = useState([]); // 営業時間から生成した全枠
    const [availableSlots, setAvailableSlots] = useState([]); // APIの空き枠（○になる開始時刻）
    const [availabilityLoading, setAvailabilityLoading] = useState(false);
    const [reserveUiMessage, setReserveUiMessage] = useState("");

    const handleLinkSubmit = (e) => {
        e.preventDefault();
        // セッションの CSRF を自動で付けて POST
        post("/mypage/link-reservation");
    };

    // ✅ キャンセル（confirm.blade.php へ遷移）
    const handleCancel = (reservationId) => {
        const ok = window.confirm(
            "キャンセル確認画面へ進みますか？\n（キャンセル理由の入力後に確定します）"
        );
        if (!ok) return;

        window.location.href = `/mypage/reservations/${reservationId}/cancel/confirm`;
    };

    /* =========================================================
     * ✅ 表示用：並び替え / 件数制限 / キャンセル除外
     * ========================================================= */

    // ✅ 予約中：キャンセル済みを表示しない（status の表記ゆれも吸収）
    const upcomingReservationsVisible = useMemo(() => {
        const list = Array.isArray(upcomingReservations)
            ? upcomingReservations
            : [];

        const canceledSet = new Set([
            "canceled",
            "cancelled",
            "cancel",
            "canceled_by_user",
            "canceled_by_admin",
        ]);

        return list
            .filter((r) => !canceledSet.has(String(r?.status ?? "")))
            .sort((a, b) => {
                const ak = `${String(a?.date ?? "")} ${String(a?.start_time ?? "").slice(0, 5)}`;
                const bk = `${String(b?.date ?? "")} ${String(b?.start_time ?? "").slice(0, 5)}`;
                return ak.localeCompare(bk);
            });
    }, [upcomingReservations]);

    // ✅ 過去の予約：新しい日時が上（降順） + 最大5件
    const pastReservationsVisible = useMemo(() => {
        const list = Array.isArray(pastReservations) ? pastReservations : [];

        // date: "YYYY-MM-DD" / start_time: "HH:mm" or "HH:mm:ss" を想定
        const toKey = (r) => {
            const d = String(r?.date ?? "");
            const t = String(r?.start_time ?? "").slice(0, 5);
            return `${d} ${t}`;
        };

        return [...list]
            .sort((a, b) => toKey(b).localeCompare(toKey(a)))
            .slice(0, 5);
    }, [pastReservations]);

    // ✅ 購入履歴：新しい日時が上（降順） + 最大5件
    const pastOrdersVisible = useMemo(() => {
        const list = Array.isArray(pastOrders) ? pastOrders : [];

        return [...list]
            .sort((a, b) => {
                const at = a?.ordered_at ? new Date(a.ordered_at).getTime() : 0;
                const bt = b?.ordered_at ? new Date(b.ordered_at).getTime() : 0;
                return bt - at;
            })
            .slice(0, 5);
    }, [pastOrders]);

    // ✅ マイページ予約フォームの選択肢（past/upcoming からユニークに作成）
    const serviceOptions = useMemo(() => {
        const src = [
            ...(Array.isArray(pastReservations) ? pastReservations : []),
            ...(Array.isArray(upcomingReservations) ? upcomingReservations : []),
        ];

        const map = new Map();
        src.forEach((r) => {
            const s = r?.service;
            if (!s?.id) return;
            if (!map.has(String(s.id))) {
                map.set(String(s.id), { id: String(s.id), name: s?.name ?? "未設定" });
            }
        });

        return Array.from(map.values()).sort((a, b) => a.name.localeCompare(b.name));
    }, [pastReservations, upcomingReservations]);

    /* =========================================================
     * ✅ マイページ予約：営業時間 & 空き枠（○×）表示
     * ========================================================= */

    const reserveDateObj = useMemo(() => {
        return parseYmdToLocalDate(reserveForm.data.date);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [reserveForm.data.date]);

    // 営業時間データ取得（選択月に追従）
    useEffect(() => {
        const year = reserveDateObj.getFullYear();
        const month = reserveDateObj.getMonth() + 1;

        const controller = new AbortController();

        async function fetchBusinessHours() {
            setBhLoading(true);
            try {
                const res = await fetch(
                    `/api/business-hours/weekly?year=${encodeURIComponent(year)}&month=${encodeURIComponent(month)}`,
                    { signal: controller.signal }
                );
                const data = await res.json().catch(() => ([]));
                if (!res.ok) {
                    setBusinessHours([]);
                    return;
                }
                setBusinessHours(Array.isArray(data) ? data : []);
            } catch (err) {
                if (err?.name !== "AbortError") {
                    setBusinessHours([]);
                }
            } finally {
                setBhLoading(false);
            }
        }

        fetchBusinessHours();
        return () => controller.abort();
    }, [reserveDateObj]);

    // 選択日から「営業時間の全枠（15分）」を生成
    useEffect(() => {
        setReserveUiMessage(""); // 表示メッセージは都度クリア

        if (!Array.isArray(businessHours) || businessHours.length === 0) {
            setAvailableTimes([]);
            // service_id や空き枠は別effectで処理するが、時間は一旦空にしておく
            reserveForm.setData("start_time", "");
            return;
        }

        const dayOfWeekNames = ["日", "月", "火", "水", "木", "金", "土"];
        const selectedDay = dayOfWeekNames[reserveDateObj.getDay()];
        const weekOfMonth = getWeekOfMonthLikeLaravel(reserveDateObj);

        const hourInfo = businessHours.find(
            (h) => Number(h.week_of_month) === Number(weekOfMonth) && h.day_of_week === selectedDay
        );

        if (!hourInfo || hourInfo.is_closed) {
            setAvailableTimes([]);
            reserveForm.setData("start_time", "");
            return;
        }

        const slots = generateTimeSlots(hourInfo.open_time, hourInfo.close_time, 15);
        setAvailableTimes(slots);

        // 営業時間が変わったら start_time は一旦クリア（選択の整合性担保）
        reserveForm.setData("start_time", "");
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [businessHours, reserveForm.data.date]);

    // 空き枠（○になる開始時刻）取得：/api/reservations/check（BusinessHour基準 + 12時間ルール込み）
    useEffect(() => {
        const serviceId = reserveForm.data.service_id;
        const ymd = reserveForm.data.date;

        // メニュー未選択なら空き判定しない
        if (!serviceId) {
            setAvailableSlots([]);
            return;
        }

        const controller = new AbortController();

        async function fetchAvailability() {
            setAvailabilityLoading(true);
            try {
                const res = await fetch(
                    `/api/reservations/check?date=${encodeURIComponent(ymd)}&service_id=${encodeURIComponent(serviceId)}`,
                    { signal: controller.signal }
                );
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    setAvailableSlots([]);
                    return;
                }
                const slots = Array.isArray(data.available_slots) ? data.available_slots : [];
                setAvailableSlots(slots);

                // もし選択中の時間が空き枠から外れたら解除
                if (reserveForm.data.start_time) {
                    const starts = new Set(slots.map((s) => s.start));
                    if (!starts.has(String(reserveForm.data.start_time).slice(0, 5))) {
                        reserveForm.setData("start_time", "");
                    }
                }
            } catch (err) {
                if (err?.name !== "AbortError") {
                    setAvailableSlots([]);
                }
            } finally {
                setAvailabilityLoading(false);
            }
        }

        fetchAvailability();

        return () => controller.abort();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [reserveForm.data.date, reserveForm.data.service_id]);

    const availableStartSet = useMemo(() => {
        return new Set((availableSlots || []).map((s) => String(s.start)));
    }, [availableSlots]);

    // ✅ マイページから予約送信（user_id はサーバー側で必ず紐付く）
    const handleReserveSubmit = (e) => {
        e.preventDefault();
        setReserveUiMessage("");

        const serviceId = String(reserveForm.data.service_id || "");
        const start = String(reserveForm.data.start_time || "").slice(0, 5);

        if (!serviceId) {
            setReserveUiMessage("メニューを選択してください。");
            return;
        }

        if (!reserveForm.data.date) {
            setReserveUiMessage("日付を選択してください。");
            return;
        }

        if (!start) {
            setReserveUiMessage("時間を選択してください。");
            return;
        }

        // 営業時間が無い（休業）なら送信前に止める
        if (!Array.isArray(availableTimes) || availableTimes.length === 0) {
            setReserveUiMessage("この日は休業日または営業時間外のため予約できません。");
            return;
        }

        // 12時間ルール（API側でも弾くが、UI上の最終チェック）
        if (!isAfter12HoursFromNow(reserveDateObj, start)) {
            setReserveUiMessage("ご予約は現在時刻から12時間以降の枠のみ受付可能です。");
            return;
        }

        // 空き枠チェック（API側でも弾くが、UI上の最終チェック）
        if (!availableStartSet.has(start)) {
            setReserveUiMessage("選択された時間枠は予約できません（空き枠ではありません）。");
            return;
        }

        // start_time は "HH:mm" で送る（UserReservationController/storeFromMypage の date_format:H:i と整合）
        reserveForm.setData("start_time", start);

        reserveForm.post("/mypage/reservations/store", {
            preserveScroll: true,
            onSuccess: () => {
                // start_time / notes はクリア（service_id と phone は残す）
                reserveForm.setData("start_time", "");
                reserveForm.setData("notes", "");
                setReserveUiMessage("");
                setReserveOpen(false);
            },
            onError: () => {
                // バリデーションは errors に入るので、ここではUIメッセージを過剰に出さない
            },
        });
    };

    // ✅ 予約フォーム操作（service_id/date変更で start_time をクリア）
    const handleReserveChange = (key, value) => {
        if (key === "service_id" || key === "date") {
            reserveForm.setData("start_time", "");
        }
        reserveForm.setData(key, value);
        setReserveUiMessage("");
    };

    // ✅ errors は string/配列どちらでも来る可能性があるので吸収
    const firstErrorText = (v) => {
        if (!v) return "";
        if (Array.isArray(v)) return String(v[0] ?? "");
        return String(v);
    };

    const reserveErrorsText = useMemo(() => {
        return (
            firstErrorText(errors?.service_id) ||
            firstErrorText(errors?.date) ||
            firstErrorText(errors?.start_time) ||
            firstErrorText(errors?.phone) ||
            ""
        );
    }, [errors]);

    return (
        <div
            className="mypage-root mypage-container"
            style={{
                padding: 0,
                backgroundColor: "transparent",
                minHeight: "auto",
            }}
        >
            <Head title="マイページ" />

            {/* -----------------------------------
                ページヘッダー
            ----------------------------------- */}
            <header className="mypage-header">
                <h1 className="mypage-header-title">
                    ようこそ、{user?.name} さん
                </h1>
                <p className="mypage-header-subtitle">
                    いつもご利用いただきありがとうございます。次回のご来店をお待ちしております。
                </p>
            </header>

            {/* -----------------------------------
                メインコンテンツ
            ----------------------------------- */}
            <main className="mypage-main">
                {/* ================================
                    予約中
                ================================= */}
                <section className="mypage-section-card">
                    <h2 className="mypage-section-title">
                        📅 予約中のメニュー
                    </h2>

                    {upcomingReservationsVisible?.length ? (
                        upcomingReservationsVisible.map((res) => (
                            <div
                                key={res.id}
                                className="mypage-item-card"
                            >
                                <p className="mypage-item-title">
                                    {res.service?.name}
                                </p>
                                <p className="mypage-item-meta">
                                    来店日：
                                    {res.date
                                        ? new Date(
                                            res.date
                                        ).toLocaleDateString()
                                        : "-"}
                                </p>
                                <p className="mypage-item-meta">
                                    開始時間：{res.start_time ? String(res.start_time).slice(0, 5) : "-"}
                                </p>

                                {/* ✅ キャンセル導線（予約中カード内） */}
                                <div className="mypage-item-actions">
                                    <button
                                        type="button"
                                        className="mypage-danger-button"
                                        onClick={() => handleCancel(res.id)}
                                        disabled={cancelForm.processing}
                                    >
                                        {cancelForm.processing ? "処理中..." : "キャンセルする"}
                                    </button>
                                </div>
                            </div>
                        ))
                    ) : (
                        <p className="mypage-empty-text">
                            現在予約はありません。
                        </p>
                    )}
                </section>

                {/* ================================
                    ✅ マイページから予約（user_id 自動紐付け）
                    - 営業時間・空き枠・12時間ルールに沿って ○× 表示
                ================================= */}
                <section className="mypage-section-card">
                    <div className="mypage-section-head">
                        <h2 className="mypage-section-title">
                            🆕 マイページから予約する
                        </h2>

                        <button
                            type="button"
                            className="mypage-inline-link mypage-inline-link--compact"
                            onClick={() => setReserveOpen((v) => !v)}
                        >
                            {reserveOpen ? "閉じる" : "予約フォームを開く"} →
                        </button>
                    </div>

                    {reserveOpen && (
                        <>
                            {/* ✅ フォーム共通メッセージ */}
                            {reserveUiMessage && (
                                <p className="mypage-flash-error">
                                    {reserveUiMessage}
                                </p>
                            )}

                            {/* ✅ バリデーションエラー（予約フォーム） */}
                            {reserveErrorsText && (
                                <p className="mypage-flash-error">
                                    {reserveErrorsText}
                                </p>
                            )}

                            <form
                                onSubmit={handleReserveSubmit}
                                className="mypage-link-form mypage-link-form--reserve"
                            >
                                {/* メニュー */}
                                <label className="mypage-form-label">メニュー（必須）</label>
                                <select
                                    className="mypage-input"
                                    value={reserveForm.data.service_id}
                                    onChange={(e) => handleReserveChange("service_id", e.target.value)}
                                    required
                                    disabled={reserveForm.processing}
                                >
                                    <option value="">メニューを選択してください</option>
                                    {serviceOptions.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.name}
                                        </option>
                                    ))}
                                </select>

                                {/* ✅ 過去予約が無い等で選択肢が空の場合の逃げ道（Blade遷移なので a を使う） */}
                                {serviceOptions.length === 0 && (
                                    <p className="mypage-empty-text">
                                        ※ まだマイページ上で選べるメニューがありません。
                                        <a
                                            href="/menu_price"
                                            className="mypage-inline-link mypage-inline-link--compact"
                                            style={{ marginLeft: "0.5rem" }}
                                        >
                                            メニュー・料金を見る →
                                        </a>
                                    </p>
                                )}

                                {/* 日付（押しやすさ改善：クラス追加 + showPicker） */}
                                <label className="mypage-form-label">ご来店日（必須）</label>
                                <input
                                    type="date"
                                    className="mypage-input mypage-date"
                                    value={reserveForm.data.date}
                                    onClick={(e) => e.currentTarget.showPicker?.()}
                                    onFocus={(e) => e.currentTarget.showPicker?.()}
                                    onChange={(e) => handleReserveChange("date", e.target.value)}
                                    required
                                    disabled={reserveForm.processing}
                                />

                                {/* ✅ 時間：○×グリッド（営業時間 + 空き枠 + 12時間ルール） */}
                                <label className="mypage-form-label">ご希望の時間（必須）</label>
                                <div className="reservation-time-wrapper">
                                    {!reserveForm.data.service_id ? (
                                        <p className="reservation-time-note">
                                            ※ メニューを選択すると空き状況（○×）が表示されます
                                        </p>
                                    ) : bhLoading ? (
                                        <p className="reservation-time-note">
                                            営業時間を確認中...
                                        </p>
                                    ) : availableTimes.length === 0 ? (
                                        <p className="reservation-time-note">
                                            ※ この日は休業日または営業時間外です
                                        </p>
                                    ) : availabilityLoading ? (
                                        <p className="reservation-time-note">
                                            空き状況を確認中...
                                        </p>
                                    ) : (
                                        <div className="reservation-time-grid">
                                            {availableTimes.map((time) => {
                                                const hhmm = String(time).slice(0, 5);
                                                const isAvailable = availableStartSet.has(hhmm);
                                                const ok12h = isAfter12HoursFromNow(reserveDateObj, hhmm);
                                                const canSelect = isAvailable && ok12h;

                                                const selected = String(reserveForm.data.start_time).slice(0, 5) === hhmm;
                                                const statusMark = canSelect ? "○" : "×";

                                                return (
                                                    <button
                                                        type="button"
                                                        key={hhmm}
                                                        onClick={() => {
                                                            if (canSelect) {
                                                                handleReserveChange("start_time", hhmm);
                                                            }
                                                        }}
                                                        disabled={!canSelect || reserveForm.processing}
                                                        className={`reservation-time-button ${selected ? "reservation-time-button--selected" : ""
                                                            } ${(!canSelect || reserveForm.processing) ? "reservation-time-button--disabled" : ""
                                                            }`}
                                                    >
                                                        <span className="reservation-time-label">{hhmm}</span>
                                                        <span className="reservation-time-status">{statusMark}</span>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    )}

                                    {reserveForm.data.start_time && (
                                        <p className="reservation-selected-time">
                                            選択された時間: {String(reserveForm.data.start_time).slice(0, 5)}
                                        </p>
                                    )}
                                </div>

                                {/* 電話番号 */}
                                <label className="mypage-form-label">
                                    電話番号（必須）
                                    <span className="mypage-form-label-note">予約確定後のご連絡先となります</span>
                                </label>
                                <input
                                    type="tel"
                                    className="mypage-input"
                                    value={reserveForm.data.phone}
                                    onChange={(e) => handleReserveChange("phone", e.target.value)}
                                    placeholder="例：090-1234-5678"
                                    required
                                    disabled={reserveForm.processing}
                                />

                                {/* 備考 */}
                                <label className="mypage-form-label">備考（任意）</label>
                                <input
                                    type="text"
                                    className="mypage-input"
                                    value={reserveForm.data.notes}
                                    onChange={(e) => handleReserveChange("notes", e.target.value)}
                                    placeholder="ご要望・アレルギーなどがあればご記入ください"
                                    disabled={reserveForm.processing}
                                />

                                <button
                                    type="submit"
                                    className="mypage-primary-button"
                                    disabled={reserveForm.processing}
                                >
                                    {reserveForm.processing ? "処理中..." : "この内容で予約する"}
                                </button>
                            </form>

                            <p className="mypage-empty-text">
                                ※ この予約は <strong>自動でマイページに紐づく</strong>ため、予約番号の入力は不要です。
                            </p>
                        </>
                    )}
                </section>

                {/* ================================
                    過去の予約
                ================================= */}
                <section className="mypage-section-card">
                    <h2 className="mypage-section-title">
                        🕘 過去のメニュー
                    </h2>

                    {pastReservationsVisible?.length ? (
                        pastReservationsVisible.map((r) => (
                            <div
                                key={r.id}
                                className="mypage-item-card"
                            >
                                <p className="mypage-item-title">
                                    {r.service?.name}
                                </p>
                                <p className="mypage-item-meta">
                                    来店日：
                                    {r.date
                                        ? new Date(
                                            r.date
                                        ).toLocaleDateString()
                                        : "-"}
                                </p>

                                {/* ✅ 過去メニューから再予約（マイページ予約フォームに反映） */}
                                <button
                                    type="button"
                                    className="mypage-inline-link"
                                    onClick={() => {
                                        const sid = r?.service?.id ? String(r.service.id) : "";
                                        if (sid) {
                                            reserveForm.setData("service_id", sid);
                                            setReserveOpen(true);
                                            // 予約フォームを開いた後に、日付/時間を触れるよう上へ誘導
                                            window.scrollTo({ top: 0, behavior: "smooth" });
                                        }
                                    }}
                                >
                                    このメニューで予約する →
                                </button>
                            </div>
                        ))
                    ) : (
                        <p className="mypage-empty-text">
                            過去の予約はありません。
                        </p>
                    )}
                </section>

                {/* ================================
                    購入履歴
                ================================= */}
                <section className="mypage-section-card">
                    <h2 className="mypage-section-title">
                        🛍 購入履歴
                    </h2>

                    {pastOrdersVisible?.length ? (
                        pastOrdersVisible.map((o) => (
                            <div
                                key={o.id}
                                className="mypage-item-card"
                            >
                                <p className="mypage-item-title">
                                    {o.product?.name}
                                </p>
                                <p className="mypage-item-meta">
                                    購入日：
                                    {o.ordered_at
                                        ? new Date(
                                            o.ordered_at
                                        ).toLocaleDateString()
                                        : "-"}
                                </p>

                                <a
                                    href={`/online-store/products/${o.product?.id}`}
                                    className="mypage-inline-link"
                                >
                                    再購入 →
                                </a>
                            </div>
                        ))
                    ) : (
                        <p className="mypage-empty-text">
                            購入履歴はありません。
                        </p>
                    )}
                </section>

                {/* ================================
                    予約番号紐付けフォーム（折りたたみ）
                ================================= */}
                <section className="mypage-section-card">
                    <div className="mypage-section-head">
                        <h2 className="mypage-section-title">
                            🔗 予約番号を紐付ける
                        </h2>
                        <button
                            type="button"
                            className="mypage-inline-link mypage-inline-link--compact"
                            onClick={() => setLinkOpen((v) => !v)}
                        >
                            {linkOpen ? "閉じる" : "開く"} →
                        </button>
                    </div>

                    {linkOpen && (
                        <>
                            {/* 成功メッセージ */}
                            {flash?.success && (
                                <p className="mypage-flash-success">
                                    {flash.success}
                                </p>
                            )}

                            {/* ✅ キャンセル等の汎用メッセージ（UserReservationController の with('message') 対応） */}
                            {flash?.message && (
                                <p className="mypage-flash-info">
                                    {flash.message}
                                </p>
                            )}

                            {/* バリデーションエラー（予約番号） */}
                            {errors?.reservation_code && (
                                <p className="mypage-flash-error">
                                    {firstErrorText(errors.reservation_code)}
                                </p>
                            )}

                            <form
                                onSubmit={handleLinkSubmit}
                                className="mypage-link-form"
                            >
                                <input
                                    type="text"
                                    name="reservation_code"
                                    value={data.reservation_code}
                                    onChange={(e) =>
                                        setData("reservation_code", e.target.value)
                                    }
                                    placeholder="予約番号を入力してください"
                                    className="mypage-input"
                                    required
                                />

                                <button
                                    type="submit"
                                    className="mypage-primary-button"
                                    disabled={processing}
                                >
                                    予約を紐付ける
                                </button>
                            </form>

                        </>
                    )}
                </section>
            </main>

            {/* -----------------------------------
                トップへ戻る（固定ボタン）
            ----------------------------------- */}
            <a href="/" className="mypage-home-fab">
                ⬆ ホームに戻る
            </a>
        </div>
    );
}
