// /resources/js/Pages/Admin/ReservationList.jsx
import { useEffect, useState } from "react";
import { Link, usePage, router } from "@inertiajs/react";
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

export default function ReservationList() {
    const { reservations: reservationsProp } = usePage().props;

    const [reservations, setReservations] = useState([]);
    const [businessHours, setBusinessHours] = useState([]);
    const [loading, setLoading] = useState(true);

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

                setBusinessHours(Array.isArray(res.data) ? res.data : []);
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
                setBusinessHours([]);
            }
        }
        fetchBusinessHours();
    }, []);

    // 予約の時間表示（営業中/営業時間外のラベルも付ける）
    const getFormattedTime = (date, startTimeRaw) => {
        const startTime = formatTimeToHHmm(startTimeRaw);

        // ✅ 営業時間が未取得の間はラベルを付けず、誤判定を避ける
        if (!businessHours || businessHours.length === 0) {
            return startTime;
        }

        const dayOfWeekNames = ["日", "月", "火", "水", "木", "金", "土"];
        const selectedDay = dayOfWeekNames[date.getDay()];

        // 営業時間データを取得（※週は考慮せず曜日ベースで判定＝既存仕様のまま）
        const hourInfo = businessHours.find((h) => h.day_of_week === selectedDay);

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
        </div>
    );
}
