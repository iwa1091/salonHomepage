import { Head, useForm, router } from "@inertiajs/react";
import { useState } from "react";
import "../../../css/pages/auth/authentication.css";

export default function Login() {
    const { data, setData, post, processing, errors, setError, clearErrors } = useForm({
        email: "",
        password: "",
    });

    const [sessionExpired, setSessionExpired] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        clearErrors();
        setSessionExpired(false);

        post("/admin/login", {
            onError: () => {
                // Inertia のバリデーションエラー（422）はここで処理される
                // 何もしなくても errors に自動セットされる
            },
            onFinish: () => {
                // パスワードフィールドをクリア
                setData("password", "");
            },
        });
    };

    // Inertia のグローバルエラーハンドリングで 419 を検知
    // コンポーネントマウント時に1回だけ登録
    useState(() => {
        const removeListener = router.on("invalid", (event) => {
            const status = event.detail.response?.status;
            if (status === 419) {
                event.preventDefault();
                setSessionExpired(true);
                // CSRFトークンを更新するためにページをリロード
                window.location.reload();
            }
        });

        return removeListener;
    });

    return (
        <>
            <Head title="管理者ログイン" />

            <div className="authentication-page">
                <div className="authentication-container">
                    <form onSubmit={submit} noValidate className="authenticate-form">
                        <h1 className="page__title">管理者ログイン</h1>

                        {/* セッション期限切れメッセージ */}
                        {sessionExpired && (
                            <div className="form__error" style={{ marginBottom: "1rem", textAlign: "center" }}>
                                セッションの有効期限が切れました。ページを更新しています...
                            </div>
                        )}

                        {/* メールアドレス */}
                        <div className="form-group">
                            <label htmlFor="email" className="entry__name">
                                メールアドレス
                            </label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                className="input"
                                value={data.email}
                                onChange={(e) => setData("email", e.target.value)}
                                autoComplete="email"
                                autoFocus
                            />
                            {errors.email && (
                                <span className="form__error">{errors.email}</span>
                            )}
                        </div>

                        {/* パスワード */}
                        <div className="form-group">
                            <label htmlFor="password" className="entry__name">
                                パスワード
                            </label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                className="input"
                                value={data.password}
                                onChange={(e) => setData("password", e.target.value)}
                                autoComplete="current-password"
                            />
                            {errors.password && (
                                <span className="form__error">{errors.password}</span>
                            )}
                        </div>

                        {/* ログインボタン */}
                        <button
                            type="submit"
                            className="btn btn--big"
                            disabled={processing || sessionExpired}
                        >
                            {sessionExpired ? "更新中..." : "ログイン"}
                        </button>
                    </form>
                </div>
            </div>
        </>
    );
}
