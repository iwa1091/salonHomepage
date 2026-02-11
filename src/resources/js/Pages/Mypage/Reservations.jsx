// /resources/js/Pages/Mypage/Reservations.jsx
import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { useMemo } from "react";

// 既存のマイページ共通デザイン（既に Index.jsx で使用しているもの）
import "../../../css/pages/admin/mypage/index.css";

// マイページ専用CSS（はみ出し防止 / date押しやすさ改善 等）
import "../../../css/pages/mypage/mypage.css";

/**
 * YYYY-MM-DD をローカルDateにする（new Date("YYYY-MM-DD")のズレ回避）
 */
function parseYmdToLocalDate(ymd) {
    const s = String(ymd || "");
    const [y, m, d] = s.split("-").map((v) => Number(v));
    if (!y || !m || !d) return new Date();
    return new Date(y, m - 1, d, 0, 0, 0);
}

/**
 * 予約の date + start_time から Date を作る（start_time は HH:mm / HH:mm:ss を許容）
 */
function reservationToDateTime(r) {
    const dateObj = parseYmdToLocalDate(r?.date);
    const t = String(r?.start_time ?? "").trim();
    const hh = Number(t.slice(0, 2) || 0);
    const mm = Number(t.slice(3, 5) || 0);

    return new Date(
        dateObj.getFullYear(),
        dateObj.getMonth(),
        dateObj.getDate(),
        hh,
        mm,
        0
    );
}

/**
 * errors は string/配列どちらでも来る可能性があるので吸収
 */
function firstErrorText(v) {
    if (!v) return "";
    if (Array.isArray(v)) return String(v[0] ?? "");
    return String(v);
}

export default function Reservations({
    user,
    // どの形で渡ってきても表示できるように吸収（念のため）
    reservations,
    upcomingReservations,
    pastReservations,
}) {
    const { flash, errors } = usePage().props;

    // キャンセルボタンの二重クリック抑止用（Index.jsx と同じ思想）
    const cancelForm = useForm({});

    // 「/mypage/reservations」へ渡ってくるデータ形式が不明でも壊れないように統合
    const allReservations = useMemo(() => {
        const src = [];
        if (Array.isArray(reservations)) src.push(...reservations);
        if (Array.isArray(upcomingReservations)) src.push(...upcomingReservations);
        if (Array.isArray(pastReservations)) src.push(...pastReservations);

        // id で重複排除
        const map = new Map();
        src.forEach((r) => {
            const id = r?.id;
            if (!id) return;
            if (!map.has(String(id))) map.set(String(id), r);
        });
        return Array.from(map.values());
    }, [reservations, upcomingReservations, pastReservations]);

    // キャンセル済み判定（表記ゆれ吸収）
    const canceledSet = useMemo(
        () =>
            new Set([
                "canceled",
                "cancelled",
                "cancel",
                "canceled_by_user",
                "canceled_by_admin",
            ]),
        []
    );

    // 未来/過去に振り分け（コントローラ側で分けて渡していても、この画面で整形）
    const { upcoming, past } = useMemo(() => {
        const now = new Date();
        const upcomingList = [];
        const pastList = [];

        allReservations.forEach((r) => {
            const dt = reservationToDateTime(r);
            if (dt.getTime() >= now.getTime()) upcomingList.push(r);
            else pastList.push(r);
        });

        // 予約中：昇順 / 過去：降順
        upcomingList.sort((a, b) => reservationToDateTime(a) - reservationToDateTime(b));
        pastList.sort((a, b) => reservationToDateTime(b) - reservationToDateTime(a));

        return { upcoming: upcomingList, past: pastList };
    }, [allReservations]);

    // ✅ キャンセル（confirm へ）
    const handleCancel = (reservationId) => {
        const ok = window.confirm(
            "キャンセル確認画面へ進みますか？\n（キャンセル理由の入力後に確定します）"
        );
        if (!ok) return;

        window.location.href = `/mypage/reservations/${reservationId}/cancel/confirm`;
    };

    const back = () => {
        // 「前のページに戻る」要件：履歴が無い場合でも破綻しにくいようにフォールバック
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = "/mypage";
        }
    };

    const pageErrorText =
        firstErrorText(errors?.message) ||
        firstErrorText(errors?.reservation_id) ||
        "";

    return (
        <div
            className="mypage-root mypage-container"
            style={{
                padding: 0,
                backgroundColor: "transparent",
                minHeight: "auto",
            }}
        >
            <Head title="予約一覧" />

            {/* 上部バー（左上：戻る） */}
            <div
                style={{
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "space-between",
                    gap: "0.75rem",
                    marginBottom: "1rem",
                }}
            >
                <button
                    type="button"
                    onClick={back}
                    className="mypage-outline-button"
                    disabled={cancelForm.processing}
                    style={{ width: "auto" }}
                >
                    ← 戻る
                </button>

                <Link
                    href="/mypage"
                    className="mypage-inline-link mypage-inline-link--compact"
                >
                    マイページへ →
                </Link>
            </div>

            {/* ページヘッダー */}
            <header className="mypage-header">
                <h1 className="mypage-header-title">
                    予約一覧{user?.name ? `（${user.name} さん）` : ""}
                </h1>
                <p className="mypage-header-subtitle">
                    予約中・過去の予約を確認できます
                </p>
            </header>

            <main className="mypage-main">
                {/* フラッシュ */}
                {flash?.success && (
                    <p className="mypage-flash-success">{flash.success}</p>
                )}
                {flash?.message && (
                    <p className="mypage-flash-info">{flash.message}</p>
                )}
                {pageErrorText && (
                    <p className="mypage-flash-error">{pageErrorText}</p>
                )}

                {/* 予約中 */}
                <section className="mypage-section-card">
                    <h2 className="mypage-section-title">📅 予約中</h2>

                    {upcoming.length ? (
                        upcoming.map((r) => {
                            const isCanceled = canceledSet.has(String(r?.status ?? ""));
                            const dt = reservationToDateTime(r);

                            return (
                                <div key={r.id} className="mypage-item-card">
                                    <p className="mypage-item-title">
                                        {r?.service?.name ?? "（メニュー不明）"}
                                    </p>

                                    <p className="mypage-item-meta">
                                        来店日：{r?.date ? dt.toLocaleDateString() : "-"}
                                    </p>
                                    <p className="mypage-item-meta">
                                        開始時間：{String(r?.start_time ?? "-").slice(0, 5)}
                                    </p>

                                    {isCanceled && (
                                        <p className="mypage-empty-text">
                                            ※ この予約はキャンセル済みです
                                        </p>
                                    )}

                                    <div className="mypage-item-actions">
                                        <Link
                                            href="/mypage"
                                            className="mypage-inline-link mypage-inline-link--compact"
                                        >
                                            マイページへ →
                                        </Link>

                                        {!isCanceled && (
                                            <button
                                                type="button"
                                                className="mypage-danger-button"
                                                onClick={() => handleCancel(r.id)}
                                                disabled={cancelForm.processing}
                                            >
                                                {cancelForm.processing ? "処理中..." : "キャンセルする"}
                                            </button>
                                        )}
                                    </div>
                                </div>
                            );
                        })
                    ) : (
                        <p className="mypage-empty-text">現在予約はありません。</p>
                    )}
                </section>

                {/* 過去 */}
                <section className="mypage-section-card">
                    <h2 className="mypage-section-title">🕘 過去の予約</h2>

                    {past.length ? (
                        past.map((r) => {
                            const dt = reservationToDateTime(r);
                            const isCanceled = canceledSet.has(String(r?.status ?? ""));

                            return (
                                <div key={r.id} className="mypage-item-card">
                                    <p className="mypage-item-title">
                                        {r?.service?.name ?? "（メニュー不明）"}
                                    </p>

                                    <p className="mypage-item-meta">
                                        来店日：{r?.date ? dt.toLocaleDateString() : "-"}
                                    </p>
                                    <p className="mypage-item-meta">
                                        開始時間：{String(r?.start_time ?? "-").slice(0, 5)}
                                    </p>

                                    {isCanceled && (
                                        <p className="mypage-empty-text">
                                            ※ この予約はキャンセル済みです
                                        </p>
                                    )}

                                    {/* 詳細ページのルートが未確定なので、この画面では無理に貼らない（不一致防止） */}
                                </div>
                            );
                        })
                    ) : (
                        <p className="mypage-empty-text">過去の予約はありません。</p>
                    )}
                </section>
            </main>

            {/* ホームへ戻る（固定） */}
            <a href="/" className="mypage-home-fab">
                ⬆ ホームに戻る
            </a>
        </div>
    );
}