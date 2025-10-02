// /resources/js/Pages/Reservation/ReservationForm.jsx

import { useState, useEffect } from "react";
import Calendar from "react-calendar";
import "react-calendar/dist/Calendar.css";

// 30分刻みの時間リストを作成するヘルパー
function generateTimeSlots(start, end, interval) {
    const slots = [];
    let [hour, minute] = start.split(":").map(Number);
    const [endHour, endMinute] = end.split(":").map(Number);

    while (hour < endHour || (hour === endHour && minute <= endMinute)) {
        const time = `${String(hour).padStart(2, "0")}:${String(minute).padStart(2, "0")}`;
        slots.push(time);
        minute += interval;
        if (minute >= 60) {
            hour += 1;
            minute = minute - 60;
        }
    }
    return slots;
}

// 午前・午後のスロット
const morningSlots = generateTimeSlots("09:00", "12:30", 30);
const afternoonSlots = generateTimeSlots("13:00", "17:00", 30);

export default function ReservationForm() {
    const [date, setDate] = useState(new Date());
    const [selectedTime, setSelectedTime] = useState("");
    const [formData, setFormData] = useState({
        name: "",
        phone: "",
        service_id: "",
        email: "",
        notes: "",
    });
    const [services, setServices] = useState([]); // APIから取得するサービス一覧
    const [message, setMessage] = useState("");

    // サービス一覧をロード
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

    const handleChange = (e) => {
        setFormData({
            ...formData,
            [e.target.name]: e.target.value,
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!selectedTime) {
            setMessage("時間を選択してください。");
            return;
        }

        const payload = {
            ...formData,
            date: date.toISOString().split("T")[0],
            start_time: selectedTime,
        };

        try {
            const response = await fetch("/api/reservations", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                },
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                const data = await response.json();
                setMessage("予約が完了しました！");
                console.log("予約成功:", data);
            } else {
                const errorData = await response.json();
                setMessage(
                    errorData.message || "予約に失敗しました。入力内容を確認してください。"
                );
                console.log("予約エラー:", errorData);
            }
        } catch (err) {
            console.error(err);
            setMessage("通信エラーが発生しました。");
        }
    };

    return (
        <main className="flex-1 max-w-3xl mx-auto p-6">
            <h1 className="text-2xl font-bold text-center mb-6">予約フォーム</h1>

            {message && (
                <p className="mb-4 text-center text-red-600 font-medium">{message}</p>
            )}

            <form onSubmit={handleSubmit} className="space-y-6 bg-white p-6 rounded-lg shadow">
                {/* 名前 */}
                <div>
                    <label className="block text-gray-700 font-medium mb-2">お名前</label>
                    <input
                        type="text"
                        name="name"
                        value={formData.name}
                        onChange={handleChange}
                        required
                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-indigo-200"
                    />
                </div>

                {/* メール */}
                <div>
                    <label className="block text-gray-700 font-medium mb-2">メールアドレス</label>
                    <input
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        required
                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-indigo-200"
                    />
                </div>

                {/* 電話番号 */}
                <div>
                    <label className="block text-gray-700 font-medium mb-2">電話番号</label>
                    <input
                        type="tel"
                        name="phone"
                        value={formData.phone}
                        onChange={handleChange}
                        required
                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-indigo-200"
                    />
                </div>

                {/* メニュー選択 */}
                <div>
                    <label className="block text-gray-700 font-medium mb-2">メニュー</label>
                    <select
                        name="service_id"
                        value={formData.service_id}
                        onChange={handleChange}
                        required
                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-indigo-200"
                    >
                        <option value="">選択してください</option>
                        {services.map((service) => (
                            <option key={service.id} value={service.id}>
                                {service.name}（{service.price}円 / {service.duration_minutes}分）
                            </option>
                        ))}
                    </select>
                </div>

                {/* カレンダー */}
                <div>
                    <label className="block text-gray-700 font-medium mb-2">ご希望日</label>
                    <Calendar
                        onChange={setDate}
                        value={date}
                        className="border rounded-lg p-2"
                    />
                    <p className="mt-2 text-sm text-gray-500">
                        選択された日付: {date.toLocaleDateString()}
                    </p>
                </div>

                {/* 時間枠選択 */}
                <div>
                    <label className="block text-gray-700 font-medium mb-2">ご希望時間</label>
                    <div className="grid grid-cols-3 gap-2">
                        {[...morningSlots, ...afternoonSlots].map((time) => (
                            <button
                                type="button"
                                key={time}
                                onClick={() => setSelectedTime(time)}
                                className={`px-3 py-2 rounded-lg border ${selectedTime === time
                                        ? "bg-indigo-600 text-white border-indigo-600"
                                        : "bg-white text-gray-700 border-gray-300 hover:bg-gray-100"
                                    }`}
                            >
                                {time}
                            </button>
                        ))}
                    </div>
                    {selectedTime && (
                        <p className="mt-2 text-sm text-gray-500">
                            選択された時間: {selectedTime}
                        </p>
                    )}
                </div>

                {/* メモ */}
                <div>
                    <label className="block text-gray-700 font-medium mb-2">備考</label>
                    <textarea
                        name="notes"
                        value={formData.notes}
                        onChange={handleChange}
                        rows={3}
                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-indigo-200"
                    />
                </div>

                {/* 送信ボタン */}
                <button
                    type="submit"
                    className="w-full bg-indigo-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-indigo-700 transition"
                >
                    予約する
                </button>
            </form>
        </main>
    );
}
