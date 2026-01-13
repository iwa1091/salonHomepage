import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// ✅ CSRF（Inertia/管理画面の更新・削除を安定させる）
const token = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

if (token) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
}

// ✅ APIがJSONを返す前提なら付けておくと事故が減る（任意だが推奨）
window.axios.defaults.headers.common["Accept"] = "application/json";
