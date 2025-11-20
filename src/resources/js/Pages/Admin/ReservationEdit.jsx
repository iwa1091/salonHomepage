import { useState, useEffect } from "react";
import { router, usePage } from "@inertiajs/react";
import Calendar from "react-calendar";
import "react-calendar/dist/Calendar.css";

/**
 * 管理者用 予約編集ページ
 * 営業時間 (business_hours) に基づいて、選択可能な日と時間を制限
 */
export default function ReservationEdit() {
    const { reservation } = usePage().props;
    const [formData, setFormData] = useState({
        name: reservation.name,
        date: reservation.date,
        start_time: reservation.start_time,
    });

    const [businessHours, setBusinessHours] = useState([]);
    const [availableTimes, setAvailableTimes] = useState([]);

    // 営業時間データ取得
    useEffect(() => {
        async function fetchBusinessHours() {
            const res = await fetch("/api/business-hours");
            if (res.ok) {
                const data = await res.json();
                setBusinessHours(data);
            }
        }
        fetchBusinessHours();
    }, []);

    // 営業日判定関数
    const tileDisabled = ({ date }) => {
        const dayNames = ["日", "月", "火", "水", "木", "金", "土"];
        const target = businessHours.find(
            (b) => b.day_of_week === dayNames[date.getDay()]
        );
        return !target || target.is_closed;
    };

    // 営業時間に基づいた時間スロット生成
    useEffect(() => {
        if (!formData.date || businessHours.length === 0) return;

        const dayNames = ["日", "月", "火", "水", "木", "金", "土"];
        const selectedDate = new Date(formData.date);
        const target = businessHours.find(
            (b) => b.day_of_week === dayNames[selectedDate.getDay()]
        );

        if (!target || target.is_closed) {
            setAvailableTimes([]);
            return;
        }

        const slots = [];
        let [h, m] = target.open_time.split(":").map(Number);
        const [endH, endM] = target.close_time.split(":").map(Number);

        while (h < endH || (h === endH && m < endM)) {
            slots.push(`${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}`);
            m += 30;
            if (m >= 60) {
                h++;
                m -= 60;
            }
        }

        setAvailableTimes(slots);
    }, [formData.date, businessHours]);

    // 入力変更
    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    // カレンダー変更
    const handleDateChange = (date) => {
        setFormData({ ...formData, date: date.toISOString().split("T")[0] });
    };

    // 更新処理
    const handleSubmit = (e) => {
        e.preventDefault();
        router.put(route("admin.reservations.update", reservation.id), formData);
    };

    return (
        <div className="max-w-lg mx-auto p-6 bg-white rounded shadow">
            <h1 className="text-xl font-bold mb-4">予約編集</h1>

            <form onSubmit={handleSubmit} className="space-y-5">
                {/* 氏名 */}
                <div>
                    <label className="block text-gray-700 mb-1">氏名</label>
                    <input
                        type="text"
                        name="name"
                        value={formData.name}
                        onChange={handleChange}
                        className="border w-full p-2 rounded"
                    />
                </div>

                {/* カレンダー */}
                <div>
                    <label className="block text-gray-700 mb-1">日付</label>
                    <Calendar
                        value={new Date(formData.date)}
                        onChange={handleDateChange}
                        tileDisabled={tileDisabled}
                    />
                    <p className="mt-2 text-sm text-gray-600">
                        選択日: {formData.date}
                    </p>
                </div>

                {/* 営業時間に基づく選択可能時間 */}
                <div>
                    <label className="block text-gray-700 mb-1">時間</label>
                    <div className="grid grid-cols-3 gap-2">
                        {availableTimes.length > 0 ? (
                            availableTimes.map((time) => (
                                <button
                                    key={time}
                                    type="button"
                                    onClick={() =>
                                        setFormData({ ...formData, start_time: time })
                                    }
                                    className={`px-3 py-2 rounded border text-sm ${formData.start_time === time
                                            ? "bg-indigo-600 text-white"
                                            : "bg-white hover:bg-gray-100"
                                        }`}
                                >
                                    {time}
                                </button>
                            ))
                        ) : (
                            <p className="col-span-3 text-gray-500 text-sm">
                                営業時間外または休業日です
                            </p>
                        )}
                    </div>
                </div>

                {/* 更新ボタン */}
                <button
                    type="submit"
                    className="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700"
                >
                    更新
                </button>
            </form>
        </div>
    );
}
