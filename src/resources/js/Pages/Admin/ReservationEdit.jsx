// /resources/js/Pages/Admin/ReservationEdit.jsx
import { useEffect, useMemo, useRef, useState } from "react";
import { router, usePage, Link } from "@inertiajs/react";
import { route } from "ziggy-js";
import Calendar from "react-calendar";
import "react-calendar/dist/Calendar.css";
import "../../../css/pages/admin/reservation-edit.css";

/**
 * ✅ "HH:mm" に正規化（Date変換は一切しない）
 * - "09:00" / "09:00:00" → "09:00"
 * - "2025-12-29T09:00:00.000000Z" など → "09:00"（時刻部分だけ抜く）
 * - 取れなければ "" を返す（00:00固定は誤解を生むのでやらない）
 */
function normalizeHHmm(value) {
    if (!value) return "";

    const str = String(value).trim();

    // "HH:MM" / "HH:MM:SS"
    if (/^\d{2}:\d{2}(:\d{2})?$/.test(str)) {
        return str.slice(0, 5);
    }

    // ISO / datetime文字列から "HH:MM" を抽出
    const m = str.match(/\b(\d{2}:\d{2})(?::\d{2})?\b/);
    if (m) return m[1];

    return "";
}

/**
 * ✅ 日付を "YYYY-MM-DD" に正規化
 * - "YYYY-MM-DD" → そのまま
 * - ISOなど → 先頭10文字を採用
 */
function normalizeYmd(value) {
    if (!value) return "";
    const str = String(value).trim();

    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) return str;

    if (/^\d{4}-\d{2}-\d{2}/.test(str)) return str.slice(0, 10);

    return "";
}

/**
 * ✅ "YYYY-MM-DD" をローカル日付として安全に Date 化（曜日/カレンダー用）
 */
function safeDateFromYmd(value) {
    const ymd = normalizeYmd(value);
    if (!ymd) return null;

    const [y, m, d] = ymd.split("-").map(Number);
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
}

/**
 * ✅ week_of_month をJSで計算（PHP側 BusinessHour::getWeekOfMonth と合わせる）
 * PHP: ceil((day + firstDay->dayOfWeekIso - 1)/7)
 * JS: dayOfWeekIso: Mon=1..Sun=7（JSは Sun=0..Sat=6）
 */
function getWeekOfMonth(dateObj) {
    if (!(dateObj instanceof Date) || isNaN(dateObj.getTime())) return 1;

    const day = dateObj.getDate();
    const firstDay = new Date(dateObj.getFullYear(), dateObj.getMonth(), 1);
    const firstIso = firstDay.getDay() === 0 ? 7 : firstDay.getDay(); // Sun(0)→7
    return Math.ceil((day + firstIso - 1) / 7);
}

function getDayOfWeekJp(dateObj) {
    const dayNames = ["日", "月", "火", "水", "木", "金", "土"];
    return dayNames[dateObj.getDay()];
}

/**
 * 📅「YYYY年MM月DD日 HH:mm」形式に整形（表示用）
 */
function formatDateTimeJp(ymd, timeHHmm) {
    const dateStr = normalizeYmd(ymd);
    if (!dateStr) return "";

    const [y, m, d] = dateStr.split("-");
    const time = normalizeHHmm(timeHHmm) || "--:--";
    return `${y}年${m}月${d}日 ${time}`;
}

/**
 * ✅ "HH:mm" を分→ "HH:mm" に戻す
 */
function minutesToHHmm(totalMinutes) {
    const h = String(Math.floor(totalMinutes / 60)).padStart(2, "0");
    const m = String(totalMinutes % 60).padStart(2, "0");
    return `${h}:${m}`;
}

/**
 * ✅ "HH:mm" → 分
 */
function hhmmToMinutes(hhmm) {
    const t = normalizeHHmm(hhmm);
    if (!t) return null;
    const [h, m] = t.split(":").map(Number);
    if (Number.isNaN(h) || Number.isNaN(m)) return null;
    return h * 60 + m;
}

export default function ReservationEdit() {
    const { reservation } = usePage().props;

    // ✅ キャンセル済み判定
    const isCanceled = reservation?.status === "canceled";

    // ✅ 画面復帰時のリロード制御（編集中は上書きしない）
    const [isDirty, setIsDirty] = useState(false);
    const lastReloadAtRef = useRef(0);

    // 初期値を “必ず” 正規化（ここがないとボタン選択の一致が崩れます）
    const initialDate = normalizeYmd(reservation?.date);
    const initialStart = normalizeHHmm(reservation?.start_time);
    const initialDuration = Number(reservation?.service?.duration_minutes || 0);

    // 表示中の月（カレンダーが見ている月）の営業時間を取るための state
    const initialDateObj = safeDateFromYmd(initialDate) || new Date();
    const [activeYear, setActiveYear] = useState(initialDateObj.getFullYear());
    const [activeMonth, setActiveMonth] = useState(initialDateObj.getMonth() + 1);

    // 戻り先：timetable（date付き）を優先
    const backHref = initialDate
        ? route("admin.timetable.index", { date: initialDate })
        : route("admin.reservations.index");

    // ✅ サービス一覧（メニュー）取得用
    const [services, setServices] = useState([]);

    // ✅ 初期値（ReservationForm/Timetable と同じ項目に揃える）
    const [formData, setFormData] = useState(() => {
        // end_time を持っていれば正規化、無ければ duration から計算（保険）
        const rawEnd = reservation?.end_time;
        let end = normalizeHHmm(rawEnd);

        if (!end && initialStart && initialDuration > 0) {
            const startMin = hhmmToMinutes(initialStart);
            if (startMin !== null) {
                end = minutesToHHmm(startMin + initialDuration);
            }
        }

        return {
            name: reservation?.name || "",
            phone: reservation?.phone || "",
            email: reservation?.email || "",
            notes: reservation?.notes || "",

            date: initialDate,
            start_time: initialStart,
            end_time: end,

            // select は文字列の方が扱いやすい
            service_id:
                reservation?.service_id != null
                    ? String(reservation.service_id)
                    : reservation?.service?.id != null
                        ? String(reservation.service.id)
                        : "",

            service_duration: initialDuration, // 所要時間（分）※内部計算用
        };
    });

    const [businessHours, setBusinessHours] = useState([]);
    const [availableTimes, setAvailableTimes] = useState([]);

    // =========================================================
    // ✅ 追加：予約ステータスを自動反映（reservation だけ定期リロード）
    // - 編集中でも「フォーム上書き」はしない（isDirtyガードが効く）
    // - キャンセルされたら画面上の isCanceled が更新される
    // =========================================================
    useEffect(() => {
        const id = setInterval(() => {
            router.reload({
                only: ["reservation"],
                preserveScroll: true,
                preserveState: true,
            });
        }, 5000); // 5秒おき（必要なら 10秒 にしてOK）

        return () => clearInterval(id);
    }, []);

    // ✅ 画面復帰時に最新propsを取り直す（編集中は上書きしない）
    useEffect(() => {
        const tryReload = () => {
            if (document.hidden) return;
            if (isDirty) return;

            const now = Date.now();
            if (now - lastReloadAtRef.current < 4000) return; // 連打防止
            lastReloadAtRef.current = now;

            router.reload({ preserveScroll: true });
        };

        const onVis = () => {
            if (!document.hidden) tryReload();
        };

        window.addEventListener("focus", tryReload);
        window.addEventListener("pageshow", tryReload);
        document.addEventListener("visibilitychange", onVis);

        return () => {
            window.removeEventListener("focus", tryReload);
            window.removeEventListener("pageshow", tryReload);
            document.removeEventListener("visibilitychange", onVis);
        };
    }, [isDirty]);

    // ✅ propsのreservationが更新されたら（リロード後など）、編集中でない場合だけフォームへ反映
    useEffect(() => {
        if (isDirty) return;

        const nextDate = normalizeYmd(reservation?.date);
        const nextStart = normalizeHHmm(reservation?.start_time);
        const nextDuration = Number(reservation?.service?.duration_minutes || 0);

        const nextDateObj = safeDateFromYmd(nextDate) || new Date();
        setActiveYear(nextDateObj.getFullYear());
        setActiveMonth(nextDateObj.getMonth() + 1);

        const rawEnd = reservation?.end_time;
        let end = normalizeHHmm(rawEnd);

        if (!end && nextStart && nextDuration > 0) {
            const startMin = hhmmToMinutes(nextStart);
            if (startMin !== null) {
                end = minutesToHHmm(startMin + nextDuration);
            }
        }

        setFormData((prev) => ({
            ...prev,
            name: reservation?.name || "",
            phone: reservation?.phone || "",
            email: reservation?.email || "",
            notes: reservation?.notes || "",
            date: nextDate,
            start_time: nextStart,
            end_time: end,
            service_id:
                reservation?.service_id != null
                    ? String(reservation.service_id)
                    : reservation?.service?.id != null
                        ? String(reservation.service.id)
                        : "",
            service_duration: nextDuration,
        }));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [reservation]);

    /**
     * ✅ サービス一覧を取得（/api/services）
     * window.axios があればそれを使用。無ければ fetch フォールバック。
     */
    useEffect(() => {
        let mounted = true;

        async function fetchServices() {
            try {
                if (window.axios) {
                    const res = await window.axios.get("/api/services", {
                        headers: {
                            Accept: "application/json",
                            "Cache-Control": "no-cache",
                            Pragma: "no-cache",
                        },
                    });
                    if (mounted) setServices(Array.isArray(res.data) ? res.data : []);
                } else {
                    const res = await fetch("/api/services", {
                        cache: "no-store",
                        credentials: "same-origin",
                        headers: {
                            Accept: "application/json",
                            "Cache-Control": "no-cache",
                            Pragma: "no-cache",
                        },
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (mounted) setServices(Array.isArray(data) ? data : []);
                }
            } catch (e) {
                console.error("サービス一覧取得失敗:", e);
                if (mounted) setServices([]);
            }
        }

        fetchServices();
        return () => {
            mounted = false;
        };
    }, []);

    /**
     * ✅ 初期 service_duration が 0 の場合でも、service_id があれば services から補完
     * （予約データに service が eager load されていないケースの保険）
     */
    useEffect(() => {
        if (!formData.service_id) return;
        if (!Array.isArray(services) || services.length === 0) return;
        if (Number(formData.service_duration || 0) > 0) return;

        const hit = services.find((s) => String(s.id) === String(formData.service_id));
        const dur = Number(hit?.duration_minutes || 0);
        if (dur > 0) {
            setFormData((prev) => ({ ...prev, service_duration: dur }));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [services]);

    /**
     * ✅ 週単位の営業時間を取得（/api/business-hours/weekly）
     * window.axios 前提（CSRF/Accept(JSON)が揃う）
     */
    useEffect(() => {
        async function fetchBusinessHoursWeekly() {
            try {
                const res = await window.axios.get("/api/business-hours/weekly", {
                    params: { year: activeYear, month: activeMonth },
                    headers: {
                        Accept: "application/json",
                        "Cache-Control": "no-cache",
                        Pragma: "no-cache",
                    },
                });
                setBusinessHours(Array.isArray(res.data) ? res.data : []);
            } catch (e) {
                console.error("営業時間取得失敗:", e);
                setBusinessHours([]);
            }
        }
        fetchBusinessHoursWeekly();
    }, [activeYear, activeMonth]);

    /**
     * ✅ カレンダーの休業日グレーアウト（week_of_month を考慮）
     * さらに「前月/翌月のはみ出し日」は無効にして安全に
     */
    const tileDisabled = ({ date, view }) => {
        if (view !== "month") return false;

        const tileYear = date.getFullYear();
        const tileMonth = date.getMonth() + 1;
        if (tileYear !== activeYear || tileMonth !== activeMonth) return true;

        const dayOfWeek = getDayOfWeekJp(date);
        const weekOfMonth = getWeekOfMonth(date);

        const target = businessHours.find(
            (b) =>
                Number(b.week_of_month) === Number(weekOfMonth) &&
                b.day_of_week === dayOfWeek
        );

        return !target || !!target.is_closed;
    };

    /**
     * ✅ 選択日（date）に対して、営業日＆営業時間に基づいた時間スロット生成
     * - week_of_month を考慮
     * - duration を考慮して「開始できる最大時刻」まで生成
     * - 15分刻み
     */
    useEffect(() => {
        const dateObj = safeDateFromYmd(formData.date);
        if (!dateObj) {
            setAvailableTimes([]);
            return;
        }
        if (!Array.isArray(businessHours) || businessHours.length === 0) {
            setAvailableTimes([]);
            return;
        }

        const duration = Number(formData.service_duration || 0);
        if (!duration || duration <= 0) {
            setAvailableTimes([]);
            return;
        }

        // 選択日が別月なら、先に月 state を更新 → businessHours 再取得後に再計算
        const y = dateObj.getFullYear();
        const m = dateObj.getMonth() + 1;
        if (y !== activeYear || m !== activeMonth) {
            setActiveYear(y);
            setActiveMonth(m);
            return;
        }

        const dayOfWeek = getDayOfWeekJp(dateObj);
        const weekOfMonth = getWeekOfMonth(dateObj);

        const target = businessHours.find(
            (b) =>
                Number(b.week_of_month) === Number(weekOfMonth) &&
                b.day_of_week === dayOfWeek
        );

        if (!target || target.is_closed) {
            setAvailableTimes([]);
            return;
        }

        const openHHmm = normalizeHHmm(target.open_time);
        const closeHHmm = normalizeHHmm(target.close_time);

        const openMin = hhmmToMinutes(openHHmm);
        const closeMin = hhmmToMinutes(closeHHmm);
        if (openMin === null || closeMin === null) {
            setAvailableTimes([]);
            return;
        }

        const lastStart = closeMin - duration;
        if (lastStart < openMin) {
            setAvailableTimes([]);
            return;
        }

        const slots = [];
        for (let t = openMin; t <= lastStart; t += 15) {
            slots.push(minutesToHHmm(t));
        }
        setAvailableTimes(slots);

        // 現在選択中がスロット外ならクリア（不整合防止）
        const current = normalizeHHmm(formData.start_time);
        if (current && !slots.includes(current)) {
            setFormData((prev) => ({ ...prev, start_time: "", end_time: "" }));
        }
    }, [
        formData.date,
        formData.service_duration,
        businessHours,
        activeYear,
        activeMonth,
    ]);

    // 入力変更（name/phone/email/notes）
    const handleChange = (e) => {
        setIsDirty(true);
        setFormData((prev) => ({ ...prev, [e.target.name]: e.target.value }));
    };

    // ✅ メニュー変更（service_id） → duration を同期し、end_time を再計算（必要なら時間をクリア）
    const handleServiceChange = (e) => {
        setIsDirty(true);

        const nextServiceId = e.target.value;

        const hit = services.find((s) => String(s.id) === String(nextServiceId));
        const nextDuration = Number(hit?.duration_minutes || 0);

        setFormData((prev) => {
            const next = {
                ...prev,
                service_id: nextServiceId,
                service_duration: nextDuration,
            };

            // 既に時間を選択している場合は end_time を再計算
            const st = normalizeHHmm(prev.start_time);
            const startMin = hhmmToMinutes(st);
            if (st && startMin !== null && nextDuration > 0) {
                next.end_time = minutesToHHmm(startMin + nextDuration);
            } else {
                // durationが無い/不正なら時間は選び直してもらう
                next.start_time = "";
                next.end_time = "";
            }
            return next;
        });
    };

    // カレンダー変更（"YYYY-MM-DD" を組み立て）
    const handleDateChange = (date) => {
        setIsDirty(true);

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");

        // 月も更新（該当月の営業時間を取得）
        setActiveYear(year);
        setActiveMonth(Number(month));

        setFormData((prev) => ({
            ...prev,
            date: `${year}-${month}-${day}`,
            start_time: "",
            end_time: "",
        }));
    };

    // カレンダーの月移動時（表示月の営業時間を取得）
    const handleActiveStartDateChange = ({ activeStartDate }) => {
        if (!activeStartDate) return;
        setActiveYear(activeStartDate.getFullYear());
        setActiveMonth(activeStartDate.getMonth() + 1);
    };

    // 時間ボタン選択（start/end を同時に更新）
    const handlePickTime = (timeHHmm) => {
        setIsDirty(true);

        const t = normalizeHHmm(timeHHmm);
        const duration = Number(formData.service_duration || 0);

        let end = "";
        const startMin = hhmmToMinutes(t);
        if (startMin !== null && duration > 0) {
            end = minutesToHHmm(startMin + duration);
        }

        setFormData((prev) => ({
            ...prev,
            start_time: t,
            end_time: end,
        }));
    };

    // 更新処理
    const handleSubmit = (e) => {
        e.preventDefault();

        // ✅ キャンセル済みは更新させない（UIでもdisabledにしているが保険）
        if (isCanceled) return;

        // ✅ 内部用の service_duration は送らず、ReservationForm と同じ項目に揃える
        const payload = {
            name: formData.name,
            phone: formData.phone,
            email: formData.email,
            notes: formData.notes,

            service_id: formData.service_id ? Number(formData.service_id) : null,

            date: normalizeYmd(formData.date),
            start_time: normalizeHHmm(formData.start_time),
            end_time: normalizeHHmm(formData.end_time),
        };

        router.put(route("admin.reservations.update", reservation.id), payload, {
            preserveScroll: true,
            onSuccess: () => {
                // 更新後は「当日のtimetable」へ戻すのが自然
                const ymd = normalizeYmd(payload.date);
                if (ymd) {
                    router.visit(route("admin.timetable.index", { date: ymd }));
                } else {
                    router.visit(route("admin.reservations.index"));
                }
            },
        });
    };

    const calendarValue = useMemo(() => {
        return safeDateFromYmd(formData.date) || new Date();
    }, [formData.date]);

    return (
        <div className="admin-reservation-edit-page">
            <div className="admin-reservation-edit-back">
                <Link href={backHref} className="admin-reservation-edit-back-link">
                    前のページに戻る
                </Link>
            </div>

            <div className="admin-reservation-edit-card">
                <h1 className="admin-reservation-edit-title">予約編集</h1>

                {isCanceled && (
                    <p style={{ margin: "0 0 12px 0", color: "rgba(0,0,0,0.65)" }}>
                        この予約はキャンセル済みのため更新できません。
                    </p>
                )}

                <form onSubmit={handleSubmit} className="admin-reservation-edit-form">
                    {/* 氏名 */}
                    <div className="admin-reservation-edit-field">
                        <label className="admin-reservation-edit-label">氏名</label>
                        <input
                            type="text"
                            name="name"
                            value={formData.name}
                            onChange={handleChange}
                            className="admin-reservation-edit-input"
                        />
                    </div>

                    {/* メール */}
                    <div className="admin-reservation-edit-field">
                        <label className="admin-reservation-edit-label">メール</label>
                        <input
                            type="email"
                            name="email"
                            value={formData.email}
                            onChange={handleChange}
                            className="admin-reservation-edit-input"
                        />
                    </div>

                    {/* 電話番号 */}
                    <div className="admin-reservation-edit-field">
                        <label className="admin-reservation-edit-label">電話番号</label>
                        <input
                            type="tel"
                            name="phone"
                            value={formData.phone}
                            onChange={handleChange}
                            className="admin-reservation-edit-input"
                        />
                    </div>

                    {/* メニュー */}
                    <div className="admin-reservation-edit-field">
                        <label className="admin-reservation-edit-label">メニュー</label>
                        <select
                            name="service_id"
                            value={formData.service_id}
                            onChange={handleServiceChange}
                            className="admin-reservation-edit-input"
                        >
                            <option value="">選択してください</option>
                            {services.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name}（{s.duration_minutes}分）
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* 備考 */}
                    <div className="admin-reservation-edit-field">
                        <label className="admin-reservation-edit-label">備考</label>
                        <textarea
                            name="notes"
                            value={formData.notes}
                            onChange={handleChange}
                            className="admin-reservation-edit-input"
                            rows={3}
                        />
                    </div>

                    {/* カレンダー */}
                    <div className="admin-reservation-edit-field">
                        <label className="admin-reservation-edit-label">日付</label>

                        <div className="admin-reservation-edit-calendar-wrapper">
                            <div className="admin-reservation-edit-calendar">
                                <Calendar
                                    value={calendarValue}
                                    onChange={handleDateChange}
                                    onActiveStartDateChange={handleActiveStartDateChange}
                                    tileDisabled={tileDisabled}
                                />
                            </div>

                            <p className="admin-reservation-edit-date-text">
                                選択日: {formatDateTimeJp(formData.date, formData.start_time)}
                            </p>
                        </div>
                    </div>

                    {/* 時間 */}
                    <div className="admin-reservation-edit-field">
                        <label className="admin-reservation-edit-label">時間</label>

                        <div className="admin-reservation-edit-time-wrapper">
                            {Number(formData.service_duration || 0) <= 0 ? (
                                <p className="admin-reservation-edit-time-empty">
                                    先にメニューを選択してください
                                </p>
                            ) : availableTimes.length > 0 ? (
                                <div className="admin-reservation-edit-time-grid">
                                    {availableTimes.map((time) => {
                                        const selected = normalizeHHmm(formData.start_time) === time;
                                        return (
                                            <button
                                                key={time}
                                                type="button"
                                                onClick={() => handlePickTime(time)}
                                                className={
                                                    "admin-reservation-edit-time-button " +
                                                    (selected
                                                        ? "admin-reservation-edit-time-button--selected"
                                                        : "")
                                                }
                                            >
                                                {time}
                                            </button>
                                        );
                                    })}
                                </div>
                            ) : (
                                <p className="admin-reservation-edit-time-empty">
                                    営業時間外または休業日です
                                </p>
                            )}
                        </div>
                    </div>

                    {/* 更新ボタン */}
                    <button
                        type="submit"
                        className="admin-reservation-edit-submit"
                        disabled={
                            isCanceled ||
                            !formData.date ||
                            !normalizeHHmm(formData.start_time) ||
                            !formData.service_id
                        }
                        title={!normalizeHHmm(formData.start_time) ? "時間を選択してください" : ""}
                    >
                        更新
                    </button>
                </form>
            </div>
        </div>
    );
}
