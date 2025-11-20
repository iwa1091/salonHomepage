import { useState, useEffect } from "react";
import Calendar from "react-calendar";
import "react-calendar/dist/Calendar.css";

/**
 * 30分刻みで時間スロットを生成
 */
function generateTimeSlots(start, end, interval = 30) {
    const slots = [];
    if (!start || !end) return slots;

    let [hour, minute] = start.split(":").map(Number);
    const [endHour, endMinute] = end.split(":").map(Number);

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
    const [services, setServices] = useState([]);
    const [businessHours, setBusinessHours] = useState([]);
    const [availableTimes, setAvailableTimes] = useState([]);
    const [message, setMessage] = useState("");

    /**
     * 🟡 URLパラメータから service_id を初期セット
     */
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const serviceId = params.get("service_id");
        if (serviceId) {
            setFormData((prev) => ({ ...prev, service_id: serviceId }));
        }
    }, []);

    /**
     * サービス一覧をロード
     */
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

    /**
     * 営業時間をロード（今月分）
     */
    useEffect(() => {
        async function fetchBusinessHours() {
            try {
                const today = new Date();
                const year = today.getFullYear();
                const month = today.getMonth() + 1;

                const res = await fetch(`/api/business-hours/weekly?year=${year}&month=${month}`);
                if (res.ok) {
                    const data = await res.json();
                    setBusinessHours(data);
                }
            } catch (err) {
                console.error("営業時間の取得に失敗:", err);
            }
        }
        fetchBusinessHours();
    }, []);

    /**
     * 選択された日付に応じて予約可能時間を更新
     */
    useEffect(() => {
        if (businessHours.length === 0) return;

        const dayOfWeekNames = ["日", "月", "火", "水", "木", "金", "土"];
        const selectedDay = dayOfWeekNames[date.getDay()];
        const weekOfMonth = Math.ceil(date.getDate() / 7);

        const hourInfo = businessHours.find(
            (h) => h.day_of_week === selectedDay && h.week_of_month === weekOfMonth
        );

        if (!hourInfo || hourInfo.is_closed) {
            setAvailableTimes([]);
        } else {
            const slots = generateTimeSlots(hourInfo.open_time, hourInfo.close_time, 30);
            setAvailableTimes(slots);
        }
    }, [date, businessHours]);

    /**
     * カレンダーの無効化（日曜など休業日）
     */
    const tileDisabled = ({ date }) => {
        const dayOfWeekNames = ["日", "月", "火", "水", "木", "金", "土"];
        const selectedDay = dayOfWeekNames[date.getDay()];
        const weekOfMonth = Math.ceil(date.getDate() / 7);

        const dayInfo = businessHours.find(
            (h) => h.day_of_week === selectedDay && h.week_of_month === weekOfMonth
        );

        return !dayInfo || dayInfo.is_closed;
    };

    /**
     * 入力変更ハンドラ
     */
    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    /**
     * 📨 送信処理
     */
    const handleSubmit = async (e) => {
        e.preventDefault();
        setMessage("");

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
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                const data = await response.json();
                setMessage("✅ ご予約が完了しました！メールをご確認ください。");
                console.log("予約成功:", data);

                // 入力リセット
                setSelectedTime("");
                setFormData({
                    name: "",
                    phone: "",
                    service_id: "",
                    email: "",
                    notes: "",
                });
            } else if (response.status === 422) {
                const errorData = await response.json();
                setMessage("⚠️ 入力内容を確認してください（" + Object.values(errorData.errors).join("、") + "）");
            } else {
                const errorData = await response.json();
                setMessage(errorData.message || "⚠️ 予約に失敗しました。");
            }
        } catch (err) {
            console.error("送信エラー:", err);
            setMessage("⚠️ サーバー通信エラーが発生しました。");
        }
    };

    return (
        <main className="flex-1 max-w-3xl mx-auto p-6">
            <h1 className="text-2xl font-bold text-center mb-6 text-[var(--salon-brown)]">
                ご予約フォーム
            </h1>

            {message && (
                <p
                    className={`mb-4 text-center font-medium ${message.includes("✅")
                        ? "text-green-600"
                        : "text-red-600"
                        }`}
                >
                    {message}
                </p>
            )}

            <form
                onSubmit={handleSubmit}
                className="space-y-6 bg-white p-6 rounded-lg shadow"
            >
                {/* 名前 */}
                <div>
                    <label className="block text-gray-700 font-medium mb-2">お名前</label>
                    <input
                        type="text"
                        name="name"
                        value={formData.name}
                        onChange={handleChange}
                        required
                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                    />
                </div>

                {/* メール */}
                <div>
                    <label className="block text-gray-700 font-medium mb-2">
                        メールアドレス
                    </label>
                    <input
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        required
                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                    />
                </div>

                {/* 電話番号 */}
                <div>
                    <label className="block text-gray-700 font-medium mb-2">
                        電話番号
                    </label>
                    <input
                        type="tel"
                        name="phone"
                        value={formData.phone}
                        onChange={handleChange}
                        required
                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
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
                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                    >
                        <option value="">選択してください</option>
                        {services.map((service) => (
                            <option key={service.id} value={service.id}>
                                {service.name}（¥{service.price} / {service.duration_minutes}分）
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
                        tileDisabled={tileDisabled}
                        className="border rounded-lg p-2"
                    />
                    <p className="mt-2 text-sm text-gray-500">
                        選択された日付: {date.toLocaleDateString()}
                    </p>
                </div>

                {/* 時間枠選択 */}
                <div>
                    <label className="block text-gray-700 font-medium mb-2">ご希望時間</label>

                    {availableTimes.length === 0 ? (
                        <p className="text-gray-500 text-sm">
                            ※ この日は休業日または営業時間外です
                        </p>
                    ) : (
                        <div className="grid grid-cols-3 gap-2">
                            {availableTimes.map((time) => (
                                <button
                                    type="button"
                                    key={time}
                                    onClick={() => setSelectedTime(time)}
                                    className={`px-3 py-2 rounded-lg border transition ${selectedTime === time
                                        ? "bg-[var(--salon-brown)] text-white border-[var(--salon-brown)]"
                                        : "bg-white text-gray-700 border-gray-300 hover:bg-gray-100"
                                        }`}
                                >
                                    {time}
                                </button>
                            ))}
                        </div>
                    )}

                    {selectedTime && (
                        <p className="mt-2 text-sm text-gray-500">
                            選択された時間: {selectedTime}
                        </p>
                    )}
                </div>

                {/* 備考 */}
                <div>
                    <label className="block text-gray-700 font-medium mb-2">備考</label>
                    <textarea
                        name="notes"
                        value={formData.notes}
                        onChange={handleChange}
                        rows={3}
                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                    />
                </div>

                {/* 送信ボタン */}
                <button
                    type="submit"
                    className="w-full bg-[var(--salon-brown)] text-white font-semibold py-2 px-4 rounded-lg hover:bg-[var(--salon-gold)] transition"
                >
                    予約する
                </button>
            </form>
        </main>
    );
}
