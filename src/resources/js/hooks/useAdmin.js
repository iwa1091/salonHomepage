import { ShoppingCart, CalendarDays, Users, LayoutDashboard, Settings } from 'lucide-react';

/**
 * 管理者エリア全体で使用される共通のロジック、設定、ユーティリティを提供するカスタムフックです。
 * ダッシュボードのナビゲーション定義や、将来的な管理者機能の共通処理を格納します。
 *
 * @returns {{ adminFeatures: Array<Object>, checkPermission: (permission: string) => boolean }}
 */
export function useAdmin() {
    // 管理機能へのリンク定義。routeプロパティはLaravelのroute()ヘルパーで使用されるルート名です。
    const adminFeatures = [
        {
            title: "予約管理",
            description: "全てのお客様の予約一覧（ReservationList.jsx）を確認、管理します。",
            route: 'admin.reservations.index',
            icon: CalendarDays,
            color: 'bg-indigo-600',
        },
        {
            title: "商品・メニュー管理",
            description: "提供する商品やメニューの情報を管理します。",
            route: 'admin.products.index',
            icon: ShoppingCart,
            color: 'bg-pink-600',
        },
        {
            title: "顧客管理",
            description: "登録されている顧客情報を管理します。",
            route: 'admin.users.index', // 仮のルート名
            icon: Users,
            color: 'bg-orange-600', // 新しいカラーを追加
        },
        {
            title: "全体概要",
            description: "売上や予約数の統計情報を確認します。",
            route: 'admin.analytics', // ルート名を具体的に変更
            icon: LayoutDashboard,
            color: 'bg-teal-600',
        },
        {
            title: "設定",
            description: "店舗情報やシステムの基本設定を変更します。",
            route: 'admin.settings', 
            icon: Settings,
            color: 'bg-gray-600', // 新しい設定用のカラーを追加
        },
    ];

    /**
     * 指定された権限キーを持つかチェックする関数（将来的な拡張用）
     * 実際にはユーザーのロールや権限を基に実装します。
     * @param {string} permission - チェックする権限キー
     * @returns {boolean}
     */
    const checkPermission = (permission) => {
        // 開発フェーズでは常にtrueを返します
        console.log(`Permission check requested for: ${permission}`);
        return true;
    };

    return {
        adminFeatures,
        checkPermission,
    };
}
