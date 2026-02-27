// /resources/js/Pages/Admin/Timetable.jsx
import { useEffect, useMemo, useRef, useState } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import { route } from "ziggy-js";
import "../../../css/pages/admin/timetable.css";

/**
 * 固定レーン
 * - 予約は常に lane=1（枠1）
 * - 管理者ブロックは lane=2/3（枠2/調整枠）
 */
const LANES = [
    { id: 1, label: "枠1", sub: "予約" },
    { id: 2, label: "枠2", sub: "ブロック" },
    { id: 3, label: "調整枠", sub: "ブロック" },
];

const SLOT_MINUTES = 15;
const SLOT_WIDTH_PX = 44; // 15分=1枠の横幅（CSSとも合わせやすい固定値）
const LANE_LABEL_WIDTH_PX = 120;

function pad2(n) {
    return String(n).padStart(2, "0");
}

function toYmd(d) {
    const y = d.getFullYear();
    const m = pad2(d.getMonth() + 1);
    const day = pad2(d.getDate());
    return `${y}-${m}-${day}`;
}

function fromYmd(ymd) {
    const [y, m, d] = String(ymd).split("-").map((v) => parseInt(v, 10));
    return new Date(y, (m || 1) - 1, d || 1);
}

function addDays(ymd, delta) {
    const base = fromYmd(ymd);
    base.setDate(base.getDate() + delta);
    return toYmd(base);
}

function extractHHmm(value) {
    if (!value) return null;
    const s = String(value).trim();
    const m = s.match(/(\d{2}:\d{2})/);
    return m ? m[1] : null;
}

function hhmmToMinutes(hhmm) {
    const t = extractHHmm(hhmm);
    if (!t) return null;
    const [hh, mm] = t.split(":").map((v) => parseInt(v, 10));
    if (Number.isNaN(hh) || Number.isNaN(mm)) return null;
    return hh * 60 + mm;
}

function minutesToHHmm(min) {
    const h = Math.floor(min / 60);
    const m = min % 60;
    return `${pad2(h)}:${pad2(m)}`;
}

function clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
}

function diffMinutes(startHHmm, endHHmm) {
    const s = hhmmToMinutes(startHHmm);
    const e = hhmmToMinutes(endHHmm);
    if (s == null || e == null) return null;
    return e - s;
}

function addMinutesToHHmm(startHHmm, addMin) {
    const s = hhmmToMinutes(startHHmm);
    if (s == null) return null;
    return minutesToHHmm(s + (Number(addMin) || 0));
}

function buildTimeOptions(openHHmm, closeHHmm) {
    const openMin = hhmmToMinutes(openHHmm);
    const closeMin = hhmmToMinutes(closeHHmm);
    if (openMin == null || closeMin == null) return [];

    const options = [];
    for (let t = openMin; t <= closeMin - SLOT_MINUTES; t += SLOT_MINUTES) {
        options.push(minutesToHHmm(t));
    }
    return options;
}

function buildDurationOptions(maxMinutes = 600) {
    const opts = [];
    for (let m = SLOT_MINUTES; m <= maxMinutes; m += SLOT_MINUTES) {
        opts.push(m);
    }
    return opts;
}

/**
 * ReservationForm の項目に合わせた表示行を作る
 * 優先：name / phone / email / service
 */
function buildDisplayLines(item, getServiceNameById) {
    const name = item?.name ? String(item.name) : "";
    const phone = item?.phone ? String(item.phone) : "";
    const email = item?.email ? String(item.email) : "";

    // サービス名の取得（APIの形が違っても吸収）
    const serviceId = item?.service_id ?? item?.serviceId ?? "";
    const serviceName =
        item?.service_name ||
        item?.serviceName ||
        item?.service?.name ||
        (serviceId ? getServiceNameById(serviceId) : "") ||
        "";

    const lines = [];
    if (name) lines.push(name);
    if (phone) lines.push(phone);
    if (email) lines.push(email);
    if (serviceName) lines.push(serviceName);

    // 備考を出したい場合（長いので控えめに）
    // const notes = item?.notes ? String(item.notes) : "";
    // if (notes) lines.push(notes.length > 22 ? `${notes.slice(0, 22)}…` : notes);

    return lines;
}

/**
 * Timetable
 * - GET /admin/api/timetable?date=YYYY-MM-DD から営業時間 + 予約 + ブロックを取得して表示
 */
export default function Timetable() {
    const { date: initialDate } = usePage().props;

    const [date, setDate] = useState(initialDate || toYmd(new Date()));

    // props の date が変わったら state を同期（Inertia遷移で state が残る対策）
    useEffect(() => {
        const next = initialDate ? String(initialDate).slice(0, 10) : "";
        if (next && next !== date) setDate(next);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [initialDate]);

    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState("");

    const [businessHour, setBusinessHour] = useState({
        is_closed: false,
        open_time: "09:00",
        close_time: "19:30",
    });

    const [reservations, setReservations] = useState([]);
    const [blocks, setBlocks] = useState([]);

    // ReservationForm と同様：サービス一覧（メニュー）を取得して select に使う
    const [services, setServices] = useState([]);

    useEffect(() => {
        let mounted = true;
        (async () => {
            try {
                const res = await fetch("/api/services", {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) return;
                const data = await res.json();
                if (mounted && Array.isArray(data)) setServices(data);
            } catch {
                // 取得失敗してもタイムテーブル自体は動かす
            }
        })();
        return () => {
            mounted = false;
        };
    }, []);

    const getServiceNameById = (id) => {
        const sid = String(id ?? "");
        const hit = services.find((s) => String(s.id) === sid);
        return hit?.name ? String(hit.name) : "";
    };

    // ブロック作成/編集モーダル
    const [modalOpen, setModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState("create"); // "create" | "edit"
    const [editingBlockId, setEditingBlockId] = useState(null);
    const [modalError, setModalError] = useState("");
    const [saving, setSaving] = useState(false);
    // ドラッグリサイズ用の state / ref
    const [dragging, setDragging] = useState(null);
    const dragRef = useRef(null);
    const didDragRef = useRef(false); // ドラッグ中フラグ（クリック抑制用）

    // ReservationForm の項目に合わせる（name / phone / email / service_id / notes）
    const [form, setForm] = useState({
        lane: 2,
        start_time: "09:00",
        duration_minutes: 60,
        name: "",
        phone: "",
        email: "",
        service_id: "",
        notes: "",
    });

    const openHHmm = extractHHmm(businessHour?.open_time) || "09:00";
    const closeHHmm = extractHHmm(businessHour?.close_time) || "19:30";
    const isClosed = !!businessHour?.is_closed;

    const openMin = hhmmToMinutes(openHHmm);
    const closeMin = hhmmToMinutes(closeHHmm);

    const slotCount = useMemo(() => {
        if (openMin == null || closeMin == null) return 0;
        const diff = closeMin - openMin;
        if (diff <= 0) return 0;
        return Math.ceil(diff / SLOT_MINUTES);
    }, [openMin, closeMin]);

    const timelineWidthPx = useMemo(() => slotCount * SLOT_WIDTH_PX, [slotCount]);

    const timeLabelHours = useMemo(() => {
        if (openMin == null || closeMin == null) return [];
        const labels = [];
        // 30分刻みの最初の区切り（営業開始以降で最も近い30分単位）
        const first = Math.ceil(openMin / 30) * 30;
        for (let t = first; t <= closeMin; t += 30) labels.push(t);
        return labels;
    }, [openMin, closeMin]);

    const timeOptions = useMemo(() => {
        if (isClosed) return [];
        return buildTimeOptions(openHHmm, closeHHmm);
    }, [openHHmm, closeHHmm, isClosed]);

    const durationOptions = useMemo(() => buildDurationOptions(600), []);

    // ✅ キャンセル扱いの判定（API側の表記ゆれも吸収）
    const isCanceledReservation = (r) => {
        const s = String(r?.status ?? "").toLowerCase();
        return s === "canceled" || s === "cancelled" || s === "cancel";
    };

    // ✅ fetchData の「直近呼び出し」ガード（focus/pageshow/手動再読込の連打防止）
    const lastReloadAtRef = useRef(0);

    const fetchData = async (ymd, signal) => {
        setLoading(true);
        setLoadError("");

        try {
            // ✅ キャッシュ回避（戻る/復帰時に古いJSONを掴むのを予防）
            const url = `/admin/api/timetable?date=${encodeURIComponent(ymd)}&t=${Date.now()}`;

            const res = await fetch(url, {
                method: "GET",
                signal,
                cache: "no-store",
                headers: {
                    Accept: "application/json",
                    "Cache-Control": "no-cache",
                    Pragma: "no-cache",
                },
            });

            if (!res.ok) {
                const text = await res.text().catch(() => "");
                throw new Error(`timetable API error: ${res.status} ${text}`);
            }

            const data = await res.json();

            const bh = data?.business_hour || data?.businessHour || {};
            const nextBh = {
                is_closed: !!bh.is_closed,
                open_time: extractHHmm(bh.open_time) || "09:00",
                close_time: extractHHmm(bh.close_time) || "19:30",
            };

            // 予約：ReservationForm の項目に合わせて phone/email/service_id/notes を保持
            // end_time が無い場合は duration から推測（あればそのまま）
            // ✅ status=canceled は Timetable では表示しない（キャンセル後に残り続ける不具合対策）
            const nextReservations = Array.isArray(data?.reservations)
                ? data.reservations
                    .map((r) => {
                        const st = extractHHmm(r.start_time);
                        const et = extractHHmm(r.end_time);
                        const dur =
                            Number(r.duration_minutes) ||
                            Number(r?.service?.duration_minutes) ||
                            Number(r?.service_duration_minutes) ||
                            0;

                        const fallbackEnd = !et && st && dur ? addMinutesToHHmm(st, dur) : et;

                        return {
                            type: "reservation",
                            ...r,
                            lane: 1,
                            start_time: st,
                            end_time: fallbackEnd,
                            // name/phone/email/service_id/notes は r のまま入ってくる想定
                        };
                    })
                    .filter((r) => !isCanceledReservation(r))
                : [];

            // ブロック：同じ項目（name/phone/email/service_id/notes）を想定
            const nextBlocks = Array.isArray(data?.blocks)
                ? data.blocks.map((b) => {
                    const st = extractHHmm(b.start_time);
                    const et = extractHHmm(b.end_time);
                    const dur = Number(b.duration_minutes) || 0;
                    const fallbackEnd = !et && st && dur ? addMinutesToHHmm(st, dur) : et;

                    return {
                        type: "block",
                        ...b,
                        lane: Number(b.lane) || 2,
                        start_time: st,
                        end_time: fallbackEnd,
                    };
                })
                : [];

            setBusinessHour(nextBh);
            setReservations(nextReservations);
            setBlocks(nextBlocks);
        } catch (e) {
            if (e?.name === "AbortError") return;
            console.error(e);
            setLoadError("データの取得に失敗しました。API のルート/実装を確認してください。");
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        const ac = new AbortController();
        fetchData(date, ac.signal);
        return () => ac.abort();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [date]);

    // ✅ 画面復帰（戻る/フォーカス/タブ復帰）で最新を取り直す（キャンセル等の反映漏れ対策）
    useEffect(() => {
        const tryReload = () => {
            if (document.hidden) return;

            const now = Date.now();
            if (now - lastReloadAtRef.current < 1500) return; // 連打防止
            lastReloadAtRef.current = now;

            fetchData(date);
        };

        const onVis = () => {
            if (!document.hidden) tryReload();
        };

        window.addEventListener("focus", tryReload);
        window.addEventListener("pageshow", tryReload);
        document.addEventListener("visibilitychange", onVis);

        return () => {
            window.removeEventListener("focus", tryReload);
            window.removeEventListener("pageshow", tryReload);
            document.removeEventListener("visibilitychange", onVis);
        };
    }, [date]);

    const goDate = (ymd) => {
        setDate(ymd);
        router.get(route("admin.timetable.index", { date: ymd }), {}, { preserveState: true, replace: true });
    };

    const openCreateModal = () => {
        if (isClosed) return;

        setModalMode("create");
        setEditingBlockId(null);
        setModalError("");

        setForm({
            lane: 2,
            start_time: timeOptions[0] || openHHmm,
            duration_minutes: 60,
            name: "",
            phone: "",
            email: "",
            service_id: "",
            notes: "",
        });

        setModalOpen(true);
    };

    const openEditModal = (block) => {
        setModalMode("edit");
        setEditingBlockId(block.id);
        setModalError("");

        const dur = diffMinutes(block.start_time, block.end_time);

        setForm({
            lane: Number(block.lane) || 2,
            start_time: extractHHmm(block.start_time) || openHHmm,
            duration_minutes: dur && dur > 0 ? dur : Number(block.duration_minutes) || 60,
            name: block.name || "",
            phone: block.phone || "",
            email: block.email || "",
            service_id: String(block.service_id ?? block.serviceId ?? "") || "",
            notes: block.notes || "",
        });

        setModalOpen(true);
    };

    const closeModal = () => {
        if (saving) return;
        setModalOpen(false);
        setModalError("");
    };

    const validateBlock = () => {
        const dur = Number(form.duration_minutes) || 0;
        if (dur <= 0 || dur % SLOT_MINUTES !== 0) {
            return "所要時間は15分刻みで指定してください。";
        }

        const sMin = hhmmToMinutes(form.start_time);
        if (sMin == null) return "開始時刻が不正です。";

        if (openMin != null && sMin < openMin) return "開始時刻が営業時間外です。";
        if (closeMin != null && sMin + dur > closeMin) return "終了時刻が営業時間を超えています。";

        const lane = Number(form.lane);
        if (![2, 3].includes(lane)) return "レーンは「枠2」または「調整枠」を選択してください。";

        return "";
    };

    const saveBlock = async () => {
        if (saving) return;

        setSaving(true);
        setModalError("");

        try {
            const v = validateBlock();
            if (v) {
                setModalError(v);
                return;
            }

            const payload = {
                date,
                lane: Number(form.lane),
                start_time: form.start_time,
                duration_minutes: Number(form.duration_minutes),

                // ReservationForm と同じ項目
                name: form.name || null,
                phone: form.phone || null,
                email: form.email || null,
                service_id: form.service_id ? Number(form.service_id) : null,
                notes: form.notes || null,
            };

            const isEdit = modalMode === "edit" && editingBlockId;

            const url = isEdit
                ? `/admin/api/blocks/${editingBlockId}`
                : `/admin/api/blocks`;
            const method = isEdit ? "put" : "post";

            let res;
            if (window.axios && typeof window.axios[method] === "function") {
                res = await window.axios[method](url, payload);
            } else {
                // fetch フォールバック — CSRF トークンを付与
                const csrfToken = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");

                res = await fetch(url, {
                    method: isEdit ? "PUT" : "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...(csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {}),
                    },
                    body: JSON.stringify(payload),
                });
                if (!res.ok) {
                    const json = await res.json().catch(() => null);

                    // 419 = CSRF トークン失効 → リロードで再取得
                    if (res.status === 419) {
                        alert("セッションの有効期限が切れました。ページを再読み込みします。");
                        window.location.reload();
                        return;
                    }

                    const msg =
                        json?.message ||
                        (json?.errors
                            ? Object.values(json.errors).flat().join("\n")
                            : "") ||
                        "保存に失敗しました。";
                    throw new Error(msg);
                }
            }

            if (!res || (res.status && res.status >= 400)) {
                setModalError("保存に失敗しました。");
                return;
            }

            setModalOpen(false);
            await fetchData(date);
        } catch (e) {
            // axios 経由の 419 エラー
            if (e?.response?.status === 419) {
                alert("セッションの有効期限が切れました。ページを再読み込みします。");
                window.location.reload();
                return;
            }

            const msg =
                e?.response?.data?.message ||
                (e?.response?.data?.errors
                    ? Object.values(e.response.data.errors).flat().join("\n")
                    : "") ||
                e?.message ||
                "保存に失敗しました。";
            setModalError(msg);
        } finally {
            setSaving(false);
        }
    };

    const deleteBlock = async () => {
        if (saving) return;
        if (!editingBlockId) return;
        if (!confirm("このブロックを削除しますか？")) return;

        setSaving(true);
        setModalError("");

        try {
            if (window.axios) {
                await window.axios.delete(`/admin/api/blocks/${editingBlockId}`);
            } else {
                const res = await fetch(`/admin/api/blocks/${editingBlockId}`, {
                    method: "DELETE",
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error("削除に失敗しました。");
            }

            setModalOpen(false);
            await fetchData(date);
        } catch (e) {
            const msg =
                e?.response?.data?.message ||
                e?.message ||
                "削除に失敗しました。（API ルート/実装を確認してください）";
            setModalError(msg);
        } finally {
            setSaving(false);
        }
    };

    const deleteReservation = async (reservationId) => {
        if (!reservationId) return;
        if (!confirm("この予約を削除しますか？")) return;

        // ✅ 他ページと同じく「admin web ルート」で削除（認証/CSRFを統一）
        router.post(
            route("admin.reservations.destroy", reservationId),
            {},
            {
                preserveScroll: true,
                onSuccess: () => fetchData(date),
                onError: () => alert("削除に失敗しました。"),
            }
        );
    };

    const blocksByLane = useMemo(() => {
        const by = new Map();
        for (const lane of LANES) by.set(lane.id, []);

        const all = [
            ...reservations.map((r) => ({ ...r, lane: 1, type: "reservation" })),
            ...blocks.map((b) => ({ ...b, type: "block" })),
        ];

        for (const item of all) {
            const laneId = Number(item.lane) || 1;
            if (!by.has(laneId)) by.set(laneId, []);
            by.get(laneId).push(item);
        }

        for (const [k, arr] of by.entries()) {
            arr.sort((a, b) => {
                const am = hhmmToMinutes(a.start_time) ?? 0;
                const bm = hhmmToMinutes(b.start_time) ?? 0;
                return am - bm;
            });
            by.set(k, arr);
        }

        return by;
    }, [reservations, blocks]);

    /**
     * ドラッグリサイズ開始
     * edge: "left" | "right"
     */
    const onResizeStart = (e, item, edge) => {
        e.stopPropagation();
        e.preventDefault();

        const s = hhmmToMinutes(item.start_time);
        const eTime = hhmmToMinutes(item.end_time);
        if (s == null || eTime == null) return;

        const startClamped = clamp(s, openMin, closeMin);
        const endClamped = clamp(eTime, openMin, closeMin);
        const startIndex = Math.floor((startClamped - openMin) / SLOT_MINUTES);
        const span = Math.ceil((endClamped - startClamped) / SLOT_MINUTES);

        const info = {
            blockId: item.id,
            item,
            edge,
            startX: e.clientX,
            originalLeft: startIndex * SLOT_WIDTH_PX,
            originalWidth: span * SLOT_WIDTH_PX,
            currentLeft: startIndex * SLOT_WIDTH_PX,
            currentWidth: span * SLOT_WIDTH_PX,
        };

        didDragRef.current = false;
        setDragging(info);
        dragRef.current = info;
    };

    /**
     * ドラッグ完了 → API で時間を更新
     */
    const resizeBlock = async (item, newStartTime, newDuration) => {
        try {
            const payload = {
                date,
                lane: Number(item.lane),
                start_time: newStartTime,
                duration_minutes: newDuration,
                name: item.name || null,
                phone: item.phone || null,
                email: item.email || null,
                service_id: item.service_id ? Number(item.service_id) : null,
                notes: item.notes || null,
            };

            if (window.axios && typeof window.axios.put === "function") {
                await window.axios.put(`/admin/api/blocks/${item.id}`, payload);
            } else {
                const csrfToken = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");

                const res = await fetch(`/admin/api/blocks/${item.id}`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...(csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {}),
                    },
                    body: JSON.stringify(payload),
                });

                if (res.status === 419) {
                    alert("セッションの有効期限が切れました。ページを再読み込みします。");
                    window.location.reload();
                    return;
                }
                if (!res.ok) throw new Error("更新に失敗しました。");
            }

            await fetchData(date);
        } catch (e) {
            alert(
                e?.response?.data?.message ||
                e?.message ||
                "時間の変更に失敗しました。"
            );
            await fetchData(date);
        }
    };

    /**
     * 予約のドラッグリサイズ完了 → API で時間を更新
     */
    const resizeReservation = async (item, newStartTime, newDuration) => {
        try {
            const start = newStartTime; // "HH:mm"
            const [h, m] = start.split(":").map(Number);
            const endMin = h * 60 + m + newDuration;
            const endTime = minutesToHHmm(endMin);

            const payload = {
                date,
                start_time: start,
                end_time: endTime,
                duration_minutes: newDuration,
            };

            if (window.axios && typeof window.axios.put === "function") {
                await window.axios.put(`/admin/api/reservations/${item.id}`, payload);
            } else {
                const csrfToken = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");

                const res = await fetch(`/admin/api/reservations/${item.id}`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...(csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {}),
                    },
                    body: JSON.stringify(payload),
                });

                if (res.status === 419) {
                    alert("セッションの有効期限が切れました。ページを再読み込みします。");
                    window.location.reload();
                    return;
                }
                if (!res.ok) throw new Error("更新に失敗しました。");
            }

            await fetchData(date);
        } catch (e) {
            alert(
                e?.response?.data?.message ||
                e?.message ||
                "時間の変更に失敗しました。"
            );
            await fetchData(date);
        }
    };

    /**
     * ドラッグ中の mousemove / mouseup をウィンドウレベルで監視
     */
    useEffect(() => {
        if (!dragging) return;

        const onMouseMove = (e) => {
            const info = dragRef.current;
            if (!info) return;

            const deltaX = e.clientX - info.startX;
            const snappedSlots = Math.round(deltaX / SLOT_WIDTH_PX);
            const snappedPx = snappedSlots * SLOT_WIDTH_PX;

            if (snappedPx !== 0) didDragRef.current = true;

            let newLeft = info.originalLeft;
            let newWidth = info.originalWidth;

            if (info.edge === "right") {
                newWidth = info.originalWidth + snappedPx;
                const maxWidth = slotCount * SLOT_WIDTH_PX - newLeft;
                newWidth = Math.min(newWidth, maxWidth);
                newWidth = Math.max(newWidth, SLOT_WIDTH_PX);
            } else {
                newLeft = info.originalLeft + snappedPx;
                newWidth = info.originalWidth - snappedPx;
                if (newLeft < 0) {
                    newWidth += newLeft;
                    newLeft = 0;
                }
                if (newWidth < SLOT_WIDTH_PX) {
                    newLeft = info.originalLeft + info.originalWidth - SLOT_WIDTH_PX;
                    newWidth = SLOT_WIDTH_PX;
                }
            }

            const updated = { ...info, currentLeft: newLeft, currentWidth: newWidth };
            dragRef.current = updated;
            setDragging(updated);
        };

        const onMouseUp = async () => {
            const info = dragRef.current;
            dragRef.current = null;
            setDragging(null);

            if (!info || !info.item) return;

            // 変更なしならAPIを呼ばない
            if (
                info.currentLeft === info.originalLeft &&
                info.currentWidth === info.originalWidth
            ) {
                return;
            }

            const newStartMin =
                openMin + (info.currentLeft / SLOT_WIDTH_PX) * SLOT_MINUTES;
            const newDuration =
                (info.currentWidth / SLOT_WIDTH_PX) * SLOT_MINUTES;

            if (info.item.type === "reservation") {
                await resizeReservation(
                    info.item,
                    minutesToHHmm(newStartMin),
                    newDuration
                );
            } else {
                await resizeBlock(
                    info.item,
                    minutesToHHmm(newStartMin),
                    newDuration
                );
            }
        };

        window.addEventListener("mousemove", onMouseMove);
        window.addEventListener("mouseup", onMouseUp);
        return () => {
            window.removeEventListener("mousemove", onMouseMove);
            window.removeEventListener("mouseup", onMouseUp);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [dragging !== null]);

    const renderItemBlock = (item) => {
        if (openMin == null || closeMin == null || slotCount <= 0) return null;

        const s = hhmmToMinutes(item.start_time);
        const e = hhmmToMinutes(item.end_time);
        if (s == null || e == null) return null;

        const startClamped = clamp(s, openMin, closeMin);
        const endClamped = clamp(e, openMin, closeMin);
        const dur = endClamped - startClamped;
        if (dur <= 0) return null;

        const startIndex = Math.floor((startClamped - openMin) / SLOT_MINUTES);
        const span = Math.ceil(dur / SLOT_MINUTES);

        let left = startIndex * SLOT_WIDTH_PX;
        let width = span * SLOT_WIDTH_PX;

        // ドラッグ中のブロックはプレビュー値を使う
        const isDragging =
            dragging && dragging.blockId === item.id;
        if (isDragging) {
            left = dragging.currentLeft;
            width = dragging.currentWidth;
        }

        // ドラッグ中のプレビュー用の時刻ラベルを計算
        const previewStartMin = openMin + (left / SLOT_WIDTH_PX) * SLOT_MINUTES;
        const previewEndMin = previewStartMin + (width / SLOT_WIDTH_PX) * SLOT_MINUTES;
        const timeLabel = isDragging
            ? `${minutesToHHmm(previewStartMin)}〜${minutesToHHmm(previewEndMin)}`
            : `${extractHHmm(item.start_time) || ""}〜${extractHHmm(item.end_time) || ""}`;

        const lines = buildDisplayLines(item, getServiceNameById);
        const isReservation = item.type === "reservation";

        const onClick = () => {
            // ドラッグ操作直後はクリックを無視する
            if (didDragRef.current) {
                didDragRef.current = false;
                return;
            }
            if (isReservation) {
                router.get(route("admin.reservations.edit", item.id));
                return;
            }
            openEditModal(item);
        };

        return (
            <div
                key={`${item.type}-${item.id}`}
                className={
                    "timetable-block " +
                    (isReservation
                        ? "timetable-block--reservation"
                        : "timetable-block--admin") +
                    (isDragging ? " timetable-block--dragging" : "")
                }
                style={{ left: `${left}px`, width: `${width}px` }}
                role="button"
                tabIndex={0}
                onClick={onClick}
                onKeyDown={(ev) => {
                    if (ev.key === "Enter") onClick();
                }}
                title={lines.join(" / ")}
            >
                <div className="timetable-block__time">{timeLabel}</div>

                <div className="timetable-block__body">
                    {lines.length > 0 ? (
                        lines.map((l, idx) => (
                            <div key={idx} className="timetable-block__line">
                                {l}
                            </div>
                        ))
                    ) : (
                        <div className="timetable-block__line">（内容なし）</div>
                    )}
                </div>

                {isReservation ? (
                    <button
                        type="button"
                        className="timetable-block__delete"
                        onClick={(ev) => {
                            ev.stopPropagation();
                            deleteReservation(item.id);
                        }}
                        aria-label="予約を削除"
                        title="予約を削除"
                    >
                        ×
                    </button>
                ) : null}

                {/* リサイズハンドル（枠1・枠2共通） */}
                <>
                    <div
                        className="timetable-block__handle timetable-block__handle--left"
                        onMouseDown={(ev) => onResizeStart(ev, item, "left")}
                    />
                    <div
                        className="timetable-block__handle timetable-block__handle--right"
                        onMouseDown={(ev) => onResizeStart(ev, item, "right")}
                    />
                </>
            </div>
        );
    };

    return (
        <div className="admin-timetable-page">
            {/* top bar */}
            <div className="admin-timetable-topbar">
                <div className="admin-timetable-back">
                    <Link href={route("admin.reservations.index")} className="admin-timetable-back-link">
                        予約カレンダーへ戻る
                    </Link>
                    <Link href={route("admin.dashboard")} className="admin-timetable-back-link">
                        ダッシュボード
                    </Link>
                </div>

                <div className="admin-timetable-nav">
                    <button
                        type="button"
                        className="admin-timetable-btn"
                        onClick={() => goDate(addDays(date, -1))}
                        disabled={loading}
                    >
                        ← 前日
                    </button>

                    <div className="admin-timetable-date">
                        <div className="admin-timetable-date__label">日付</div>
                        <div className="admin-timetable-date__value">{date}</div>
                        <div className="admin-timetable-date__sub">
                            {isClosed ? (
                                <span className="admin-timetable-closed">休業日</span>
                            ) : (
                                <span className="admin-timetable-open">
                                    {openHHmm}〜{closeHHmm}
                                </span>
                            )}
                        </div>
                    </div>

                    <button
                        type="button"
                        className="admin-timetable-btn"
                        onClick={() => goDate(addDays(date, 1))}
                        disabled={loading}
                    >
                        翌日 →
                    </button>
                </div>

                <div className="admin-timetable-actions">
                    <button
                        type="button"
                        className="admin-timetable-btn admin-timetable-btn--primary"
                        onClick={openCreateModal}
                        disabled={loading || isClosed}
                        title={isClosed ? "休業日は作成できません" : "管理者ブロックを作成"}
                    >
                        ＋ ブロック作成
                    </button>
                </div>
            </div>

            {loading ? (
                <div className="admin-timetable-loading">読み込み中...</div>
            ) : loadError ? (
                <div className="admin-timetable-error">{loadError}</div>
            ) : (
                <div className="admin-timetable-card">
                    <div className="admin-timetable-hint">
                        <span className="admin-timetable-hint__badge">15分刻み</span>
                        <span className="admin-timetable-hint__text">
                            予約は <b>枠1</b> に表示。枠2/調整枠は管理者ブロックで使用します。
                        </span>
                    </div>

                    {isClosed ? (
                        <div className="admin-timetable-closed-panel">
                            この日は休業日です（タイムテーブルは表示しません）。
                        </div>
                    ) : (
                        <div className="timetable-scroll">
                            {/* header */}
                            <div className="timetable-header">
                                <div
                                    className="timetable-header__lane"
                                    style={{ width: `${LANE_LABEL_WIDTH_PX}px` }}
                                />
                                <div
                                    className="timetable-header__timeline"
                                    style={{
                                        width: `${timelineWidthPx}px`,
                                        "--slot-width": `${SLOT_WIDTH_PX}px`,
                                    }}
                                >
                                    {timeLabelHours.map((min) => {
                                        if (min < openMin || min > closeMin) return null;

                                        const offsetSlots = Math.round((min - openMin) / SLOT_MINUTES);
                                        const leftPx = offsetSlots * SLOT_WIDTH_PX;
                                        const hhmm = minutesToHHmm(min);
                                        const isHour = min % 60 === 0;

                                        // 端（営業開始/終了）のラベルが見切れないように寄せる
                                        const isStart = min === openMin;
                                        const isEnd = min === closeMin;

                                        let left = leftPx;
                                        let transform = "translateX(-50%)";

                                        // 端だけ寄せて「見切れ」を防ぐ（6pxは好みで調整）
                                        if (isStart) {
                                            left = 6;
                                            transform = "translateX(0)";
                                        } else if (isEnd) {
                                            left = leftPx - 6;
                                            transform = "translateX(-100%)";
                                        }

                                        return (
                                            <div
                                                key={min}
                                                className={
                                                    "timetable-hour-label" +
                                                    (isHour ? " timetable-hour-label--hour" : " timetable-hour-label--half")
                                                }
                                                style={{ left: `${left}px`, transform }}
                                            >
                                                {hhmm}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* lanes */}
                            <div className="timetable-body">
                                {LANES.map((lane) => {
                                    const items = blocksByLane.get(lane.id) || [];
                                    return (
                                        <div key={lane.id} className="timetable-row">
                                            <div
                                                className="timetable-lane"
                                                style={{ width: `${LANE_LABEL_WIDTH_PX}px` }}
                                            >
                                                <div className="timetable-lane__title">{lane.label}</div>
                                                <div className="timetable-lane__sub">{lane.sub}</div>
                                            </div>

                                            <div
                                                className="timetable-track"
                                                style={{
                                                    width: `${timelineWidthPx}px`,
                                                    "--slot-width": `${SLOT_WIDTH_PX}px`,
                                                }}
                                            >
                                                {items.map(renderItemBlock)}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* modal */}
            {modalOpen ? (
                <div className="timetable-modal-overlay" onMouseDown={closeModal}>
                    <div
                        className="timetable-modal"
                        onMouseDown={(e) => e.stopPropagation()}
                        role="dialog"
                        aria-modal="true"
                    >
                        <div className="timetable-modal__header">
                            <div className="timetable-modal__title">
                                {modalMode === "edit" ? "ブロック編集" : "ブロック作成"}
                            </div>
                            <button
                                type="button"
                                className="timetable-modal__close"
                                onClick={closeModal}
                                disabled={saving}
                                aria-label="閉じる"
                            >
                                ×
                            </button>
                        </div>

                        <div className="timetable-modal__body">
                            {modalError ? <div className="timetable-modal__error">{modalError}</div> : null}

                            <form className="timetable-form-grid" noValidate onSubmit={(e) => e.preventDefault()}>
                                <label className="timetable-form-field">
                                    <span className="timetable-form-label">レーン</span>
                                    <select
                                        className="timetable-form-input"
                                        value={form.lane}
                                        onChange={(e) => setForm((p) => ({ ...p, lane: Number(e.target.value) }))}
                                        disabled={saving}
                                    >
                                        <option value={2}>枠2</option>
                                        <option value={3}>調整枠</option>
                                    </select>
                                </label>

                                <label className="timetable-form-field">
                                    <span className="timetable-form-label">開始</span>
                                    <select
                                        className="timetable-form-input"
                                        value={form.start_time}
                                        onChange={(e) => setForm((p) => ({ ...p, start_time: e.target.value }))}
                                        disabled={saving}
                                    >
                                        {timeOptions.map((t) => (
                                            <option key={t} value={t}>
                                                {t}
                                            </option>
                                        ))}
                                    </select>
                                </label>

                                <label className="timetable-form-field">
                                    <span className="timetable-form-label">所要時間</span>
                                    <select
                                        className="timetable-form-input"
                                        value={form.duration_minutes}
                                        onChange={(e) =>
                                            setForm((p) => ({
                                                ...p,
                                                duration_minutes: Number(e.target.value),
                                            }))
                                        }
                                        disabled={saving}
                                    >
                                        {durationOptions.map((m) => (
                                            <option key={m} value={m}>
                                                {m}分
                                            </option>
                                        ))}
                                    </select>
                                </label>

                                {/* ReservationForm と同じ項目 */}
                                <label className="timetable-form-field">
                                    <span className="timetable-form-label">お名前</span>
                                    <input
                                        className="timetable-form-input"
                                        value={form.name}
                                        onChange={(e) => setForm((p) => ({ ...p, name: e.target.value }))}
                                        disabled={saving}
                                        placeholder="例）田中 太郎"
                                    />
                                </label>

                                <label className="timetable-form-field">
                                    <span className="timetable-form-label">電話番号</span>
                                    <input
                                        className="timetable-form-input"
                                        value={form.phone}
                                        onChange={(e) => setForm((p) => ({ ...p, phone: e.target.value }))}
                                        disabled={saving}
                                        placeholder="例）09012345678"
                                    />
                                </label>

                                <label className="timetable-form-field">
                                    <span className="timetable-form-label">メール</span>
                                    <input
                                        className="timetable-form-input"
                                        value={form.email}
                                        onChange={(e) => setForm((p) => ({ ...p, email: e.target.value }))}
                                        disabled={saving}
                                        placeholder="例）example@mail.com"
                                    />
                                </label>

                                <label className="timetable-form-field timetable-form-field--full">
                                    <span className="timetable-form-label">メニュー</span>
                                    <select
                                        className="timetable-form-input"
                                        value={form.service_id}
                                        onChange={(e) => setForm((p) => ({ ...p, service_id: e.target.value }))}
                                        disabled={saving}
                                    >
                                        <option value="">選択してください</option>
                                        {services.map((s) => (
                                            <option key={s.id} value={s.id}>
                                                {s.name}（{s.duration_minutes}分）
                                            </option>
                                        ))}
                                    </select>
                                </label>

                                <label className="timetable-form-field timetable-form-field--full">
                                    <span className="timetable-form-label">備考</span>
                                    <textarea
                                        className="timetable-form-textarea"
                                        value={form.notes}
                                        onChange={(e) => setForm((p) => ({ ...p, notes: e.target.value }))}
                                        disabled={saving}
                                        rows={3}
                                        placeholder="必要に応じて"
                                    />
                                </label>
                            </form>
                        </div>

                        <div className="timetable-modal__footer">
                            {modalMode === "edit" ? (
                                <button
                                    type="button"
                                    className="admin-timetable-btn admin-timetable-btn--danger"
                                    onClick={deleteBlock}
                                    disabled={saving}
                                >
                                    削除
                                </button>
                            ) : (
                                <span />
                            )}

                            <div className="timetable-modal__footer-right">
                                <button
                                    type="button"
                                    className="admin-timetable-btn"
                                    onClick={closeModal}
                                    disabled={saving}
                                >
                                    キャンセル
                                </button>
                                <button
                                    type="button"
                                    className="admin-timetable-btn admin-timetable-btn--primary"
                                    onClick={saveBlock}
                                    disabled={saving}
                                >
                                    {saving ? "保存中..." : "保存"}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            ) : null}
        </div>
    );
}
