import React from "react";
import { Link, usePage, router } from "@inertiajs/react";
import { motion } from "framer-motion";
import { route } from "ziggy-js"; // ✅ Ziggy v2以降は名前付きインポート

export default function Index() {
    const { products, filters } = usePage().props;

    //  検索フォームの送信処理
    const handleSearch = (e) => {
        e.preventDefault();
        const form = e.target;

        router.get(
            route("admin.products.index"),
            {
                keyword: form.keyword.value,
                sort: form.sort.value,
            },
            {
                preserveScroll: true, // ページをスクロール位置を維持
                preserveState: true,  // 状態を保持（再レンダー防止）
            }
        );
    };

    return (
        <motion.div
            className="store-page flex justify-center bg-[var(--salon-beige)] min-h-screen py-16"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
        >
            <div className="store-container bg-white rounded-2xl shadow-xl p-10 w-full max-w-[1200px]">
                {/* Header */}
                <div className="store-header text-center mb-16">
                    <h1 className="store-title text-4xl font-handwriting text-[var(--salon-brown)] mb-4">
                        商品管理
                    </h1>
                    <p className="text-[color:rgba(139,125,114,0.7)] max-w-2xl mx-auto">
                        オンラインストアで販売する商品の追加、編集、削除を行います。
                        在庫や価格設定を最新に保ちましょう。
                    </p>
                </div>

                {/* 検索・並び替えフォーム */}
                <form
                    onSubmit={handleSearch}
                    className="flex flex-wrap justify-between items-center gap-4 mb-8"
                >
                    <div className="flex gap-2 flex-wrap">
                        <input
                            type="text"
                            name="keyword"
                            placeholder="商品名で検索"
                            defaultValue={filters.keyword || ""}
                            className="border rounded-lg px-4 py-2"
                        />
                        <select
                            name="sort"
                            defaultValue={filters.sort || ""}
                            className="border rounded-lg px-4 py-2"
                        >
                            <option value="">価格で並び替え</option>
                            <option value="high_price">高い順</option>
                            <option value="low_price">低い順</option>
                        </select>
                        <button
                            type="submit"
                            className="bg-[var(--salon-brown)] text-white px-6 py-2 rounded-lg hover:bg-opacity-80 transition"
                        >
                            検索
                        </button>
                    </div>

                    {/* 新しい商品追加ボタン */}
                    <Link
                        href={route("admin.products.create")}
                        className="bg-[var(--salon-gold)] text-white px-6 py-2 rounded-lg hover:bg-opacity-90 flex items-center gap-2 transition"
                    >
                        <i className="fa-solid fa-plus-circle" />
                        新しい商品を追加
                    </Link>
                </form>

                {/* 商品一覧 */}
                <section className="product-grid grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {products.data.length > 0 ? (
                        products.data.map((product) => (
                            <Link
                                key={product.id}
                                href={route("admin.products.show", product.id)}
                                className="bg-white border rounded-xl shadow-md overflow-hidden hover:shadow-lg transition"
                            >
                                <div className="h-56 overflow-hidden">
                                    <img
                                        src={`/storage/${product.image_path}`}
                                        alt={product.name}
                                        className="w-full h-full object-cover"
                                    />
                                </div>
                                <div className="p-4">
                                    <h3 className="text-lg font-semibold text-[var(--salon-brown)] mb-1">
                                        {product.name}
                                    </h3>
                                    <p className="text-sm text-gray-600 mb-2">
                                        {product.description.slice(0, 50)}...
                                    </p>
                                    <span className="text-[var(--salon-gold)] font-bold text-lg">
                                        ¥{product.price.toLocaleString()}
                                    </span>
                                    <p
                                        className={`mt-1 text-sm font-semibold ${product.stock > 0
                                                ? "text-green-600"
                                                : "text-red-500"
                                            }`}
                                    >
                                        {product.stock > 0
                                            ? `在庫: ${product.stock} 点`
                                            : "在庫なし（売り切れ）"}
                                    </p>
                                </div>
                            </Link>
                        ))
                    ) : (
                        <p className="text-center text-gray-500 col-span-full">
                            商品が見つかりませんでした。
                        </p>
                    )}
                </section>

                {/* ページネーション */}
                <div className="mt-8 flex justify-center flex-wrap gap-2">
                    {products.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url || "#"}
                            className={`px-3 py-1 border rounded ${link.active
                                    ? "bg-[var(--salon-gold)] text-white"
                                    : "bg-white text-[var(--salon-brown)]"
                                }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            </div>

            {/*  右下固定の戻るボタン（スマホではアイコンのみ） */}
            <Link
                href={route("admin.dashboard")}
                className="fixed bottom-6 right-6 bg-[var(--salon-brown)] text-white px-5 py-3 rounded-full shadow-lg hover:bg-opacity-90 hover:scale-105 transition flex items-center gap-2"
            >
                <i className="fa-solid fa-arrow-left text-lg"></i>
                {/* md以上の画面でのみ表示 */}
                <span className="hidden md:inline font-medium">
                    ダッシュボードに戻る
                </span>
            </Link>
        </motion.div>
    );
}
