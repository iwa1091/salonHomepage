// /resources/js/Pages/Admin/ServiceForm.jsx
import React, { useState, useEffect } from "react";
import { useForm, usePage, Link, router } from "@inertiajs/react";
import { route } from "ziggy-js";
import CategoryModal from "./CategoryModal";

// モジュール化した CSS をインポート
import "../../../css/pages/admin/service-form.css";

export default function ServiceForm({
    service = null,
    categories: initialCategories = [],
}) {
    const { errors } = usePage().props;

    // ✅ Inertia の useForm フックを使用
    const { data, setData, processing } = useForm({
        name: service?.name || "",
        description: service?.description || "",
        price: service?.price || "",
        duration_minutes: service?.duration_minutes || "",
        sort_order: service?.sort_order || 0,
        is_active: service?.is_active || false,
        is_popular: service?.is_popular || false,
        category_id: service?.category_id || "",
        features: Array.isArray(service?.features) ? service.features : [],
        image: null,
    });

    const [categories, setCategories] = useState(initialCategories);
    const [showModal, setShowModal] = useState(false);
    const [featureInput, setFeatureInput] = useState("");
    // ✅ セッション切れ（419）検知 → 自動リロード
    useEffect(() => {
        const removeListener = router.on("invalid", (event) => {
            const status = event?.detail?.response?.status;
            if (status === 419) {
                event.preventDefault();
                alert("セッションの有効期限が切れました。ページを再読み込みします。");
                window.location.reload();
            }
        });
        return () => removeListener();
    }, []);

    /** ✅ カテゴリ新規作成後に即反映 */
    const handleCategoryCreated = (newCategory) => {
        if (!newCategory) return;

        setCategories((prev) => [...prev, newCategory]);
        setData("category_id", newCategory.id);
        setShowModal(false);
    };

    /** ✅ 入力変更 */
    const handleChange = (e) => {
        const { name, type, checked, files, value } = e.target;

        if (type === "checkbox") {
            setData(name, checked);
        } else if (type === "file") {
            setData(name, files[0] ?? null);
        } else {
            setData(name, value);
        }
    };

    /** ✅ 特徴追加（Enterキー） */
    const handleFeatureKeyDown = (e) => {
        if (e.isComposing || e.keyCode === 229) return; // IME変換中はスキップ

        if (e.key === "Enter") {
            e.preventDefault();
            const trimmed = featureInput.trim();

            if (trimmed && !data.features.includes(trimmed)) {
                setData("features", [...data.features, trimmed]);
            }
            setFeatureInput("");
        }
    };

    /** ✅ 特徴削除 */
    const removeFeature = (feature) => {
        setData(
            "features",
            data.features.filter((f) => f !== feature)
        );
    };

    /** ✅ 保存処理 */
    const handleSubmit = (e) => {
        e.preventDefault();

        const formData = new FormData();

        Object.entries(data).forEach(([key, value]) => {
            if (key === "features" && Array.isArray(value)) {
                value.forEach((feature) => {
                    formData.append("features[]", feature);
                });
            } else if (value !== null && value !== undefined) {
                formData.append(key, value);
            }
        });

        if (service) {
            // 更新時は Laravel 側で PUT として扱わせる
            formData.append("_method", "PUT");

            router.post(route("admin.services.update", service.id), formData, {
                forceFormData: true,
                preserveScroll: true,
            });
        } else {
            router.post(route("admin.services.store"), formData, {
                forceFormData: true,
                preserveScroll: true,
            });
        }
    };

    return (
        <div className="admin-service-form-page">
            <div className="admin-service-form-container">
                {/* 🔙 サービス一覧（ServiceIndex）へ戻るボタン */}
                <div className="service-form-back-area">
                    <Link
                        href={route("admin.services.index")}
                        className="service-form-back-button"
                    >
                        前のページに戻る
                    </Link>
                </div>

                <h1 className="service-form-title">
                    {service ? "メニュー編集" : "新規メニュー作成"}
                </h1>

                <form
                    onSubmit={handleSubmit}
                    className="service-form"
                    encType="multipart/form-data"
                    noValidate
                >
                    {/* 名前 */}
                    <div className="service-form-field">
                        <label className="service-form-label">名前</label>
                        <input
                            type="text"
                            name="name"
                            value={data.name}
                            onChange={handleChange}
                            className="service-form-input"
                            required
                        />
                        {errors.name && (
                            <div className="service-form-error">
                                {errors.name}
                            </div>
                        )}
                    </div>

                    {/* カテゴリ */}
                    <div className="service-form-field">
                        <label className="service-form-label">カテゴリ</label>
                        <div className="service-form-category-row">
                            <select
                                name="category_id"
                                value={data.category_id}
                                onChange={handleChange}
                                className="service-form-select"
                                required
                            >
                                <option value="">選択してください</option>
                                {categories.map((cat) => (
                                    <option key={cat.id} value={cat.id}>
                                        {cat.name}
                                    </option>
                                ))}
                            </select>

                            {/* ✅ 新規カテゴリ追加ボタン */}
                            <button
                                type="button"
                                className="service-form-category-add"
                                onClick={() => setShowModal(true)}
                            >
                                ＋新規作成
                            </button>
                        </div>
                        {errors.category_id && (
                            <div className="service-form-error">
                                {errors.category_id}
                            </div>
                        )}
                    </div>

                    {/* 説明 */}
                    <div className="service-form-field">
                        <label className="service-form-label">説明</label>
                        <textarea
                            name="description"
                            value={data.description}
                            onChange={handleChange}
                            className="service-form-textarea"
                            rows="4"
                        />
                        {errors.description && (
                            <div className="service-form-error">
                                {errors.description}
                            </div>
                        )}
                    </div>

                    {/* 価格 */}
                    <div className="service-form-field">
                        <label className="service-form-label">
                            価格 (円)
                        </label>
                        <input
                            type="number"
                            name="price"
                            value={data.price}
                            onChange={handleChange}
                            className="service-form-input"
                            min="0"
                            required
                        />
                        {errors.price && (
                            <div className="service-form-error">
                                {errors.price}
                            </div>
                        )}
                    </div>

                    {/* 所要時間 */}
                    <div className="service-form-field">
                        <label className="service-form-label">
                            所要時間 (分)
                        </label>
                        <input
                            type="number"
                            name="duration_minutes"
                            value={data.duration_minutes}
                            onChange={handleChange}
                            className="service-form-input"
                            min="1"
                            max="480"
                            required
                        />
                        {errors.duration_minutes && (
                            <div className="service-form-error">
                                {errors.duration_minutes}
                            </div>
                        )}
                    </div>

                    {/* 表示順序 */}
                    <div className="service-form-field">
                        <label className="service-form-label">表示順序</label>
                        <input
                            type="number"
                            name="sort_order"
                            value={data.sort_order}
                            onChange={handleChange}
                            className="service-form-input"
                            min="0"
                        />
                        {errors.sort_order && (
                            <div className="service-form-error">
                                {errors.sort_order}
                            </div>
                        )}
                    </div>

                    {/* 公開 */}
                    <div className="service-form-field">
                        <label className="service-form-checkbox-row">
                            <input
                                type="checkbox"
                                name="is_active"
                                checked={data.is_active}
                                onChange={handleChange}
                                className="service-form-checkbox"
                            />
                            公開
                        </label>
                    </div>

                    {/* 人気サービス */}
                    <div className="service-form-field">
                        <label className="service-form-checkbox-row">
                            <input
                                type="checkbox"
                                name="is_popular"
                                checked={data.is_popular}
                                onChange={handleChange}
                                className="service-form-checkbox"
                            />
                            人気サービス
                        </label>
                    </div>

                    {/* 特徴 */}
                    <div className="service-form-field">
                        <label className="service-form-label">特徴</label>
                        <input
                            type="text"
                            value={featureInput}
                            onChange={(e) => setFeatureInput(e.target.value)}
                            onKeyDown={handleFeatureKeyDown}
                            placeholder="Enterで追加"
                            className="service-form-input service-form-feature-input"
                            autoComplete="off"
                        />
                        <div className="service-features-container">
                            {data.features.map((f, idx) => (
                                <span
                                    key={idx}
                                    className="service-feature-chip"
                                >
                                    {f}
                                    <button
                                        type="button"
                                        className="service-feature-chip-remove"
                                        onClick={() => removeFeature(f)}
                                    >
                                        ×
                                    </button>
                                </span>
                            ))}
                        </div>
                        {errors.features && (
                            <div className="service-form-error">
                                {errors.features}
                            </div>
                        )}
                    </div>

                    {/* 画像 */}
                    <div className="service-form-field">
                        <label className="service-form-label">
                            画像アップロード
                        </label>
                        <input
                            type="file"
                            name="image"
                            onChange={handleChange}
                            className="service-form-input"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                        />
                        <p className="service-form-hint">
                            jpeg・png・gif・webp形式、1MB以内のファイルを選択してください。
                        </p>
                        {service?.image_url && (
                            <img
                                src={service.image_url}
                                alt="Current"
                                className="service-form-image-preview"
                            />
                        )}
                        {errors.image && (
                            <div className="service-form-error">
                                {errors.image}
                            </div>
                        )}
                    </div>

                    {/* 公開ページとの関連を明示 */}
                    <p className="service-form-hint" style={{ textAlign: "center", marginBottom: "1rem" }}>
                        ※ ここで登録・編集した内容は公開ページ「メニュー・料金」に反映されます。
                    </p>

                    {/* 保存ボタン */}
                    <button
                        type="submit"
                        disabled={processing}
                        className="service-form-submit-button"
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
        </div>
    );
}
