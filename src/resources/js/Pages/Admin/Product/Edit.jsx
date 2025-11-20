import React, { useState } from "react";
import { useForm, router, usePage } from "@inertiajs/react";
import { motion } from "framer-motion";

/**
 * 商品編集ページ (Admin/Product/Edit.jsx)
 * - Create.jsx と同じUIをベースに既存データを編集可能に
 * - 商品名 / 価格 / 説明 / 画像更新
 * - 既存画像プレビュー + 新しい画像プレビュー対応
 */

export default function Edit() {
    const { product, flash } = usePage().props;

    // useForm で初期値を設定
    const { data, setData, post, processing, errors, reset } = useForm({
        name: product.name || "",
        price: product.price || "",
        description: product.description || "",
        image: null,
    });

    const [preview, setPreview] = useState(null);

    // ファイル選択時のプレビュー処理
    const handleFileChange = (e) => {
        const file = e.target.files[0];
        setData("image", file);
        if (file) {
            setPreview(URL.createObjectURL(file));
        }
    };

    // 送信処理（PATCHで送信）
    const handleSubmit = (e) => {
        e.preventDefault();

        router.post(route("admin.products.update", product.id), {
            _method: "patch", // LaravelにPATCHとして送る
            ...data,
            onSuccess: () => {
                alert("商品情報を更新しました。");
                reset("image");
                setPreview(null);
            },
        });
    };

    return (
        <motion.div
            className="min-h-screen bg-[var(--salon-beige)] flex justify-center items-start py-16"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
        >
            <div className="bg-white p-8 rounded-2xl shadow-lg w-full max-w-[600px]">
                <h1 className="text-3xl font-handwriting text-[var(--salon-brown)] mb-8 text-center">
                    商品編集
                </h1>

                {/* フラッシュメッセージ */}
                {flash?.success && (
                    <p className="text-green-600 text-center mb-4 font-medium">
                        {flash.success}
                    </p>
                )}

                <form onSubmit={handleSubmit} className="space-y-6" encType="multipart/form-data">
                    {/* 商品名 */}
                    <div>
                        <label className="block font-medium text-[var(--salon-brown)] mb-2">
                            商品名
                        </label>
                        <input
                            type="text"
                            name="name"
                            value={data.name}
                            onChange={(e) => setData("name", e.target.value)}
                            className="w-full border rounded-lg px-4 py-2"
                            placeholder="商品名を入力"
                        />
                        {errors.name && (
                            <p className="text-red-600 text-sm mt-1">{errors.name}</p>
                        )}
                    </div>

                    {/* 価格 */}
                    <div>
                        <label className="block font-medium text-[var(--salon-brown)] mb-2">
                            価格
                        </label>
                        <input
                            type="number"
                            name="price"
                            value={data.price}
                            onChange={(e) => setData("price", e.target.value)}
                            className="w-full border rounded-lg px-4 py-2"
                            placeholder="価格を入力"
                        />
                        {errors.price && (
                            <p className="text-red-600 text-sm mt-1">{errors.price}</p>
                        )}
                    </div>

                    {/* 商品説明 */}
                    <div>
                        <label className="block font-medium text-[var(--salon-brown)] mb-2">
                            商品説明
                        </label>
                        <textarea
                            name="description"
                            value={data.description}
                            onChange={(e) => setData("description", e.target.value)}
                            className="w-full border rounded-lg px-4 py-2"
                            placeholder="商品の説明を入力"
                            rows="5"
                        />
                        {errors.description && (
                            <p className="text-red-600 text-sm mt-1">{errors.description}</p>
                        )}
                    </div>

                    {/* 画像アップロード */}
                    <div>
                        <label className="block font-medium text-[var(--salon-brown)] mb-2">
                            商品画像
                        </label>
                        <input
                            type="file"
                            onChange={handleFileChange}
                            className="w-full border rounded-lg px-4 py-2"
                            accept="image/*"
                        />
                        {/* プレビュー表示 */}
                        <div className="mt-4 flex flex-col items-center gap-4">
                            {preview ? (
                                <img
                                    src={preview}
                                    alt="新しいプレビュー"
                                    className="max-h-[250px] rounded-lg shadow-md object-contain"
                                />
                            ) : (
                                <img
                                    src={`/storage/${product.image_path}`}
                                    alt="既存画像"
                                    className="max-h-[250px] rounded-lg shadow-md object-contain"
                                />
                            )}
                        </div>
                        {errors.image && (
                            <p className="text-red-600 text-sm mt-1">{errors.image}</p>
                        )}
                    </div>

                    {/* 在庫数 */}
                    <div>
                        <label htmlFor="stock" className="block text-salon-brown font-semibold mb-1">
                            在庫数
                        </label>
                        <input
                            type="number"
                            name="stock"
                            id="stock"
                            value={data.stock || 0}
                            onChange={(e) => setData("stock", e.target.value)}
                            className="w-full border rounded-md p-2 focus:ring-2 focus:ring-[var(--salon-gold)]"
                            min="0"
                        />
                        {errors.stock && <p className="text-red-500 text-sm mt-1">{errors.stock}</p>}
                    </div>

                    {/* ボタンエリア */}
                    <div className="flex justify-between items-center mt-8">
                        <button
                            type="button"
                            onClick={() => router.visit(route("admin.products.index"))}
                            className="px-6 py-2 rounded-lg bg-gray-200 text-[var(--salon-brown)] hover:bg-gray-300"
                        >
                            戻る
                        </button>

                        <button
                            type="submit"
                            disabled={processing}
                            className="px-6 py-2 rounded-lg bg-[var(--salon-gold)] text-white hover:opacity-90"
                        >
                            {processing ? "更新中..." : "更新する"}
                        </button>
                    </div>
                </form>
            </div>
        </motion.div>
    );
}
