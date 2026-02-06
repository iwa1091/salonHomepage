// /resources/js/Pages/Reservation/ReservationForm.jsx
import { useEffect, useMemo, useState } from "react";
import Calendar from "react-calendar";
import "react-calendar/dist/Calendar.css";
import "../../../css/pages/reservation/reservation-form.css";

/**
 * 15分刻みで時間スロットを生成
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
 * JST(ローカル)日付を YYYY-MM-DD で作る（toISOString() 由来のズレを防ぐ）
 */
function formatDateYMD(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
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
 * 現在時刻から12時間以降かどうか（UI表示用の補助）
 * ※API側でも12時間ルールで弾いているが、表示上の判定にも使用
 */
function isAfter12HoursFromNow(selectedDate, timeHHmm) {
    if (!timeHHmm) return false;
    const [hh, mm] = timeHHmm.split(":").map((v) => Number(v));
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

export default function ReservationForm({ service_id = "" }) {
    const [date, setDate] = useState(new Date());
    const [selectedTime, setSelectedTime] = useState("");

    // ✅ menu_price.blade.php から来る service_id を初期値にする（props優先、なければクエリから拾う）
    const initialServiceId = useMemo(() => {
        const propId =
            service_id !== null && service_id !== undefined && String(service_id).trim() !== ""
                ? String(service_id)
                : "";

        if (propId) return propId;

        try {
            const q = new URLSearchParams(window.location.search).get("service_id");
            return q ? String(q) : "";
        } catch (e) {
            return "";
        }
    }, [service_id]);

    const [formData, setFormData] = useState(() => ({
        name: "",
        phone: "",
        service_id: initialServiceId,
        email: "",
        notes: "",
    }));

    const [services, setServices] = useState([]);
    const [businessHours, setBusinessHours] = useState([]); // 営業時間データ
    const [availableTimes, setAvailableTimes] = useState([]); // 営業時間から生成した全枠（15分刻み）

    // ✅ 追加：APIの空き枠（○になる開始時刻）
    const [availableSlots, setAvailableSlots] = useState([]); // [{start,end}]
    const [availabilityLoading, setAvailabilityLoading] = useState(false);

    const [message, setMessage] = useState("");

    // ✅ 初期service_idが取れたのに formData に入っていない場合だけ補完（保険）
    useEffect(() => {
        if (!formData.service_id && initialServiceId) {
            setFormData((prev) => ({ ...prev, service_id: initialServiceId }));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [initialServiceId]);

    // サービス一覧の取得
    useEffect(() => {
        async function fetchServices() {
            try {
                const res = await fetch("/api/services");
                if (res.ok) {
                    const data = await res.json();
                    setServices(data);
                }
            } catch (err) {
                console.error("サービス一覧の取得に失敗:", err);
            }
        }
        fetchServices();
    }, []);

    // 表示用：選択中サービス
    const selectedService = useMemo(() => {
        if (!formData.service_id) return null;
        return (services || []).find((s) => String(s.id) === String(formData.service_id)) || null;
    }, [services, formData.service_id]);

    // 営業時間の取得（来月のデータも取得できるように修正）
    useEffect(() => {
        async function fetchBusinessHours() {
            try {
                const year = date.getFullYear();
                const month = date.getMonth() + 1;

                const res = await fetch(`/api/business-hours/weekly?year=${year}&month=${month}`);
                if (res.ok) {
                    const data = await res.json();
                    setBusinessHours(Array.isArray(data) ? data : []);
                }
            } catch (err) {
                console.error("営業時間の取得に失敗:", err);
            }
        }
        fetchBusinessHours();
    }, [date]);

    // 選択された日付に応じて、営業時間から「全枠」を生成
    useEffect(() => {
        if (!Array.isArray(businessHours) || businessHours.length === 0) {
            setAvailableTimes([]);
            return;
        }

        const dayOfWeekNames = ["日", "月", "火", "水", "木", "金", "土"];
        const selectedDay = dayOfWeekNames[date.getDay()];
        const weekOfMonth = getWeekOfMonthLikeLaravel(date);

        const hourInfo = businessHours.find(
            (h) => Number(h.week_of_month) === Number(weekOfMonth) && h.day_of_week === selectedDay
        );

        if (!hourInfo || hourInfo.is_closed) {
            setAvailableTimes([]);
            return;
        }

        const slots = generateTimeSlots(hourInfo.open_time, hourInfo.close_time, 15);
        setAvailableTimes(slots);
    }, [date, businessHours]);

    // ✅ 追加：空き枠（○/×判定用）を API から取得（BusinessHour基準 + 12時間ルール込み）
    useEffect(() => {
        const serviceId = formData.service_id;

        // メニュー未選択なら空き判定できない（全て×扱い）
        if (!serviceId) {
            setAvailableSlots([]);
            return;
        }

        const ymd = formatDateYMD(date);
        const controller = new AbortController();

        async function fetchAvailability() {
            setAvailabilityLoading(true);
            try {
                const res = await fetch(
                    `/api/reservations/check?date=${encodeURIComponent(ymd)}&service_id=${encodeURIComponent(
                        serviceId
                    )}`,
                    { signal: controller.signal }
                );

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    console.error("空き枠取得エラー:", data);
                    setAvailableSlots([]);
                    return;
                }

                const slots = Array.isArray(data.available_slots) ? data.available_slots : [];
                setAvailableSlots(slots);

                // もし選択中の時間が空き枠から外れたら解除
                if (selectedTime) {
                    const starts = new Set(slots.map((s) => s.start));
                    if (!starts.has(selectedTime)) {
                        setSelectedTime("");
                    }
                }
            } catch (err) {
                if (err?.name !== "AbortError") {
                    console.error("空き枠取得に失敗:", err);
                    setAvailableSlots([]);
                }
            } finally {
                setAvailabilityLoading(false);
            }
        }

        fetchAvailability();

        return () => controller.abort();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [date, formData.service_id]);

    // availableSlots から「○になる開始時刻セット」を作る
    const availableStartSet = useMemo(() => {
        return new Set((availableSlots || []).map((s) => s.start));
    }, [availableSlots]);

    // カレンダーの無効化（休業日など）
    const tileDisabled = ({ date: tileDate }) => {
        if (!Array.isArray(businessHours) || businessHours.length === 0) {
            // データ未取得中に全日Disableになるのを避ける
            return false;
        }

        const dayOfWeekNames = ["日", "月", "火", "水", "木", "金", "土"];
        const selectedDay = dayOfWeekNames[tileDate.getDay()];
        const weekOfMonth = getWeekOfMonthLikeLaravel(tileDate);

        const dayInfo = businessHours.find(
            (h) => Number(h.week_of_month) === Number(weekOfMonth) && h.day_of_week === selectedDay
        );

        return !dayInfo || !!dayInfo.is_closed;
    };

    // 入力変更ハンドラ
    const handleChange = (e) => {
        const { name, value } = e.target;

        // メニュー変更時は選択時間をリセット（空き判定が変わるため）
        if (name === "service_id") {
            setSelectedTime("");
        }

        setFormData({ ...formData, [name]: value });
    };

    // 日付変更（range対応しつつ、選択時間をリセット）
    const handleDateChange = (value) => {
        const d = Array.isArray(value) ? value[0] : value;
        setDate(d);
        setSelectedTime("");
    };

    // 送信処理
    const handleSubmit = async (e) => {
        e.preventDefault();
        setMessage("");

        if (!formData.service_id) {
            setMessage("メニューが選択されていません。メニュー・料金ページから選択してください。");
            return;
        }

        if (!selectedTime) {
            setMessage("時間を選択してください。");
            return;
        }

        // UI上でも12時間チェック（最終防波堤はAPI側）
        if (!isAfter12HoursFromNow(date, selectedTime)) {
            setMessage("ご予約は現在時刻から12時間以降の枠のみ受付可能です。");
            return;
        }

        const payload = {
            ...formData,
            date: formatDateYMD(date),
            start_time: selectedTime,
        };

        try {
            const response = await fetch("/api/reservations", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok) {
                setMessage("✅ ご予約が完了しました！メールをご確認ください。");
                console.log("予約成功:", data);

                // 入力リセット（service_id は固定のまま維持）
                setSelectedTime("");
                setFormData({
                    name: "",
                    phone: "",
                    service_id: initialServiceId,
                    email: "",
                    notes: "",
                });
                setAvailableSlots([]);
            } else {
                setMessage(data.message || "⚠️ 予約に失敗しました。");
            }
        } catch (err) {
            console.error("送信エラー:", err);
            setMessage("⚠️ サーバー通信エラーが発生しました。");
        }
    };

    // 🔙 メニュー・料金ページへ戻る
    const handleBack = () => {
        window.location.href = "/menu_price";
    };

    return (
        <main className="reservation-main">
            {/* 前のページに戻るボタン */}
            <div className="reservation-back">
                <button type="button" onClick={handleBack} className="reservation-back-button">
                    前のページに戻る
                </button>
            </div>

            <h1 className="reservation-title">ご予約フォーム</h1>

            <form onSubmit={handleSubmit} className="reservation-form-card">
                {/* ✅ メニュー（表示のみ：menu_price で選択済み想定） */}
                <div className="reservation-field">
                    <label className="reservation-label">
                        ※眉毛の自己処理はご来店の約2週間前からお控えください。<br />
                        (1ヶ月ほど手を加えずにご来店いただくのがおすすめです。)
                    </label>

                    <label className="reservation-label">メニュー</label>

                    {!formData.service_id ? (
                        <p className="reservation-time-note">
                            ※ メニューが選択されていません。
                            <button
                                type="button"
                                onClick={handleBack}
                                className="reservation-back-button"
                            >
                                メニュー・料金へ戻る
                            </button>
                        </p>
                    ) : selectedService ? (
                        <p className="reservation-selected-time">
                            選択中: {selectedService.name}（¥{selectedService.price} /{" "}
                            {selectedService.duration_minutes}分）{" "}
                            <button type="button" onClick={handleBack} className="reservation-back-button">
                                メニューを変更する
                            </button>
                        </p>
                    ) : (
                        <p className="reservation-time-note">※ 選択中メニュー情報を確認中...</p>
                    )}
                </div>

                {/* 名前 */}
                <div className="reservation-field">
                    <label className="reservation-label">お名前</label>
                    <input
                        type="text"
                        name="name"
                        value={formData.name}
                        onChange={handleChange}
                        required
                        className="reservation-input"
                    />
                </div>

                {/* メール */}
                <div className="reservation-field">
                    <label className="reservation-label">メールアドレス</label>
                    <input
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        required
                        className="reservation-input"
                    />
                </div>

                {/* 電話番号 */}
                <div className="reservation-field">
                    <label className="reservation-label">電話番号</label>
                    <input
                        type="tel"
                        name="phone"
                        value={formData.phone}
                        onChange={handleChange}
                        required
                        className="reservation-input"
                    />
                </div>

                {/* カレンダー */}
                <div className="reservation-field">
                    <label className="reservation-label">ご希望日</label>
                    <div className="reservation-calendar-wrapper">
                        <div className="reservation-calendar">
                            <Calendar onChange={handleDateChange} value={date} tileDisabled={tileDisabled} />
                        </div>
                        <p className="reservation-date-text">選択された日付: {date.toLocaleDateString()}</p>
                    </div>
                </div>

                {/* 時間枠選択 */}
                <div className="reservation-field">
                    <label className="reservation-label">ご希望時間</label>

                    <div className="reservation-time-wrapper">
                        {availableTimes.length === 0 ? (
                            <p className="reservation-time-note">※ この日は休業日または営業時間外です</p>
                        ) : !formData.service_id ? (
                            <p className="reservation-time-note">
                                ※ メニューが選択されていません（メニュー・料金から選択してください）
                            </p>
                        ) : availabilityLoading ? (
                            <p className="reservation-time-note">空き状況を確認中...</p>
                        ) : (
                            <div className="reservation-time-grid">
                                {availableTimes.map((time) => {
                                    const isAvailable = availableStartSet.has(time);
                                    // UI上でも12h未満は選べない（API側でも弾いているが見た目補強）
                                    const ok12h = isAfter12HoursFromNow(date, time);
                                    const canSelect = isAvailable && ok12h;

                                    const statusMark = canSelect ? "○" : "×";

                                    return (
                                        <button
                                            type="button"
                                            key={time}
                                            onClick={() => {
                                                if (canSelect) setSelectedTime(time);
                                            }}
                                            disabled={!canSelect}
                                            className={`reservation-time-button ${selectedTime === time ? "reservation-time-button--selected" : ""
                                                } ${!canSelect ? "reservation-time-button--disabled" : ""}`}
                                        >
                                            <span className="reservation-time-label">{time}</span>
                                            <span className="reservation-time-status">{statusMark}</span>
                                        </button>
                                    );
                                })}
                            </div>
                        )}

                        {selectedTime && <p className="reservation-selected-time">選択された時間: {selectedTime}</p>}
                    </div>
                </div>

                {/* 備考 */}
                <div className="reservation-field">
                    <label className="reservation-label">備考</label>
                    <textarea
                        name="notes"
                        value={formData.notes}
                        onChange={handleChange}
                        rows={3}
                        className="reservation-textarea"
                    />
                </div>

                {/* ✅ メッセージを「予約する」ボタンの上に表示 */}
                {message && (
                    <p
                        className={`reservation-message ${message.includes("✅")
                                ? "reservation-message--success"
                                : "reservation-message--error"
                            }`}
                    >
                        {message}
                    </p>
                )}

                {/* 送信ボタン */}
                <button type="submit" className="reservation-submit-button">
                    予約する
                </button>
            </form>
        </main>
    );
}
