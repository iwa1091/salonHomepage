import { useState, useEffect } from "react";

export default function BusinessHours() {
    const [hours, setHours] = useState([]);
    const [loading, setLoading] = useState(true);
    const [message, setMessage] = useState("");
    const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1); // 今月
    const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
    const [selectedWeek, setSelectedWeek] = useState(1);

    // 営業時間を取得
    const fetchWeeklyHours = async (year, month) => {
        setLoading(true);
        try {
            const res = await fetch(`/api/business-hours/weekly?year=${year}&month=${month}`);
            const data = await res.json();
            setHours(data);
        } catch (err) {
            console.error("営業時間取得失敗:", err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchWeeklyHours(selectedYear, selectedMonth);
    }, [selectedYear, selectedMonth]);

    // 値の変更ハンドラ
    const handleChange = (index, field, value) => {
        const updated = [...hours];
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
            const res = await fetch("/api/business-hours/weekly", {
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(hours),
            });

            if (res.ok) {
                setMessage("営業時間を更新しました。");
                setTimeout(() => setMessage(""), 3000);
            } else {
                setMessage("更新に失敗しました。");
            }
        } catch (err) {
            console.error("更新エラー:", err);
            setMessage("サーバー通信エラーが発生しました。");
        }
    };

    // 表示する週データをフィルタ
    const filteredHours = hours.filter((h) => h.week_of_month === selectedWeek);

    // 月のプルダウン（今月・来月）
    const months = [
        { label: "今月", value: new Date().getMonth() + 1 },
        { label: "来月", value: new Date().getMonth() + 2 > 12 ? 1 : new Date().getMonth() + 2 },
    ];

    if (loading) {
        return <p className="text-center mt-8">読み込み中...</p>;
    }

    return (
        <div className="max-w-6xl mx-auto p-6 bg-white rounded-lg shadow">
            <h1 className="text-2xl font-bold mb-6 text-[var(--salon-brown)]">
                営業日・営業時間設定（週単位・30分刻み）
            </h1>

            {message && (
                <p className="mb-4 text-green-600 text-center font-semibold">{message}</p>
            )}

            {/* 月・週セレクト */}
            <div className="flex justify-center gap-4 mb-6">
                {/* 年・月セレクト */}
                <select
                    value={selectedMonth}
                    onChange={(e) => setSelectedMonth(Number(e.target.value))}
                    className="border rounded px-3 py-2"
                >
                    {months.map((m) => (
                        <option key={m.value} value={m.value}>
                            {selectedYear}年 {m.value}月（{m.label}）
                        </option>
                    ))}
                </select>

                {/* 週タブ */}
                <div className="flex space-x-2">
                    {[1, 2, 3, 4, 5].map((week) => (
                        <button
                            key={week}
                            onClick={() => setSelectedWeek(week)}
                            className={`px-4 py-2 rounded-md font-semibold ${selectedWeek === week
                                    ? "bg-[var(--salon-brown)] text-white"
                                    : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                                }`}
                        >
                            第{week}週
                        </button>
                    ))}
                </div>
            </div>

            {/* テーブル */}
            <div className="overflow-x-auto">
                <table className="w-full border border-gray-300 rounded-lg overflow-hidden">
                    <thead className="bg-[var(--salon-beige)] text-[var(--salon-brown)]">
                        <tr>
                            <th className="p-3 border">曜日</th>
                            <th className="p-3 border">開店時間</th>
                            <th className="p-3 border">閉店時間</th>
                            <th className="p-3 border">休業日</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredHours.map((h, i) => (
                            <tr key={`${h.day_of_week}-${h.week_of_month}`} className="text-center">
                                <td className="border p-2 font-semibold">{h.day_of_week}</td>
                                <td className="border p-2">
                                    <input
                                        type="time"
                                        step="1800" // 30分単位
                                        value={h.open_time || ""}
                                        onChange={(e) =>
                                            handleChange(
                                                hours.indexOf(h),
                                                "open_time",
                                                e.target.value
                                            )
                                        }
                                        disabled={h.is_closed}
                                        className="border rounded px-2 py-1 w-28"
                                    />
                                </td>
                                <td className="border p-2">
                                    <input
                                        type="time"
                                        step="1800"
                                        value={h.close_time || ""}
                                        onChange={(e) =>
                                            handleChange(
                                                hours.indexOf(h),
                                                "close_time",
                                                e.target.value
                                            )
                                        }
                                        disabled={h.is_closed}
                                        className="border rounded px-2 py-1 w-28"
                                    />
                                </td>
                                <td className="border p-2">
                                    <input
                                        type="checkbox"
                                        checked={h.is_closed}
                                        onChange={(e) =>
                                            handleChange(
                                                hours.indexOf(h),
                                                "is_closed",
                                                e.target.checked
                                            )
                                        }
                                        className="w-5 h-5 accent-[var(--salon-brown)]"
                                    />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* 保存ボタン */}
            <div className="text-center mt-6">
                <button
                    onClick={handleSave}
                    className="bg-[var(--salon-brown)] hover:bg-[var(--salon-gold)] text-white px-6 py-2 rounded-lg font-semibold transition"
                >
                    保存する
                </button>
            </div>
        </div>
    );
}
