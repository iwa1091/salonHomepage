import React from 'react';

// 新しく分離したカスタムフックをインポート
import { useAdmin } from '@/hooks/useAdmin';

// ★追加: 作成したCSSファイルをインポート
import './dashboard.css';

// エイリアス解決の問題を回避するため、Layoutsは仮のコンポーネントに置き換えます。
// TailwindクラスをCSSクラスに置き換えます
const AuthenticatedLayout = ({ user, header, children }) => (
    <div className="admin-layout"> {/* min-h-screen bg-gray-100 を置き換え */}
        <header className="admin-header-bar"> {/* bg-white shadow を置き換え */}
            <div className="container header-container"> {/* max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 を置き換え */}
                {header}
            </div>
        </header>
        <main>{children}</main>
    </div>
);

// InertiaのHeadコンポーネントの代わりとなるダミーコンポーネント (変更なし)
const Head = ({ title }) => <title>{title}</title>;

// 管理者ダッシュボードコンポーネント
export default function AdminDashboard({ auth }) {
    const { adminFeatures } = useAdmin();
    const userName = auth.user?.name || "ゲスト管理者";

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                // TailwindクラスをCSSクラスに置き換え
                <h2 className="dashboard-title">
                    管理者ダッシュボード
                </h2>
            }
        >
            <Head title="管理者ダッシュボード" />

            <div className="dashboard-wrapper"> {/* py-12 bg-gray-50 min-h-screen を置き換え */}
                <div className="container"> {/* mx-auto max-w-7xl sm:px-6 lg:px-8 を置き換え */}
                    <div className="dashboard-card-area"> {/* bg-white p-8 sm:p-10 rounded-2xl shadow-2xl を置き換え */}
                        <p className="welcome-message"> {/* text-2xl font-semibold text-gray-700 mb-8 を置き換え */}
                            ようこそ、<span className="user-name">{userName}</span>様。 {/* text-indigo-600 font-bold を置き換え */}
                            <span className="message-subtext"> {/* block mt-1 text-base font-normal text-gray-500 を置き換え */}
                                実行したい管理タスクを選択してください。
                            </span>
                        </p>

                        {/* 機能カードのグリッドレイアウト */}
                        <div className="feature-grid"> {/* grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3 を置き換え */}
                            {adminFeatures.map((feature) => (
                                <a
                                    key={feature.title}
                                    // Inertiaのroute()ヘルパーがないため、暫定的なパスを使用
                                    href={route(feature.route)}
                                    className="feature-card"
                                >
                                    {/* feature.color, text-white, shadow-md をクラスに含める */}
                                    <div className={`feature-icon-wrapper ${feature.color}`}>
                                        {/* feature.icon はuseAdmin.jsでインポートされたLucideアイコンコンポーネントです */}
                                        <feature.icon size={28} strokeWidth={2.5} />
                                    </div>
                                    <h3 className="card-title"> {/* text-xl font-bold text-gray-900 mb-2 group-hover:text-indigo-700... を置き換え */}
                                        {feature.title}
                                    </h3>
                                    <p className="card-description"> {/* text-sm text-gray-500 flex-grow を置き換え */}
                                        {feature.description}
                                    </p>
                                    <span className="card-link"> {/* mt-4 inline-block text-sm font-semibold text-indigo-600 group-hover:text-indigo-800... を置き換え */}
                                        詳細管理へ &rarr;
                                    </span>
                                </a>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}