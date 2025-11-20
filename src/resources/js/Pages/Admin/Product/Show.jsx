import React from "react";
import { Link, usePage, router } from "@inertiajs/react";
import { motion } from "framer-motion";

/**
 * 商品詳細ページ (Admin/Product/Show.jsx)
 * - Create.jsx と同じデザインコンセプト
 * - 商品の内容確認、編集・削除ボタンを配置
 */

export default function Show() {
    const { product, flash } = usePage().props;

    const handleDelete = () => {
        if (confirm("この商品を削除してもよろしいですか？")) {
            router.delete(route("admin.products.destroy", product.id), {
                onSuccess: () => {
                    alert("商品を削除しました。");
                },
            });
        }
    };

    return (
        <motion.div
            className="min-h-screen bg-[var(--salon-beige)] flex justify-center items-start py-16"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
        >
            <div className="bg-white p-8 rounded-2xl shadow-lg w-full max-w-[700px]">
                {/* タイトル */}
                <h1 className="text-3xl font-handwriting text-[var(--salon-brown)] mb-8 text-center">
                    商品詳細
                </h1>

                {/* フラッシュメッセージ */}
                {flash?.success && (
                    <p className="text-green-600 text-center mb-4 font-medium">
                        {flash.success}
                    </p>
                )}

                {/* 商品情報 */}
                <div className="space-y-6">
                    {/* 画像 */}
                    <div className="text-center">
                        <img
                            src={`/storage/${product.image_path}`}
                            alt={product.name}
                            className="max-h-[350px] mx-auto rounded-lg shadow-md object-contain"
                        />
                    </div>

                    {/* 商品名 */}
                    <div>
                        <h2 className="text-xl font-semibold text-[var(--salon-brown)] mb-1">
                            商品名
                        </h2>
                        <p className="text-[var(--salon-brown)] text-base bg-[var(--salon-beige)] px-4 py-2 rounded-lg">
                            {product.name}
                        </p>
                    </div>

                    {/* 価格 */}
                    <div>
                        <h2 className="text-xl font-semibold text-[var(--salon-brown)] mb-1">
                            価格
                        </h2>
                        <p className="text-[var(--salon-gold)] font-bold text-lg bg-[var(--salon-beige)] px-4 py-2 rounded-lg">
                            ¥{Number(product.price).toLocaleString()}
                        </p>
                    </div>

                    {/* 説明 */}
                    <div>
                        <h2 className="text-xl font-semibold text-[var(--salon-brown)] mb-1">
                            商品説明
                        </h2>
                        <p className="text-[var(--salon-brown)] leading-relaxed bg-[var(--salon-beige)] px-4 py-3 rounded-lg whitespace-pre-line">
                            {product.description}
                        </p>
                    </div>
                </div>

                {/* ボタン群 */}
                <div className="flex justify-between items-center mt-10">
                    {/* 戻る */}
                    <Link
                        href={route("admin.products.index")}
                        className="px-6 py-2 rounded-lg bg-gray-200 text-[var(--salon-brown)] hover:bg-gray-300 transition"
                    >
                        一覧へ戻る
                    </Link>

                    <div className="flex gap-4">
                        {/* 編集 */}
                        <Link
                            href={route("admin.products.edit", product.id)}
                            className="px-6 py-2 rounded-lg bg-[var(--salon-gold)] text-white hover:opacity-90 transition"
                        >
                            編集する
                        </Link>

                        {/* 削除 */}
                        <button
                            onClick={handleDelete}
                            className="px-6 py-2 rounded-lg bg-red-500 text-white hover:bg-red-600 transition"
                        >
                            削除
                        </button>
                    </div>
                </div>
            </div>
        </motion.div>
    );
}
