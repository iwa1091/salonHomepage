// /resources/js/Pages/Admin/ReservationList.jsx
import { useEffect, useState } from "react";
import { Link, usePage, router } from "@inertiajs/react";
import Calendar from "react-calendar";
import "../../../css/pages/admin/reservation-list.css";

// ⏰ 時刻表示を日本時間の「HH:mm」形式に揃えるヘルパー
function formatTimeToHHmm(value) {
    if (!value) return "";

    // すでに "HH:MM" or "HH:MM:SS" 形式なら、そのまま / 切り詰めて利用
    if (/^\d{2}:\d{2}(:\d{2})?$/.test(value)) {
        return value.slice(0, 5); // "HH:MM"
    }

    // "2025-11-28T06:00:00.000000Z" のような ISO 文字列の場合
    const d = new Date(value);
    if (isNaN(d.getTime())) {
        // パースできなければ元の値をそのまま返す（保険）
        return value;
    }

    const hours = String(d.getHours()).padStart(2, "0");
    const minutes = String(d.getMinutes()).padStart(2, "0");
    return `${hours}:${minutes}`;
}

// 📅 日付表示を「0000年00月00日」に揃えるヘルパー
function formatDateToJapanese(value) {
    if (!value) return "";

    const d = new Date(value);
    if (isNaN(d.getTime())) {
        // パースできない場合は元の値をそのまま返す
        return value;
    }

    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");

    return `${year}年${month}月${day}日`;
}

// ========================================
// 🆕 カレンダービュー用のヘルパー関数
// ========================================

// week_of_month 計算（PHP BusinessHour::getWeekOfMonth() と同じロジック）
function getWeekOfMonth(dateObj) {
    if (!(dateObj instanceof Date) || isNaN(dateObj.getTime())) return 1;
    const day = dateObj.getDate();
    const firstDay = new Date(dateObj.getFullYear(), dateObj.getMonth(), 1);
    const firstIso = firstDay.getDay() === 0 ? 7 : firstDay.getDay();
    return Math.ceil((day + firstIso - 1) / 7);
}

// 曜日を日本語に変換
function getDayOfWeekJp(dateObj) {
    const dayNames = ['日', '月', '火', '水', '木', '金', '土'];
    return dayNames[dateObj.getDay()];
}

// タイムゾーン安全な YYYY-MM-DD 変換
function toYmd(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

// タイムゾーン安全な Date 生成
function parseYmd(ymdStr) {
    const parts = ymdStr.split('-');
    if (parts.length !== 3) return new Date();
    const [y, m, d] = parts.map(Number);
    return new Date(y, m - 1, d);
}

export default function ReservationList() {
    const { reservations: reservationsProp } = usePage().props;

    const [reservations, setReservations] = useState([]);
    const [businessHoursForTable, setBusinessHoursForTable] = useState([]);
    const [businessHoursForCalendar, setBusinessHoursForCalendar] = useState([]);
    const [loading, setLoading] = useState(true);

    // カレンダービュー用の state
    const [viewMode, setViewMode] = useState('table'); // 'table' | 'calendar'
    const [monthOffset, setMonthOffset] = useState(0); // 0=今月, 1=来月
    const [countsByDate, setCountsByDate] = useState({});

    // ✅ Inertia props から予約データを反映（/admin/reservations の Inertia ページ）
    useEffect(() => {
        const data = reservationsProp?.data
            ? reservationsProp.data
            : Array.isArray(reservationsProp)
                ? reservationsProp
                : [];

        setReservations(data);
        setLoading(false);
    }, [reservationsProp]);

    // 営業時間データの取得（✅ axios化：CSRF/Accept(JSON)/エラーハンドリング統一）
    useEffect(() => {
        async function fetchBusinessHours() {
            try {
                const now = new Date();
                const year = now.getFullYear();
                const month = now.getMonth() + 1; // 現在の月

                // bootstrap.js で window.axios を初期化済み前提
                const res = await window.axios.get("/api/business-hours/weekly", {
                    params: { year, month },
                });

                setBusinessHoursForTable(Array.isArray(res.data) ? res.data : []);
            } catch (err) {
                // axios エラーは err.response がある場合がある
                const status = err?.response?.status;
                const data = err?.response?.data;

                console.error("営業時間の取得に失敗:", {
                    status,
                    data,
                    message: err?.message,
                });

                // 失敗時は空にして誤判定を避ける
                setBusinessHoursForTable([]);
            }
        }
        fetchBusinessHours();
    }, []);

    // カレンダービュー用：営業時間取得
    useEffect(() => {
        if (viewMode !== 'calendar') return;

        const base = new Date();
        base.setMonth(base.getMonth() + monthOffset);
        const year = base.getFullYear();
        const month = base.getMonth() + 1;

        (async () => {
            try {
                const res = await window.axios.get('/api/business-hours/weekly', {
                    params: { year, month },
                });
                setBusinessHoursForCalendar(Array.isArray(res.data) ? res.data : []);
            } catch (err) {
                console.error('営業時間取得エラー:', err);
                setBusinessHoursForCalendar([]);
            }
        })();
    }, [viewMode, monthOffset]);

    // カレンダービュー用：予約件数取得
    useEffect(() => {
        if (viewMode !== 'calendar') return;

        const base = new Date();
        base.setMonth(base.getMonth() + monthOffset);
        const year = base.getFullYear();
        const month = base.getMonth() + 1;

        const from = toYmd(new Date(year, month - 1, 1));
        const to = toYmd(new Date(year, month, 0));

        (async () => {
            try {
                const res = await window.axios.get('/admin/api/reservations', {
                    params: { from, to },
                });
                const map = {};
                for (const r of Array.isArray(res.data) ? res.data : []) {
                    const d = String(r.date).slice(0, 10);
                    map[d] = (map[d] || 0) + 1;
                }
                setCountsByDate(map);
            } catch (err) {
                console.error('予約件数取得エラー:', err);
                setCountsByDate({});
            }
        })();
    }, [viewMode, monthOffset]);

    // 予約の時間表示（営業中/営業時間外のラベルも付ける）
    const getFormattedTime = (date, startTimeRaw) => {
        const startTime = formatTimeToHHmm(startTimeRaw);

        // ✅ 営業時間が未取得の間はラベルを付けず、誤判定を避ける
        if (!businessHoursForTable || businessHoursForTable.length === 0) {
            return startTime;
        }

        const dayOfWeekNames = ["日", "月", "火", "水", "木", "金", "土"];
        const selectedDay = dayOfWeekNames[date.getDay()];

        // 営業時間データを取得（※週は考慮せず曜日ベースで判定＝既存仕様のまま）
        const hourInfo = businessHoursForTable.find((h) => h.day_of_week === selectedDay);

        if (hourInfo && !hourInfo.is_closed) {
            return `${startTime}（営業中）`;
        }

        return `${startTime}（営業時間外）`;
    };

    const handleDelete = async (id) => {
        if (!confirm("この予約を削除しますか？")) return;

        // ✅ 公開APIではなく、admin認証下の web ルートで削除する
        router.post(route("admin.reservations.destroy", id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // 画面反映を即時にしたい場合は残す（Inertia の再描画でも更新されます）
                setReservations((prev) => prev.filter((r) => r.id !== id));
            },
        });
    };

    // react-calendar: 営業日判定
    const tileDisabled = ({ date, view }) => {
        if (view !== 'month') return false;
        const w = getWeekOfMonth(date);
        const d = getDayOfWeekJp(date);
        const target = businessHoursForCalendar.find(
            (b) => Number(b.week_of_month) === Number(w) && b.day_of_week === d
        );
        return !target || !!target.is_closed;
    };

    // react-calendar: 予約件数バッジ表示
    const tileContent = ({ date, view }) => {
        if (view !== 'month') return null;
        const key = toYmd(date);
        const c = countsByDate[key] || 0;
        if (!c) return null;
        return <span className="admin-cal-dot" title={`${c}件の予約`}>●</span>;
    };

    // react-calendar: 日付クリック
    const onClickDay = (date) => {
        const ymd = toYmd(date);
        router.get(route('admin.timetable.index', { date: ymd }));
    };

    if (loading) {
        return <p className="admin-reservation-loading">読み込み中...</p>;
    }

    return (
        <div className="admin-reservation-page">
            {/* 🔙 ダッシュボードへ戻るボタン */}
            <div className="admin-reservation-back">
                <Link
                    href={route("admin.dashboard")}
                    className="admin-reservation-back-link"
                >
                    前のページに戻る
                </Link>
            </div>

            <h1 className="admin-reservation-title">予約一覧</h1>

            {/* タブ切り替え */}
            <div className="admin-cal-tabs">
                <button
                    className={`admin-cal-tab ${viewMode === 'table' ? 'is-active' : ''}`}
                    onClick={() => setViewMode('table')}
                >
                    テーブル表示
                </button>
                <button
                    className={`admin-cal-tab ${viewMode === 'calendar' ? 'is-active' : ''}`}
                    onClick={() => setViewMode('calendar')}
                >
                    カレンダー表示
                </button>
            </div>

            {viewMode === 'calendar' ? (
                <div className="admin-cal-wrapper">
                    <div className="admin-cal-header">
                        <div className="admin-cal-month-tabs">
                            <button
                                className={`admin-cal-month-tab ${monthOffset === 0 ? 'is-active' : ''}`}
                                onClick={() => setMonthOffset(0)}
                            >
                                今月
                            </button>
                            <button
                                className={`admin-cal-month-tab ${monthOffset === 1 ? 'is-active' : ''}`}
                                onClick={() => setMonthOffset(1)}
                            >
                                来月
                            </button>
                        </div>
                        <p className="admin-cal-note">● は予約あり（件数はツールチップ）</p>
                    </div>
                    <Calendar
                        activeStartDate={(() => {
                            const base = new Date();
                            base.setMonth(base.getMonth() + monthOffset);
                            return base;
                        })()}
                        value={null}
                        onClickDay={onClickDay}
                        tileDisabled={tileDisabled}
                        tileContent={tileContent}
                        showNeighboringMonth={true}
                    />
                </div>
            ) : (
                <>
                    <div className="admin-reservation-table-wrapper">
                        <table className="admin-reservation-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>氏名</th>
                                    <th>メニュー</th>
                                    <th>日付</th>
                                    <th>時間</th>
                                    <th>状態</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                {reservations.map((r) => (
                                    <tr key={r.id} className="admin-reservation-row">
                                        <td className="admin-reservation-cell admin-reservation-cell--id">
                                            {r.id}
                                        </td>
                                        <td className="admin-reservation-cell">{r.name}</td>
                                        <td className="admin-reservation-cell">
                                            {r.service_name}
                                        </td>
                                        <td className="admin-reservation-cell admin-reservation-cell--date">
                                            {formatDateToJapanese(r.date)}
                                        </td>
                                        <td className="admin-reservation-cell admin-reservation-cell--time">
                                            {getFormattedTime(new Date(r.date), r.start_time)}
                                        </td>
                                        <td className="admin-reservation-cell">
                                            <span className="admin-reservation-status">
                                                {r.status || "予約中"}
                                            </span>
                                        </td>
                                        <td className="admin-reservation-actions">
                                            <Link
                                                href={route("admin.reservations.edit", r.id)}
                                                className="admin-reservation-button admin-reservation-button--edit"
                                            >
                                                編集
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(r.id)}
                                                className="admin-reservation-button admin-reservation-button--delete"
                                            >
                                                削除
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* ✅ paginate を使っている場合のリンク（CSS未追加でも表示はされます） */}
                    {Array.isArray(reservationsProp?.links) && reservationsProp.links.length > 0 && (
                        <div className="admin-reservation-pagination">
                            {reservationsProp.links.map((l, idx) => {
                                // l.url が null のものは非活性
                                if (!l.url) {
                                    return (
                                        <span
                                            key={idx}
                                            className="admin-reservation-back-link"
                                            style={{ opacity: 0.5, pointerEvents: "none" }}
                                            dangerouslySetInnerHTML={{ __html: l.label }}
                                        />
                                    );
                                }

                                return (
                                    <Link
                                        key={idx}
                                        href={l.url}
                                        className="admin-reservation-back-link"
                                        preserveScroll
                                        dangerouslySetInnerHTML={{ __html: l.label }}
                                    />
                                );
                            })}
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
