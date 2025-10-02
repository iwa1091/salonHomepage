import { useState, useEffect } from "react";
import Calendar from "react-calendar";
import "react-calendar/dist/Calendar.css";

export default function ReservationForm({ initialServiceId }) {
    const [date, setDate] = useState(new Date());
    const [selectedTime, setSelectedTime] = useState("");
    const [formData, setFormData] = useState({
        service_id: initialServiceId || "",
        name: "",
        email: "",
        phone: "",
        notes: "",
    });
    const [services, setServices] = useState([]);
    const [message, setMessage] = useState("");

    // サービス一覧取得
    useEffect(() => {
        async function fetchServices() {
            const res = await fetch("/api/services");
            if (res.ok) {
                const data = await res.json();
                setServices(data);
            }
        }
        fetchServices();
    }, []);

    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const payload = {
            ...formData,
            date: date.toISOString().split("T")[0],
            start_time: selectedTime,
        };
        try {
            const res = await fetch("/api/reservations", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            });
            if (res.ok) {
                setMessage("予約が完了しました！");
            } else {
                const errorData = await res.json();
                setMessage(errorData.message || "予約に失敗しました");
            }
        } catch {
            setMessage("通信エラーが発生しました");
        }
    };

    // 30分刻み時間スロット
    const generateTimeSlots = (start, end, interval) => {
        const slots = [];
        let [hour, minute] = start.split(":").map(Number);
        const [endHour, endMinute] = end.split(":").map(Number);
        while (hour < endHour || (hour === endHour && minute <= endMinute)) {
            slots.push(`${String(hour).padStart(2, "0")}:${String(minute).padStart(2, "0")}`);
            minute += interval;
            if (minute >= 60) { hour += 1; minute -= 60; }
        }
        return slots;
    };
    const timeSlots = [...generateTimeSlots("09:00", "12:30", 30), ...generateTimeSlots("13:00", "17:00", 30)];

    return (
        <main className="max-w-3xl mx-auto p-6">
            <h1 className="text-2xl font-bold text-center mb-6">予約フォーム</h1>

            {message && <p className="text-red-600 mb-4 text-center">{message}</p>}

            <form onSubmit={handleSubmit} className="space-y-6 bg-white p-6 rounded-lg shadow">
                <div>
                    <label>メニュー</label>
                    <select name="service_id" value={formData.service_id} onChange={handleChange} required>
                        <option value="">選択してください</option>
                        {services.map(s => (
                            <option key={s.id} value={s.id}>{s.name}（¥{s.price} / {s.duration}分）</option>
                        ))}
                    </select>
                </div>

                <div>
                    <label>お名前</label>
                    <input type="text" name="name" value={formData.name} onChange={handleChange} required />
                </div>

                <div>
                    <label>メール</label>
                    <input type="email" name="email" value={formData.email} onChange={handleChange} required />
                </div>

                <div>
                    <label>電話番号</label>
                    <input type="tel" name="phone" value={formData.phone} onChange={handleChange} required />
                </div>

                <div>
                    <label>ご希望日</label>
                    <Calendar value={date} onChange={setDate} />
                    <p>選択日: {date.toLocaleDateString()}</p>
                </div>

                <div>
                    <label>ご希望時間</label>
                    <div className="grid grid-cols-3 gap-2">
                        {timeSlots.map(t => (
                            <button
                                key={t} type="button"
                                className={`px-3 py-2 rounded border ${selectedTime === t ? "bg-indigo-600 text-white" : "bg-white"}`}
                                onClick={() => setSelectedTime(t)}
                            >
                                {t}
                            </button>
                        ))}
                    </div>
                    {selectedTime && <p>選択時間: {selectedTime}</p>}
                </div>

                <div>
                    <label>備考</label>
                    <textarea name="notes" value={formData.notes} onChange={handleChange}></textarea>
                </div>

                <button type="submit" className="w-full bg-indigo-600 text-white py-2 px-4 rounded">予約する</button>
            </form>
        </main>
    );
}
