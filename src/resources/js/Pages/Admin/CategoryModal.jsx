import React, { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Inertia } from "@inertiajs/inertia";

export default function CategoryModal({ isOpen, onClose, onCreated }) {
    const [name, setName] = useState("");
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});

        Inertia.post(
            "/admin/categories",
            { name },
            {
                onSuccess: (page) => {
                    const createdCategory =
                        page.props.flash?.category || page.props.category;
                    if (createdCategory && onCreated) {
                        onCreated(createdCategory ?? null);
                    }
                    setName("");
                    onClose();
                },
                onError: (err) => {
                    // Laravel バリデーションエラーを errors にセット
                    setErrors(err);
                },
                onFinish: () => setLoading(false),
            }
        );
    };

    return (
        <AnimatePresence>
            {isOpen && (
                <>
                    {/* 背景オーバーレイ */}
                    <motion.div
                        className="fixed inset-0 bg-black bg-opacity-40 z-40"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        onClick={onClose}
                    />

                    {/* モーダル本体 */}
                    <motion.div
                        className="fixed inset-0 z-50 flex items-center justify-center p-4"
                        initial={{ opacity: 0, scale: 0.9, y: -30 }}
                        animate={{ opacity: 1, scale: 1, y: 0 }}
                        exit={{ opacity: 0, scale: 0.9, y: -30 }}
                        transition={{ duration: 0.25 }}
                    >
                        <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 relative">
                            <h2 className="text-lg font-semibold text-gray-800 mb-4">
                                新規カテゴリー作成
                            </h2>

                            <form onSubmit={handleSubmit}>
                                <div className="mb-4">
                                    <label
                                        htmlFor="category-name"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        カテゴリー名
                                    </label>
                                    <input
                                        id="category-name"
                                        type="text"
                                        value={name}
                                        onChange={(e) => setName(e.target.value)}
                                        className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="例: まつ毛エクステ"
                                    />
                                    {errors.name && (
                                        <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                    )}
                                </div>

                                <div className="flex justify-end space-x-2 mt-6">
                                    <button
                                        type="button"
                                        className="px-4 py-2 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200"
                                        onClick={onClose}
                                    >
                                        キャンセル
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={loading}
                                        className="px-4 py-2 rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
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
