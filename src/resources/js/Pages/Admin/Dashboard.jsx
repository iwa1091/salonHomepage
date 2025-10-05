import React from 'react';
import { Link } from "@inertiajs/react";

import { useAdmin } from '@/hooks/useAdmin';
import './dashboard.css';

const AuthenticatedLayout = ({ user, header, children }) => (
    <div className="admin-layout">
        <header className="admin-header-bar">
            <div className="container header-container">
                {header}
            </div>
        </header>
        <main>{children}</main>
    </div>
);

const Head = ({ title }) => <title>{title}</title>;

export default function AdminDashboard({ auth }) {
    const { adminFeatures } = useAdmin();
    const userName = auth.user?.name || "ゲスト管理者";

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="dashboard-title">管理者ダッシュボード</h2>}
        >
            <Head title="管理者ダッシュボード" />

            <div className="dashboard-wrapper">
                <div className="container">
                    <div className="dashboard-card-area">
                        <p className="welcome-message">
                            ようこそ、<span className="user-name">{userName}</span>様。
                            <span className="message-subtext">
                                実行したい管理タスクを選択してください。
                            </span>
                        </p>

                        <div className="feature-grid">
                            {adminFeatures.map((feature) => (
                                // ★ここを <Link> に変更
                                <Link
                                    key={feature.title}
                                    href={route(feature.route)}
                                    className="feature-card"
                                >
                                    <div className={`feature-icon-wrapper ${feature.color}`}>
                                        <feature.icon size={28} strokeWidth={2.5} />
                                    </div>
                                    <h3 className="card-title">{feature.title}</h3>
                                    <p className="card-description">{feature.description}</p>
                                    <span className="card-link">詳細管理へ &rarr;</span>
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
