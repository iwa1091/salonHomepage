import React, { useState } from "react";
import { useForm, router } from "@inertiajs/react";
import { motion } from "framer-motion";

export default function Create() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        price: "",
        description: "",
        image: null,
    });

    const [preview, setPreview] = useState(null);

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        setData("image", file);
        setPreview(URL.createObjectURL(file));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("admin.products.store"), {
            onSuccess: () => {
                reset();
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
                    商品登録
                </h1>

                <form onSubmit={handleSubmit} className="space-y-6" encType="multipart/form-data">
                    <div>
                        <label className="block font-medium text-[var(--salon-brown)] mb-2">商品名</label>
                        <input
                            type="text"
                            name="name"
                            value={data.name}
                            onChange={(e) => setData("name", e.target.value)}
                            className="w-full border rounded-lg px-4 py-2"
                            placeholder="商品名を入力"
                        />
                        {errors.name && <p className="text-red-600 text-sm mt-1">{errors.name}</p>}
                    </div>

                    <div>
                        <label className="block font-medium text-[var(--salon-brown)] mb-2">価格</label>
                        <input
                            type="number"
                            name="price"
                            value={data.price}
                            onChange={(e) => setData("price", e.target.value)}
                            className="w-full border rounded-lg px-4 py-2"
                            placeholder="価格を入力"
                        />
                        {errors.price && <p className="text-red-600 text-sm mt-1">{errors.price}</p>}
                    </div>

                    <div>
                        <label className="block font-medium text-[var(--salon-brown)] mb-2">商品説明</label>
                        <textarea
                            name="description"
                            value={data.description}
                            onChange={(e) => setData("description", e.target.value)}
                            className="w-full border rounded-lg px-4 py-2"
                            placeholder="商品の説明を入力"
                        />
                        {errors.description && <p className="text-red-600 text-sm mt-1">{errors.description}</p>}
                    </div>

                    <div>
                        <label className="block font-medium text-[var(--salon-brown)] mb-2">商品画像</label>
                        <input
                            type="file"
                            onChange={handleFileChange}
                            className="w-full border rounded-lg px-4 py-2"
                            accept="image/*"
                        />
                        {preview && (
                            <div className="mt-3">
                                <img src={preview} alt="preview" className="rounded-lg shadow-md" />
                            </div>
                        )}
                        {errors.image && <p className="text-red-600 text-sm mt-1">{errors.image}</p>}
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
                            {processing ? "登録中..." : "登録"}
                        </button>
                    </div>
                </form>
            </div>
        </motion.div>
    );
}
