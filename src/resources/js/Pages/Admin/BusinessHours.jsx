// /resources/js/Pages/Admin/BusinessHours.jsx
import { useState, useEffect } from "react";
import { Link } from "@inertiajs/react";
import { route } from "ziggy-js";

// モジュール化した CSS をインポート
import "../../../css/pages/admin/business-hours.css";

const toFullWidth = (n) =>
    String(n).replace(/[0-9]/g, (d) => "０１２３４５６７８９"[d]);

export default function BusinessHours() {
    const now = new Date();

    const [hours, setHours] = useState([]);
    const [loading, setLoading] = useState(true);
    const [message, setMessage] = useState("");

    const [selectedMonth, setSelectedMonth] = useState(now.getMonth() + 1); // 今月
    const [selectedYear, setSelectedYear] = useState(now.getFullYear());
    const [selectedWeek, setSelectedWeek] = useState(1);

    // 月のプルダウン（今月・来月）※ 年跨ぎ（12→1）も正しく扱う
    const months = [
        {
            label: "今月",
            year: new Date(now.getFullYear(), now.getMonth(), 1).getFullYear(),
            month: new Date(now.getFullYear(), now.getMonth(), 1).getMonth() + 1,
        },
        {
            label: "来月",
            year: new Date(now.getFullYear(), now.getMonth() + 1, 1).getFullYear(),
            month: new Date(now.getFullYear(), now.getMonth() + 1, 1).getMonth() + 1,
        },
    ];

    const ymValue = `${selectedYear}-${String(selectedMonth).padStart(2, "0")}`;

    // 営業時間を取得
    const fetchWeeklyHours = async (year, month) => {
        setLoading(true);
        try {
            // ReservationEdit.jsx と合わせて axios を使用（CSRF/JSONの差異による不一致を避ける）
            const res = await window.axios.get("/api/business-hours/weekly", {
                params: { year, month },
            });
            setHours(Array.isArray(res.data) ? res.data : []);
        } catch (err) {
            console.error("営業時間取得失敗:", err);
            setHours([]);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchWeeklyHours(selectedYear, selectedMonth);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedYear, selectedMonth]);

    // 値の変更ハンドラ
    const handleChange = (index, field, value) => {
        const updated = [...hours];
        if (!updated[index]) return;

        updated[index][field] = value;

        // 休業日チェック時は時間をクリア
        if (field === "is_closed" && value === true) {
            updated[index].open_time = null;
            updated[index].close_time = null;
        }

        setHours(updated);
    };

    // 保存処理
    const handleSave = async () => {
        try {
            const res = await window.axios.put("/api/business-hours/weekly", hours);

            if (res && res.status >= 200 && res.status < 300) {
                setMessage("営業時間を更新しました。");
                setTimeout(() => setMessage(""), 3000);

                // 保存後に再取得（サーバ側正規化との差異を消す）
                fetchWeeklyHours(selectedYear, selectedMonth);
            } else {
                setMessage("更新に失敗しました。");
            }
        } catch (err) {
            console.error("更新エラー:", err);
            setMessage("サーバー通信エラーが発生しました。");
        }
    };

    // 表示する週データをフィルタ
    const filteredHours = hours.filter(
        (h) => Number(h.week_of_month) === Number(selectedWeek)
    );

    if (loading) {
        return (
            <div className="admin-business-hours-page">
                <div className="admin-business-hours-container">
                    <p className="business-hours-loading">読み込み中...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="admin-business-hours-page">
            <div className="admin-business-hours-container">
                {/* 🔙 ダッシュボードへ戻る */}
                <div className="business-hours-back-area">
                    <Link
                        href={route("admin.dashboard")}
                        className="business-hours-back-button"
                    >
                        前のページに戻る
                    </Link>
                </div>

                <h1 className="business-hours-title">
                    営業日・営業時間設定（週単位・15分刻み）
                </h1>

                {message && <p className="business-hours-message">{message}</p>}

                {/* 月・週セレクト */}
                <div className="business-hours-controls">
                    {/* 年・月セレクト（年跨ぎ対応） */}
                    <select
                        value={ymValue}
                        onChange={(e) => {
                            const [y, m] = String(e.target.value).split("-");
                            setSelectedYear(Number(y));
                            setSelectedMonth(Number(m));
                            // 月を変えたら第1週に戻す（表示の空振り防止）
                            setSelectedWeek(1);
                        }}
                        className="business-hours-month-select"
                    >
                        {months.map((opt) => {
                            const v = `${opt.year}-${String(opt.month).padStart(2, "0")}`;
                            return (
                                <option key={v} value={v}>
                                    {opt.year}年 {opt.month}月（{opt.label}）
                                </option>
                            );
                        })}
                    </select>

                    {/* 週タブ */}
                    <div className="business-hours-week-tabs">
                        {[1, 2, 3, 4, 5].map((week) => (
                            <button
                                key={week}
                                onClick={() => setSelectedWeek(week)}
                                className={
                                    "business-hours-week-button" +
                                    (selectedWeek === week
                                        ? " business-hours-week-button--active"
                                        : "")
                                }
                            >
                                第{toFullWidth(week)}週
                            </button>
                        ))}
                    </div>
                </div>

                {/* テーブル */}
                <div className="business-hours-table-wrapper">
                    <table className="business-hours-table">
                        <thead>
                            <tr>
                                <th>曜日</th>
                                <th>開店時間</th>
                                <th>閉店時間</th>
                                <th>休業日</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredHours.map((h) => {
                                const idx = hours.findIndex(
                                    (x) =>
                                        Number(x.year) === Number(h.year) &&
                                        Number(x.month) === Number(h.month) &&
                                        Number(x.week_of_month) === Number(h.week_of_month) &&
                                        x.day_of_week === h.day_of_week
                                );

                                return (
                                    <tr
                                        key={`${h.year}-${h.month}-${h.week_of_month}-${h.day_of_week}`}
                                    >
                                        <td className="business-hours-day-cell">
                                            {h.day_of_week}
                                        </td>
                                        <td>
                                            <input
                                                type="time"
                                                step="900" // 15分単位
                                                value={h.open_time || ""}
                                                onChange={(e) =>
                                                    handleChange(
                                                        idx,
                                                        "open_time",
                                                        e.target.value
                                                    )
                                                }
                                                disabled={!!h.is_closed}
                                                className="business-hours-time-input"
                                            />
                                        </td>
                                        <td>
                                            <input
                                                type="time"
                                                step="900" // 15分単位
                                                value={h.close_time || ""}
                                                onChange={(e) =>
                                                    handleChange(
                                                        idx,
                                                        "close_time",
                                                        e.target.value
                                                    )
                                                }
                                                disabled={!!h.is_closed}
                                                className="business-hours-time-input"
                                            />
                                        </td>
                                        <td>
                                            <input
                                                type="checkbox"
                                                checked={!!h.is_closed}
                                                onChange={(e) =>
                                                    handleChange(
                                                        idx,
                                                        "is_closed",
                                                        e.target.checked
                                                    )
                                                }
                                                className="business-hours-closed-checkbox"
                                            />
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                {/* 保存ボタン */}
                <div className="business-hours-save-area">
                    <button
                        onClick={handleSave}
                        className="business-hours-save-button"
                    >
                        保存する
                    </button>
                </div>
            </div>
        </div>
    );
}
