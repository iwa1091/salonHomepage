import { Head, useForm } from "@inertiajs/react";
import "../../../css/pages/auth/authentication.css";

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: "",
        password: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post("/admin/login");
    };

    return (
        <>
            <Head title="管理者ログイン" />

            <div className="authentication-page">
                <div className="authentication-container">
                    <form onSubmit={submit} noValidate className="authenticate-form">
                        <h1 className="page__title">管理者ログイン</h1>

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
                            disabled={processing}
                        >
                            ログイン
                        </button>
                    </form>
                </div>
            </div>
        </>
    );
}
