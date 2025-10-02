import React, { useState, useEffect, useCallback } from 'react';
// Lucide Reactからアイコンをインポート
import { Users, Search, List, Mail, Phone, Clock, XCircle, UserPlus } from 'lucide-react';

// ダッシュボードと同じレイアウト構造を再利用
// AuthenticatedLayout と Head は Dashboard.jsx と ReservationList.jsx の定義に依存します
const AuthenticatedLayout = ({ user, header, children }) => (
    <div className="min-h-screen bg-gray-100">
        <header className="bg-white shadow">
            <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {header}
            </div>
        </header>
        <main className="py-12">{children}</main>
    </div>
);
const Head = ({ title }) => <title>{title}</title>;

// モックデータ: APIからの取得を想定 (顧客情報)
const mockUsers = [
    { id: 101, name: '佐藤 綾香', email: 'sato.ayaka@example.com', phone: '090-xxxx-0001', lastVisit: '2025/09/25', totalReservations: 5, membership: 'Regular' },
    { id: 102, name: '田中 美咲', email: 'tanaka.m@example.com', phone: '090-xxxx-0002', lastVisit: '2025/10/01', totalReservations: 12, membership: 'VIP' },
    { id: 103, name: '小林 由衣', email: 'kobayashi.yui@example.com', phone: '090-xxxx-0003', lastVisit: '2025/08/10', totalReservations: 2, membership: 'Regular' },
    { id: 104, name: '山本 太郎', email: 'yamamoto.t@example.com', phone: '090-xxxx-0004', lastVisit: '2025/07/01', totalReservations: 1, membership: 'Regular' },
    { id: 105, name: '中村 恵美', email: 'nakamura.e@example.com', phone: '090-xxxx-0005', lastVisit: '2025/10/02', totalReservations: 8, membership: 'Gold' },
    { id: 106, name: '渡辺 健太', email: 'watanabe.k@example.com', phone: '090-xxxx-0006', lastVisit: '2025/06/15', totalReservations: 3, membership: 'Regular' },
    { id: 107, name: '井上 さくら', email: 'inoue.s@example.com', phone: '090-xxxx-0007', lastVisit: '2025/10/01', totalReservations: 20, membership: 'VIP' },
];

/**
 * メンバーシップバッジコンポーネント
 */
const MembershipBadge = ({ membership }) => {
    let colorClass = '';
    switch (membership) {
        case 'VIP':
            colorClass = 'text-purple-600 bg-purple-100';
            break;
        case 'Gold':
            colorClass = 'text-yellow-600 bg-yellow-100';
            break;
        case 'Regular':
        default:
            colorClass = 'text-gray-600 bg-gray-100';
            break;
    }
    return (
        <span className={`inline-flex items-center px-3 py-1 text-xs font-medium rounded-full ${colorClass}`}>
            {membership}
        </span>
    );
};

/**
 * 顧客管理一覧コンポーネント
 */
export default function UserList({ auth }) {
    // 状態管理
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState(''); // 顧客名、メール、電話番号検索用
    const [filterMembership, setFilterMembership] = useState('ALL'); // メンバーシップフィルタ

    // 顧客データを取得する関数 (APIコールをシミュレート)
    const fetchUsers = useCallback(async (term, membershipFilter) => {
        setLoading(true);

        // 開発環境のため、遅延をシミュレート
        await new Promise(resolve => setTimeout(resolve, 500));

        let filteredData = mockUsers;

        // メンバーシップフィルタ
        if (membershipFilter !== 'ALL') {
            filteredData = filteredData.filter(user => user.membership === membershipFilter);
        }

        // 検索フィルタ
        if (term) {
            const lowerCaseTerm = term.toLowerCase();
            filteredData = filteredData.filter(user =>
                user.name.toLowerCase().includes(lowerCaseTerm) ||
                user.email.toLowerCase().includes(lowerCaseTerm) ||
                user.phone.includes(lowerCaseTerm.replace(/-/g, '')) // ハイフン無しでも検索できるように
            );
        }

        // ソート（最終来店日の新しい順にソート）
        filteredData.sort((a, b) => {
            const dateA = new Date(a.lastVisit.replace(/\//g, '-'));
            const dateB = new Date(b.lastVisit.replace(/\//g, '-'));
            // 降順（新しい日付が先）
            return dateB - dateA;
        });

        setUsers(filteredData);
        setLoading(false);
    }, []);

    // フィルタリングパラメータが変更されたらデータを再取得
    useEffect(() => {
        fetchUsers(searchTerm, filterMembership);
    }, [fetchUsers, searchTerm, filterMembership]);

    const handleEditUser = (id) => {
        alert(`顧客ID: ${id} の詳細情報を編集します。（実装予定）`);
    };

    const handleAddUser = () => {
        alert('新規顧客登録モーダルを開きます。（実装予定）');
    };

    // ユニークなメンバーシップレベルを取得
    const membershipLevels = ['ALL', ...new Set(mockUsers.map(u => u.membership))];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="text-3xl font-extrabold text-gray-900 flex items-center">
                    <Users className="w-8 h-8 mr-3 text-orange-600" />
                    顧客管理一覧
                </h2>
            }
        >
            <Head title="顧客管理" />

            <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="bg-white p-6 sm:p-8 rounded-2xl shadow-xl border border-gray-200">

                    {/* フィルタリングとアクションボタン */}
                    <div className="mb-6 border-b pb-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0 md:space-x-4">

                            {/* 1. 検索フィールド */}
                            <div className="relative flex-grow w-full md:w-1/3">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                                <input
                                    type="text"
                                    placeholder="顧客名、メール、電話番号で検索..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500 transition duration-150"
                                />
                            </div>

                            {/* 2. 新規顧客追加ボタン */}
                            <button
                                className="flex items-center px-4 py-2 bg-orange-500 text-white font-semibold rounded-lg shadow-md hover:bg-orange-600 transition duration-150 w-full md:w-auto justify-center"
                                onClick={handleAddUser}
                            >
                                <UserPlus className="w-4 h-4 mr-2" />
                                新規顧客登録
                            </button>
                        </div>

                        {/* メンバーシップフィルタリングボタン群 */}
                        <div className="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-100">
                            <span className="text-sm font-medium text-gray-700 self-center mr-2">会員ランク:</span>
                            {membershipLevels.map(level => (
                                <button
                                    key={level}
                                    onClick={() => setFilterMembership(level)}
                                    className={`px-3 py-1 text-sm font-semibold rounded-full transition duration-150 ${filterMembership === level
                                        ? 'bg-orange-600 text-white shadow-md'
                                        : 'bg-gray-100 text-gray-700 hover:bg-orange-50 hover:text-orange-600'
                                        }`}
                                >
                                    {level === 'ALL' ? '全顧客' : level}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* データ表示テーブル */}
                    {loading ? (
                        <div className="text-center py-10 text-gray-500">
                            <span className="animate-pulse">顧客データを読み込み中...</span>
                        </div>
                    ) : users.length === 0 ? (
                        <div className="text-center py-10 text-gray-500">
                            該当する顧客情報はありません。フィルタ条件を変更してください。
                        </div>
                    ) : (
                        <div className="overflow-x-auto shadow-md rounded-lg">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">顧客名</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">連絡先</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">会員ランク</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">最終来店日</th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">アクション</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {users.map((user) => (
                                        <tr key={user.id} className="hover:bg-orange-50 transition duration-100">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <div className="font-semibold">{user.name}</div>
                                                <div className="text-xs text-gray-500 mt-1">予約合計: {user.totalReservations}件</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                <div className="flex items-center space-x-1">
                                                    <Mail className="w-3 h-3 text-gray-400" />
                                                    <span className="text-xs">{user.email}</span>
                                                </div>
                                                <div className="flex items-center space-x-1 mt-1">
                                                    <Phone className="w-3 h-3 text-gray-400" />
                                                    <span className="text-xs">{user.phone}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                <MembershipBadge membership={user.membership} />
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                <div className="flex items-center space-x-1">
                                                    <Clock className="w-4 h-4 text-gray-500" />
                                                    <span>{user.lastVisit}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div className="flex justify-end space-x-2">
                                                    <button
                                                        onClick={() => handleEditUser(user.id)}
                                                        title="詳細編集"
                                                        className="text-orange-600 hover:text-orange-900 p-2 rounded-full hover:bg-orange-100 transition duration-150"
                                                    >
                                                        <List className="w-5 h-5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
