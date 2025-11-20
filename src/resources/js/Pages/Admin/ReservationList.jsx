import { useEffect, useState } from "react";
import { Link, router } from "@inertiajs/react";

export default function ReservationList() {
    const [reservations, setReservations] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        async function fetchReservations() {
            const res = await fetch("/api/admin/reservations");
            if (res.ok) {
                const data = await res.json();
                setReservations(data);
            }
            setLoading(false);
        }
        fetchReservations();
    }, []);

    const handleDelete = async (id) => {
        if (!confirm("この予約を削除しますか？")) return;
        const res = await fetch(`/api/admin/reservations/${id}`, {
            method: "DELETE",
        });
        if (res.ok) {
            setReservations((prev) => prev.filter((r) => r.id !== id));
        }
    };

    if (loading) return <p className="text-center">読み込み中...</p>;

    return (
        <div className="p-6">
            <h1 className="text-2xl font-bold mb-6">予約一覧</h1>

            <table className="w-full border-collapse border border-gray-300 text-sm">
                <thead className="bg-gray-100">
                    <tr>
                        <th className="border p-2">ID</th>
                        <th className="border p-2">氏名</th>
                        <th className="border p-2">メニュー</th>
                        <th className="border p-2">日付</th>
                        <th className="border p-2">時間</th>
                        <th className="border p-2">状態</th>
                        <th className="border p-2">操作</th>
                    </tr>
                </thead>
                <tbody>
                    {reservations.map((r) => (
                        <tr key={r.id}>
                            <td className="border p-2">{r.id}</td>
                            <td className="border p-2">{r.name}</td>
                            <td className="border p-2">{r.service_name}</td>
                            <td className="border p-2">{r.date}</td>
                            <td className="border p-2">{r.start_time}</td>
                            <td className="border p-2">{r.status || "予約中"}</td>
                            <td className="border p-2 flex gap-2">
                                <Link
                                    href={route("admin.reservations.edit", r.id)}
                                    className="px-2 py-1 bg-blue-500 text-white rounded"
                                >
                                    編集
                                </Link>
                                <button
                                    onClick={() => handleDelete(r.id)}
                                    className="px-2 py-1 bg-red-500 text-white rounded"
                                >
                                    削除
                                </button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
