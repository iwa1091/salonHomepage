// /resources/js/Pages/Admin/CategoryModal.jsx
import React, { useEffect, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";

// モジュール化した CSS をインポート
import "../../../css/pages/admin/category-modal.css";

export default function CategoryModal({ isOpen, onClose, onCreated }) {
    const [name, setName] = useState("");
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});

    // ✅ 開くたびに入力/エラーをリセット（2回目以降の不具合防止）
    useEffect(() => {
        if (!isOpen) return;
        setName("");
        setErrors({});
        setLoading(false);
    }, [isOpen]);

    const closeSafely = () => {
        if (loading) return; // 保存中に閉じない（事故防止）
        setErrors({});
        onClose?.();
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        const trimmed = name.trim();
        if (!trimmed) {
            setErrors({ name: "カテゴリー名を入力してください。" });
            return;
        }

        setLoading(true);
        setErrors({});

        try {
            // ✅ window.axios は bootstrap.js で初期化済み前提
            // server 側で expectsJson() に反応させるため、Accept: application/json を付ける
            const res = await window.axios.post(
                "/admin/categories",
                { name: trimmed },
                { headers: { Accept: "application/json" } }
            );

            const created = res?.data?.category || null;

            if (created && onCreated) {
                onCreated(created);
            }

            setName("");
            setErrors({});
            onClose?.(); // ✅ 成功したら閉じる
        } catch (err) {
            // Laravel validation (422)
            const status = err?.response?.status;
            const data = err?.response?.data;

            if (status === 422 && data?.errors) {
                // errors.name は配列のことが多い
                const nameErr = Array.isArray(data.errors.name)
                    ? data.errors.name[0]
                    : data.errors.name;

                setErrors({
                    ...data.errors,
                    ...(nameErr ? { name: nameErr } : {}),
                });
            } else {
                // その他（500など）
                setErrors({
                    name: data?.message || "保存に失敗しました。時間をおいて再度お試しください。",
                });
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <AnimatePresence>
            {isOpen && (
                <>
                    {/* 背景オーバーレイ */}
                    <motion.div
                        className="category-modal-overlay"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        onClick={closeSafely}
                    />

                    {/* モーダル本体 */}
                    <motion.div
                        className="category-modal-wrapper"
                        initial={{ opacity: 0, scale: 0.9, y: -30 }}
                        animate={{ opacity: 1, scale: 1, y: 0 }}
                        exit={{ opacity: 0, scale: 0.9, y: -30 }}
                        transition={{ duration: 0.25 }}
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="category-modal-content">
                            <h2 className="category-modal-title">新規カテゴリー作成</h2>

                            <form onSubmit={handleSubmit} className="category-modal-form">
                                <div className="category-modal-field">
                                    <label htmlFor="category-name" className="category-modal-label">
                                        カテゴリー名
                                    </label>
                                    <input
                                        id="category-name"
                                        type="text"
                                        value={name}
                                        onChange={(e) => setName(e.target.value)}
                                        className="category-modal-input"
                                        placeholder="例: まつ毛エクステ"
                                        disabled={loading}
                                        autoFocus
                                    />
                                    {errors?.name && (
                                        <p className="category-modal-error">
                                            {Array.isArray(errors.name) ? errors.name[0] : errors.name}
                                        </p>
                                    )}
                                </div>

                                <div className="category-modal-actions">
                                    <button
                                        type="button"
                                        className="category-modal-button category-modal-button--cancel"
                                        onClick={closeSafely}
                                        disabled={loading}
                                    >
                                        キャンセル
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={loading}
                                        className="category-modal-button category-modal-button--submit"
                                    >
                                        {loading ? "保存中..." : "保存"}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </motion.div>
                </>
            )}
        </AnimatePresence>
    );
}
