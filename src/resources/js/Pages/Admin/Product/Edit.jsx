// /resources/js/Pages/Admin/Product/Edit.jsx
import React, { useState } from "react";
import { useForm, router, usePage } from "@inertiajs/react";
import { motion } from "framer-motion";
import { route } from "ziggy-js";

// CSS モジュール
import "../../../../css/pages/admin/product/edit.css";

/**
 * 商品編集ページ (Admin/Product/Edit.jsx)
 * - 商品名 / 価格 / 説明 / 画像 / 在庫数 を編集
 * - 既存画像プレビュー + 新しい画像プレビュー対応
 */
export default function Edit() {
    const { product, flash } = usePage().props;

    // useForm で初期値を設定（✅ ファイル送信を確実にするため post + _method を使用）
    const { data, setData, post, processing, errors, reset } = useForm({
        _method: "patch",
        name: product.name || "",
        price: product.price || "",
        description: product.description || "",
        image: null,
        stock: product.stock ?? 0,
    });

    const [preview, setPreview] = useState(null);

    // 既存画像（/storage/0 などを踏まないようにフォールバック）
    const existingImageSrc =
        product?.image_url ||
        (product?.image_path && product.image_path !== "0"
            ? `/storage/${product.image_path}`
            : "/img/logo.png");

    // ファイル選択時のプレビュー処理
    const handleFileChange = (e) => {
        const file = e.target.files?.[0] ?? null;
        setData("image", file);

        if (file) {
            setPreview(URL.createObjectURL(file));
        } else {
            setPreview(null);
        }
    };

    // 送信処理（✅ post + _method=patch で送信）
    const handleSubmit = (e) => {
        e.preventDefault();

        post(route("admin.products.update", product.id), {
            forceFormData: true, // ✅ ファイル送信を確実にする
            onSuccess: () => {
                alert("商品情報を更新しました。");
                reset("image");
                setPreview(null);
            },
        });
    };

    return (
        <motion.div
            className="admin-product-edit-page"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
        >
            <div className="admin-product-edit-container">
                <h1 className="admin-product-edit-title">商品編集</h1>

                {/* フラッシュメッセージ */}
                {flash?.success && (
                    <p className="admin-product-edit-flash">
                        {flash.success}
                    </p>
                )}

                <form
                    onSubmit={handleSubmit}
                    className="admin-product-edit-form"
                    encType="multipart/form-data"
                    noValidate
                >
                    {/* 商品名 */}
                    <div className="admin-product-edit-field">
                        <label className="admin-product-edit-label">
                            商品名
                        </label>
                        <input
                            type="text"
                            name="name"
                            value={data.name}
                            onChange={(e) => setData("name", e.target.value)}
                            className="admin-product-edit-input"
                            placeholder="商品名を入力"
                        />
                        {errors.name && (
                            <p className="admin-product-edit-error">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    {/* 価格 */}
                    <div className="admin-product-edit-field">
                        <label className="admin-product-edit-label">
                            価格
                        </label>
                        <input
                            type="number"
                            name="price"
                            value={data.price}
                            onChange={(e) => setData("price", e.target.value)}
                            className="admin-product-edit-input"
                            placeholder="価格を入力"
                        />
                        {errors.price && (
                            <p className="admin-product-edit-error">
                                {errors.price}
                            </p>
                        )}
                    </div>

                    {/* 商品説明 */}
                    <div className="admin-product-edit-field">
                        <label className="admin-product-edit-label">
                            商品説明
                        </label>
                        <textarea
                            name="description"
                            value={data.description}
                            onChange={(e) =>
                                setData("description", e.target.value)
                            }
                            className="admin-product-edit-textarea"
                            placeholder="商品の説明を入力"
                            rows={5}
                        />
                        {errors.description && (
                            <p className="admin-product-edit-error">
                                {errors.description}
                            </p>
                        )}
                    </div>

                    {/* 画像アップロード */}
                    <div className="admin-product-edit-field">
                        <label className="admin-product-edit-label">
                            商品画像
                        </label>
                        <input
                            type="file"
                            onChange={handleFileChange}
                            className="admin-product-edit-file"
                            accept="image/*"
                        />

                        <div className="admin-product-edit-preview-wrapper">
                            <div className="admin-product-edit-preview-inner">
                                <img
                                    src={preview || existingImageSrc}
                                    alt={preview ? "新しいプレビュー" : "既存画像"}
                                    className="admin-product-edit-preview-image"
                                />
                            </div>
                        </div>

                        {errors.image && (
                            <p className="admin-product-edit-error">
                                {errors.image}
                            </p>
                        )}
                    </div>

                    {/* 在庫数 */}
                    <div className="admin-product-edit-field">
                        <label
                            htmlFor="stock"
                            className="admin-product-edit-label"
                        >
                            在庫数
                        </label>
                        <input
                            type="number"
                            name="stock"
                            id="stock"
                            value={data.stock}
                            onChange={(e) => setData("stock", e.target.value)}
                            className="admin-product-edit-input"
                            min="0"
                        />
                        {errors.stock && (
                            <p className="admin-product-edit-error">
                                {errors.stock}
                            </p>
                        )}
                    </div>

                    {/* ボタンエリア */}
                    <div className="admin-product-edit-actions">
                        <button
                            type="button"
                            onClick={() =>
                                router.visit(route("admin.products.index"))
                            }
                            className="admin-product-edit-button admin-product-edit-button--back"
                        >
                            戻る
                        </button>

                        <button
                            type="submit"
                            disabled={processing}
                            className="admin-product-edit-button admin-product-edit-button--submit"
                        >
                            {processing ? "更新中..." : "更新する"}
                        </button>
                    </div>
                </form>
            </div>
        </motion.div>
    );
}
