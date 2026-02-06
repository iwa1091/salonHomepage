// /resources/js/Pages/Admin/ReservationList.jsx
import { useEffect, useMemo, useState } from "react";
import { router, Link } from "@inertiajs/react";
import { route } from "ziggy-js";
import Calendar from "react-calendar";
import "react-calendar/dist/Calendar.css";
import "../../../css/pages/admin/reservation-list.css";

function pad2(n) {
    return String(n).padStart(2, "0");
}

function toYmd(date) {
    const y = date.getFullYear();
    const m = pad2(date.getMonth() + 1);
    const d = pad2(date.getDate());
    return `${y}-${m}-${d}`;
}

function startOfMonth(year, month1to12) {
    return new Date(year, month1to12 - 1, 1);
}

function endOfMonth(year, month1to12) {
    // month に 1..12 を渡すと、Date(year, month, 0) で「その月の末日」
    return new Date(year, month1to12, 0);
}

function getWeekOfMonth(dateObj) {
    const firstDay = new Date(dateObj.getFullYear(), dateObj.getMonth(), 1);
    // ISO風（Mon=1..Sun=7）
    const firstIso = firstDay.getDay() === 0 ? 7 : firstDay.getDay();
    return Math.ceil((dateObj.getDate() + firstIso - 1) / 7);
}

function dayJp(dateObj) {
    const names = ["日", "月", "火", "水", "木", "金", "土"];
    return names[dateObj.getDay()];
}

export default function ReservationList() {
    const [monthOffset, setMonthOffset] = useState(0); // 0=今月, 1=来月

    const base = useMemo(() => {
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth() + monthOffset, 1);
    }, [monthOffset]);

    const year = base.getFullYear();
    const month = base.getMonth() + 1;

    const [businessHours, setBusinessHours] = useState([]);
    const [countsByDate, setCountsByDate] = useState({});

    // 営業時間（その月）
    useEffect(() => {
        let alive = true;

        (async () => {
            try {
                const res = await fetch(`/api/business-hours/weekly?year=${year}&month=${month}`, {
                    cache: "no-store",
                    credentials: "same-origin",
                    headers: {
                        Accept: "application/json",
                        "Cache-Control": "no-cache",
                        Pragma: "no-cache",
                    },
                });
                if (!res.ok) {
                    if (alive) setBusinessHours([]);
                    return;
                }
                const data = await res.json();
                if (alive) setBusinessHours(Array.isArray(data) ? data : []);
            } catch {
                if (alive) setBusinessHours([]);
            }
        })();

        return () => {
            alive = false;
        };
    }, [year, month]);

    // 予約件数（その月：from/to で軽量に）
    useEffect(() => {
        let alive = true;

        const fetchCounts = async () => {
            try {
                const from = toYmd(startOfMonth(year, month));
                const to = toYmd(endOfMonth(year, month));

                // ✅ ルート定義に合わせて /admin/api/reservations
                const res = await fetch(`/admin/api/reservations?from=${from}&to=${to}`, {
                    cache: "no-store",
                    credentials: "same-origin",
                    headers: {
                        Accept: "application/json",
                        "Cache-Control": "no-cache",
                        Pragma: "no-cache",
                    },
                });
                if (!res.ok) {
                    if (alive) setCountsByDate({});
                    return;
                }

                const data = await res.json();
                const map = {};
                for (const r of Array.isArray(data) ? data : []) {
                    const d = String(r.date).slice(0, 10);
                    map[d] = (map[d] || 0) + 1;
                }
                if (alive) setCountsByDate(map);
            } catch {
                if (alive) setCountsByDate({});
            }
        };

        // 初回
        fetchCounts();

        // ✅ 画面復帰（戻る/タブ復帰/フォーカス）でも最新を取り直す
        const refresh = () => {
            if (document.hidden) return;
            fetchCounts();
        };
        const onVis = () => {
            if (!document.hidden) refresh();
        };

        window.addEventListener("focus", refresh);
        window.addEventListener("pageshow", refresh);
        document.addEventListener("visibilitychange", onVis);

        // 任意：放置でも更新（30秒）
        const timer = setInterval(refresh, 30000);

        return () => {
            alive = false;
            window.removeEventListener("focus", refresh);
            window.removeEventListener("pageshow", refresh);
            document.removeEventListener("visibilitychange", onVis);
            clearInterval(timer);
        };
    }, [year, month]);

    const tileDisabled = ({ date, view }) => {
        if (view !== "month") return false;

        const w = getWeekOfMonth(date);
        const d = dayJp(date);

        const target = businessHours.find(
            (b) => Number(b.week_of_month) === Number(w) && b.day_of_week === d
        );

        // データが無い月は「全部押せる」にしておく（seed される想定）
        if (!target) return false;

        return !!target.is_closed;
    };

    const tileContent = ({ date, view }) => {
        if (view !== "month") return null;

        const key = toYmd(date);
        const c = countsByDate[key] || 0;
        if (!c) return null;

        // ✅ CSSは文字色/サイズ前提なので "●" を入れる
        return (
            <span className="admin-cal-dot" title={`${c}件`}>
                ●
            </span>
        );
    };

    const onClickDay = (date) => {
        const ymd = toYmd(date);
        // ✅ ルート名に合わせて admin.timetable.index
        router.get(route("admin.timetable.index", { date: ymd }));
    };

    return (
        <div className="admin-reservation-page">
            <div className="admin-reservation-back">
                <Link href={route("admin.dashboard")} className="admin-reservation-back-link">
                    前のページに戻る
                </Link>
            </div>

            <h1 className="admin-reservation-title">予約カレンダー</h1>

            <div className="admin-cal-wrapper">
                <div className="admin-cal-header">
                    <div className="admin-cal-tabs">
                        <button
                            type="button"
                            className={"admin-cal-tab " + (monthOffset === 0 ? "is-active" : "")}
                            onClick={() => setMonthOffset(0)}
                        >
                            今月
                        </button>
                        <button
                            type="button"
                            className={"admin-cal-tab " + (monthOffset === 1 ? "is-active" : "")}
                            onClick={() => setMonthOffset(1)}
                        >
                            来月
                        </button>
                    </div>

                    <p className="admin-cal-note">● は予約あり（件数はツールチップ）</p>
                </div>

                <Calendar
                    activeStartDate={base}
                    value={null}
                    onClickDay={onClickDay}
                    tileDisabled={tileDisabled}
                    tileContent={tileContent}
                    showNeighboringMonth={true}
                    prevLabel={null}
                    nextLabel={null}
                    prev2Label={null}
                    next2Label={null}
                />
            </div>
        </div>
    );
}
