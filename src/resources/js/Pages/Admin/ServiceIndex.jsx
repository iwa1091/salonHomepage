import React from 'react';
import { Inertia } from '@inertiajs/inertia';
import { Link, usePage } from '@inertiajs/inertia-react';

export default function ServiceIndex() {
    const { services } = usePage().props;

    const handleDelete = (id) => {
        if (confirm('削除してもよろしいですか？')) {
            Inertia.delete(route('admin.services.destroy', id));
        }
    };

    const handleToggle = (id) => {
        Inertia.post(route('admin.services.toggle', id));
    };

    return (
        <div className="container mx-auto p-6">
            <h1 className="text-2xl font-bold mb-4">サービス管理</h1>
            <Link
                href={route('admin.services.create')}
                className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mb-4 inline-block"
            >
                新規作成
            </Link>

            <table className="min-w-full border">
                <thead>
                    <tr className="bg-gray-100">
                        <th className="px-4 py-2 border">ID</th>
                        <th className="px-4 py-2 border">名前</th>
                        <th className="px-4 py-2 border">価格</th>
                        <th className="px-4 py-2 border">時間</th>
                        <th className="px-4 py-2 border">公開</th>
                        <th className="px-4 py-2 border">操作</th>
                    </tr>
                </thead>
                <tbody>
                    {services.map(service => (
                        <tr key={service.id} className="text-center">
                            <td className="border px-4 py-2">{service.id}</td>
                            <td className="border px-4 py-2">{service.name}</td>
                            <td className="border px-4 py-2">¥{service.price}</td>
                            <td className="border px-4 py-2">{service.duration_minutes}分</td>
                            <td className="border px-4 py-2">
                                <button
                                    className={`px-3 py-1 rounded ${service.is_active ? 'bg-green-500 text-white' : 'bg-gray-300 text-black'}`}
                                    onClick={() => handleToggle(service.id)}
                                >
                                    {service.is_active ? '公開' : '非公開'}
                                </button>
                            </td>
                            <td className="border px-4 py-2 space-x-2">
                                <Link
                                    href={route('admin.services.edit', service.id)}
                                    className="bg-yellow-400 px-3 py-1 rounded hover:bg-yellow-500"
                                >
                                    編集
                                </Link>
                                <button
                                    className="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600"
                                    onClick={() => handleDelete(service.id)}
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
