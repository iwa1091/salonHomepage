// Inertiaのインポートを模倣するヘルパー関数群

/**
 * InertiaのLinkの代わりに標準のaタグを使用するヘルパー関数。
 * @param {object} props - href, children, active, className などの標準的なプロパティ
 */
export const Link = (props) => {
    // href, children, active, className などの標準的なプロパティを受け取ります
    return <a {...props}>{props.children}</a>;
};

/**
 * InertiaのusePageの代わりに、ダミーのprops取得関数を定義します。
 * 実際にはInertiaのフレームワークによりpropsが渡されますが、ビルドエラー回避のため、
 * 必須のauthとreservationsの構造を仮定します。
 * @returns {object} ページコンポーネントに渡されるpropsを模倣したオブジェクト
 */
export const usePage = () => ({
    props: {
        auth: {
            user: {
                name: "管理者ユーザー",
                email: "admin@example.com",
            }
        },
        // ダミーデータまたは環境から注入された実際の予約データ
        reservations: window.initialReservations || [
            { id: 1, name: '山田 太郎', email: 'yamada@example.com', date: '2025-10-15T10:00:00' },
            { id: 2, name: '佐藤 花子', email: 'sato@example.com', date: '2025-10-15T14:30:00' },
            { id: 3, name: '田中 一郎', email: 'tanaka@example.com', date: '2025-10-16T11:00:00' },
        ],
    }
});

/**
 * Inertiaのroute関数の代わりに、開発環境のルートパスを模倣した関数を定義します。
 * @param {string} name - ルート名
 * @param {object} params - ルートパラメーター
 * @returns {string} 生成されたURL
 */
export const route = (name, params = {}) => {
    switch (name) {
        case 'dashboard':
            return '/dashboard';
        case 'admin.dashboard':
            return '/admin/dashboard';
        case 'profile.edit':
            return '/profile';
        case 'logout':
            return '/logout';
        case 'admin.reservations.destroy':
            // paramsがオブジェクトの場合はIDを取得、IDが直接渡された場合はそのまま使用
            const id = typeof params === 'object' && params !== null ? params.id : params;
            return `/admin/reservations/${id}`; // 削除ルート
        default:
            return '#';
    }
};

/**
 * CSRFトークンの取得 (Laravel環境を想定)
 * @returns {string} CSRFトークン
 */
export const getCsrfToken = () => {
    // 環境変数またはメタタグからCSRFトークンを取得する処理を模倣
    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    // NOTE: 実際の本番環境ではこのmock_csrf_tokenは動作しません
    return tokenElement ? tokenElement.content : 'mock_csrf_token';
};
