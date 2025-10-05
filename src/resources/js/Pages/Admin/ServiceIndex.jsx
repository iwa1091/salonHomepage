import React, { useState } from 'react';
import { usePage, Link } from '@inertiajs/react';
import { Inertia } from '@inertiajs/inertia';
import { route } from 'ziggy-js';

export default function ServiceIndex() {
    const { services: initialServices, categories } = usePage().props;
    const [filterCategory, setFilterCategory] = useState('');
    const [services, setServices] = useState(initialServices);

    const handleDelete = (id) => {
        if (confirm('本当に削除しますか？')) {
            Inertia.delete(route('admin.services.destroy', id), {
                preserveScroll: true,
                onSuccess: () => {
                    setServices(services.filter((s) => s.id !== id));
                },
            });
        }
    };

    const toggleActive = (serviceId) => {
        Inertia.post(
            route('admin.services.toggleActive', serviceId),
            {},
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    // サーバーから返ってきた最新状態に反映
                    const updatedService = page.props.services.find((s) => s.id === serviceId);
                    setServices(
                        services.map((s) => (s.id === serviceId ? updatedService : s))
                    );
                },
            }
        );
    };

    const filteredServices = filterCategory
        ? services.filter((s) => s.category_id === parseInt(filterCategory))
        : services;

    return (
        <div className="container mx-auto p-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">サービス一覧</h1>
                <Link
                    href={route('admin.services.create')}
                    className="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
                >
                    新規作成
                </Link>
            </div>

            {/* カテゴリフィルタ */}
            <div className="mb-4">
                <label className="mr-2 font-medium">カテゴリで絞り込み:</label>
                <select
                    value={filterCategory}
                    onChange={(e) => setFilterCategory(e.target.value)}
                    className="border px-3 py-2 rounded"
                >
                    <option value="">すべて</option>
                    {categories.map((cat) => (
                        <option key={cat.id} value={cat.id}>
                            {cat.name}
                        </option>
                    ))}
                </select>
            </div>

            <table className="w-full border text-sm">
                <thead className="bg-gray-100">
                    <tr>
                        <th className="border px-4 py-2">ID</th>
                        <th className="border px-4 py-2">名前</th>
                        <th className="border px-4 py-2">カテゴリ</th>
                        <th className="border px-4 py-2">価格</th>
                        <th className="border px-4 py-2">所要時間</th>
                        <th className="border px-4 py-2">特徴</th>
                        <th className="border px-4 py-2">人気</th>
                        <th className="border px-4 py-2">公開</th>
                        <th className="border px-4 py-2">操作</th>
                    </tr>
                </thead>
                <tbody>
                    {filteredServices.map((service) => (
                        <tr key={service.id}>
                            <td className="border px-4 py-2">{service.id}</td>
                            <td className="border px-4 py-2">{service.name}</td>
                            <td className="border px-4 py-2">{service.category || '-'}</td>
                            <td className="border px-4 py-2">¥{service.price}</td>
                            <td className="border px-4 py-2">{service.duration_minutes}分</td>
                            <td className="border px-4 py-2">
                                {service.features && service.features.length > 0 ? (
                                    <ul className="list-disc pl-4">
                                        {service.features.map((f, idx) => (
                                            <li key={idx}>{f}</li>
                                        ))}
                                    </ul>
                                ) : (
                                    <span className="text-gray-400">なし</span>
                                )}
                            </td>
                            <td className="border px-4 py-2">
                                {service.is_popular ? (
                                    <span className="text-red-600 font-bold">人気</span>
                                ) : (
                                    <span className="text-gray-400">-</span>
                                )}
                            </td>
                            {/* 公開/非公開切替ボタン */}
                            <td className="border px-4 py-2">
                                <button
                                    onClick={() => toggleActive(service.id)}
                                    className={`px-3 py-1 rounded ${service.is_active
                                            ? 'bg-green-500 text-white'
                                            : 'bg-gray-300 text-gray-700'
                                        }`}
                                >
                                    {service.is_active ? '公開' : '非公開'}
                                </button>
                            </td>
                            <td className="border px-4 py-2 space-x-2">
                                <Link
                                    href={route('admin.services.edit', service.id)}
                                    className="text-blue-600 hover:underline"
                                >
                                    編集
                                </Link>
                                <button
                                    onClick={() => handleDelete(service.id)}
                                    className="text-red-600 hover:underline"
                                >
                                    削除
                                </button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
