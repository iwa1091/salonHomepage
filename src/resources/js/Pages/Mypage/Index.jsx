import { Head, Link } from '@inertiajs/react';

export default function Mypage({ user, pastReservations, pastOrders, upcomingReservations }) {
    return (
        <div className="min-h-screen bg-[#faf7f4] pb-24">
            <Head title="マイページ" />

            {/* ページヘッダー */}
            <header className="bg-white shadow-sm py-6 px-6 md:px-10">
                <h1 className="text-2xl md:text-3xl font-bold text-[var(--salon-brown)]">
                    ようこそ、{user?.name} さん
                </h1>
                <p className="text-gray-600 mt-1">
                    ご予約履歴やお気に入りメニューをいつでも確認できます
                </p>
            </header>

            {/* コンテンツ */}
            <main className="max-w-4xl mx-auto px-6 md:px-10 mt-8 space-y-10">

                {/* 予約コード紐付け */}
                <section className="bg-white p-6 rounded-xl shadow-sm border">
                    <h2 className="text-lg md:text-xl font-semibold text-[var(--salon-brown)] mb-4">
                        🔗 予約番号を紐付ける
                    </h2>
                    <form method="POST" action="/mypage/link-reservation" className="space-y-4">
                        <input
                            type="text"
                            name="reservation_code"
                            placeholder="予約番号を入力してください"
                            className="border rounded-lg w-full p-3 bg-[#fafafa]"
                            required
                        />
                        <button
                            type="submit"
                            className="w-full bg-[var(--salon-brown)] text-white py-3 rounded-lg font-semibold hover:bg-[var(--salon-gold)] transition"
                        >
                            予約を紐付ける
                        </button>
                    </form>
                </section>

                {/* 予約中 */}
                <section className="bg-white p-6 rounded-xl shadow-sm border">
                    <h2 className="text-lg md:text-xl font-semibold text-[var(--salon-brown)] mb-4">
                        📅 予約中のメニュー
                    </h2>

                    {upcomingReservations?.length ? (
                        upcomingReservations.map((res) => (
                            <div key={res.id} className="p-4 border rounded-xl mb-3 bg-[#fafafa]">
                                <p className="font-bold">{res.service?.name}</p>
                                <p className="text-sm text-gray-600 mt-1">
                                    来店日：{res.date ? new Date(res.date).toLocaleDateString() : '-'}
                                </p>
                                <p className="text-sm text-gray-600">
                                    開始時間：{res.start_time ?? '-'}
                                </p>
                            </div>
                        ))
                    ) : (
                        <p className="text-gray-500">現在予約はありません。</p>
                    )}
                </section>

                {/* 過去の予約 */}
                <section className="bg-white p-6 rounded-xl shadow-sm border">
                    <h2 className="text-lg md:text-xl font-semibold text-[var(--salon-brown)] mb-4">
                        🕘 過去のメニュー
                    </h2>

                    {pastReservations?.length ? (
                        pastReservations.map((r) => (
                            <div key={r.id} className="p-4 border rounded-xl mb-3 bg-[#fafafa]">
                                <p className="font-bold">{r.service?.name}</p>
                                <p className="text-sm text-gray-500">
                                    来店日：{r.date ? new Date(r.date).toLocaleDateString() : '-'}
                                </p>
                                <Link
                                    href={`/reservation?repeat=${r.service?.id}`}
                                    className="text-blue-500 text-sm mt-2 inline-block"
                                >
                                    このメニューを再予約 →
                                </Link>
                            </div>
                        ))
                    ) : (
                        <p className="text-gray-500">過去の予約はありません。</p>
                    )}
                </section>

                {/* 購入履歴 */}
                <section className="bg-white p-6 rounded-xl shadow-sm border">
                    <h2 className="text-lg md:text-xl font-semibold text-[var(--salon-brown)] mb-4">
                        🛍 購入履歴
                    </h2>

                    {pastOrders?.length ? (
                        pastOrders.map((o) => (
                            <div key={o.id} className="p-4 border rounded-xl mb-3 bg-[#fafafa]">
                                <p className="font-bold">{o.product?.name}</p>
                                <p className="text-sm text-gray-500">
                                    購入日：{o.ordered_at ? new Date(o.ordered_at).toLocaleDateString() : '-'}
                                </p>
                                <Link
                                    href={`/online-store/products/${o.product?.id}`}
                                    className="text-blue-500 text-sm mt-2 inline-block"
                                >
                                    再購入 →
                                </Link>
                            </div>
                        ))
                    ) : (
                        <p className="text-gray-500">購入履歴はありません。</p>
                    )}
                </section>
            </main>

            {/* 固定表示：トップに戻る */}
            <a
                href="/"
                className="fixed bottom-6 right-6 bg-[var(--salon-brown)] text-white p-4 rounded-full shadow-lg hover:bg-[var(--salon-gold)] transition"
            >
                ⬆ ホームに戻る
            </a>
        </div>
    );
}
