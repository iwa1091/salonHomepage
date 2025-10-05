import React, { useState } from "react";
import { useForm, usePage } from "@inertiajs/react";
import { route } from "ziggy-js";
import CategoryModal from "./CategoryModal";

export default function ServiceForm({ service = null, categories: initialCategories = [] }) {
    const { errors } = usePage().props;

    const { data, setData, post, put, processing } = useForm({
        name: service?.name || "",
        description: service?.description || "",
        price: service?.price || "",
        duration_minutes: service?.duration_minutes || "",
        sort_order: service?.sort_order || 0,
        is_active: service?.is_active || false,
        is_popular: service?.is_popular || false,
        category_id: service?.category_id || "",
        features: service?.features || [],
        image: null,
    });

    const [categories, setCategories] = useState(initialCategories);
    const [showModal, setShowModal] = useState(false);
    const [featureInput, setFeatureInput] = useState("");

    // モーダル新規作成後に即反映
    const handleCategoryCreated = (newCategory) => {
        setCategories((prev) => [...prev, newCategory]);
        setData("category_id", newCategory.id);
    };

    // 入力変化
    const handleChange = (e) => {
        const { name, type, checked, files, value } = e.target;
        setData(
            name,
            type === "checkbox"
                ? checked
                : type === "file"
                    ? files[0]
                    : value
        );
    };

    // 特徴追加
    const handleFeatureKeyDown = (e) => {
        if (e.key === "Enter" && featureInput.trim() !== "") {
            e.preventDefault();
            if (!data.features.includes(featureInput.trim())) {
                setData("features", [...data.features, featureInput.trim()]);
            }
            setFeatureInput("");
        }
    };

    const removeFeature = (feature) => {
        setData("features", data.features.filter((f) => f !== feature));
    };

    // 保存
    const handleSubmit = (e) => {
        e.preventDefault();
        const formData = new FormData();
        Object.keys(data).forEach((key) => {
            if (key === "features") {
                formData.append("features", JSON.stringify(data.features));
            } else if (data[key] !== null) {
                formData.append(key, data[key]);
            }
        });

        if (service) {
            put(route("admin.services.update", service.id), {
                data: formData,
                forceFormData: true,
                preserveScroll: true,
            });
        } else {
            post(route("admin.services.store"), {
                data: formData,
                forceFormData: true,
                preserveScroll: true,
            });
        }
    };

    return (
        <div className="container mx-auto p-6">
            <h1 className="text-2xl font-bold mb-6">
                {service ? "サービス編集" : "新規サービス作成"}
            </h1>

            <form
                onSubmit={handleSubmit}
                className="space-y-4 max-w-lg"
                encType="multipart/form-data"
            >
                {/* 名前 */}
                <div>
                    <label className="block mb-1 font-medium">名前</label>
                    <input
                        type="text"
                        name="name"
                        value={data.name}
                        onChange={handleChange}
                        className="w-full border px-3 py-2 rounded"
                        required
                    />
                    {errors.name && (
                        <div className="text-red-600">{errors.name}</div>
                    )}
                </div>

                {/* カテゴリ */}
                <div>
                    <label className="block mb-1 font-medium">カテゴリ</label>
                    <div className="flex items-center gap-2">
                        <select
                            name="category_id"
                            value={data.category_id}
                            onChange={handleChange}
                            className="w-full border px-3 py-2 rounded"
                            required
                        >
                            <option value="">選択してください</option>
                            {categories.map((cat) => (
                                <option key={cat.id} value={cat.id}>
                                    {cat.name}
                                </option>
                            ))}
                        </select>
                        <button
                            type="button"
                            className="px-3 py-2 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200"
                            onClick={() => setShowModal(true)}
                        >
                            ＋新規作成
                        </button>
                    </div>
                    {errors.category_id && (
                        <div className="text-red-600">{errors.category_id}</div>
                    )}
                </div>

                {/* 説明 */}
                <div>
                    <label className="block mb-1 font-medium">説明</label>
                    <textarea
                        name="description"
                        value={data.description}
                        onChange={handleChange}
                        className="w-full border px-3 py-2 rounded"
                    />
                    {errors.description && (
                        <div className="text-red-600">
                            {errors.description}
                        </div>
                    )}
                </div>

                {/* 価格 */}
                <div>
                    <label className="block mb-1 font-medium">価格 (円)</label>
                    <input
                        type="number"
                        name="price"
                        value={data.price}
                        onChange={handleChange}
                        className="w-full border px-3 py-2 rounded"
                        min="0"
                        required
                    />
                    {errors.price && (
                        <div className="text-red-600">{errors.price}</div>
                    )}
                </div>

                {/* 所要時間 */}
                <div>
                    <label className="block mb-1 font-medium">
                        所要時間 (分)
                    </label>
                    <input
                        type="number"
                        name="duration_minutes"
                        value={data.duration_minutes}
                        onChange={handleChange}
                        className="w-full border px-3 py-2 rounded"
                        min="1"
                        max="480"
                        required
                    />
                    {errors.duration_minutes && (
                        <div className="text-red-600">
                            {errors.duration_minutes}
                        </div>
                    )}
                </div>

                {/* 表示順序 */}
                <div>
                    <label className="block mb-1 font-medium">表示順序</label>
                    <input
                        type="number"
                        name="sort_order"
                        value={data.sort_order}
                        onChange={handleChange}
                        className="w-full border px-3 py-2 rounded"
                        min="0"
                    />
                    {errors.sort_order && (
                        <div className="text-red-600">{errors.sort_order}</div>
                    )}
                </div>

                {/* 特徴 */}
                <div>
                    <label className="block mb-1 font-medium">特徴</label>
                    <input
                        type="text"
                        value={featureInput}
                        onChange={(e) => setFeatureInput(e.target.value)}
                        onKeyDown={handleFeatureKeyDown}
                        placeholder="Enterで追加"
                        className="w-full border px-3 py-2 rounded"
                    />
                    <div className="flex flex-wrap mt-2 gap-2">
                        {data.features.map((f, idx) => (
                            <span
                                key={idx}
                                className="bg-gray-200 px-2 py-1 rounded-full text-sm flex items-center"
                            >
                                {f}
                                <button
                                    type="button"
                                    className="ml-2 text-red-500"
                                    onClick={() => removeFeature(f)}
                                >
                                    ×
                                </button>
                            </span>
                        ))}
                    </div>
                    {errors.features && (
                        <div className="text-red-600">{errors.features}</div>
                    )}
                </div>

                {/* 公開 */}
                <div>
                    <label className="inline-flex items-center">
                        <input
                            type="checkbox"
                            name="is_active"
                            checked={data.is_active}
                            onChange={handleChange}
                            className="mr-2"
                        />
                        公開
                    </label>
                </div>

                {/* 人気 */}
                <div>
                    <label className="inline-flex items-center">
                        <input
                            type="checkbox"
                            name="is_popular"
                            checked={data.is_popular}
                            onChange={handleChange}
                            className="mr-2"
                        />
                        人気サービス
                    </label>
                </div>

                {/* 画像 */}
                <div>
                    <label className="block mb-1 font-medium">
                        画像アップロード
                    </label>
                    <input
                        type="file"
                        name="image"
                        onChange={handleChange}
                        className="w-full"
                        accept="image/*"
                    />
                    {service?.image_url && (
                        <img
                            src={service.image_url}
                            alt="Current"
                            className="mt-2 w-32 h-32 object-cover rounded"
                        />
                    )}
                    {errors.image && (
                        <div className="text-red-600">{errors.image}</div>
                    )}
                </div>

                {/* 保存 */}
                <button
                    type="submit"
                    disabled={processing}
                    className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                >
                    保存
                </button>
            </form>

            {/* モーダル */}
            <CategoryModal
                isOpen={showModal}
                onClose={() => setShowModal(false)}
                onCreated={handleCategoryCreated}
            />
        </div>
    );
}
