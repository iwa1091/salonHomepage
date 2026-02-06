<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminBlockController extends Controller
{
    /**
     * ブロック作成
     * POST /admin/api/blocks
     */
    public function store(Request $request)
    {
        $v = $request->validate([
            'date'             => ['required', 'date'],
            // ✅ 予約は lane=1 想定なので、ブロックは 2 or 3 に制限（Timetable.jsx の想定に合わせる）
            'lane'             => ['required', 'integer', 'in:2,3'],
            'start_time'       => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:600'],

            // ✅ lash-brow-ohana（ReservationForm.jsx）の項目に寄せる
            'name'             => ['nullable', 'string', 'max:255'],
            'email'            => ['nullable', 'email', 'max:255'],
            'phone'            => ['nullable', 'string', 'max:20'],
            'service_id'       => ['nullable', 'exists:services,id'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        // ✅ 15分刻み（ズレ防止）
        if ($v['duration_minutes'] % 15 !== 0) {
            return response()->json(['message' => 'duration_minutes は15分刻みで指定してください'], 422);
        }

        $start = Carbon::createFromFormat('Y-m-d H:i', $v['date'] . ' ' . $v['start_time']);
        $end   = (clone $start)->addMinutes((int) $v['duration_minutes']);

        $block = AdminBlock::create([
            'date'       => $v['date'],
            'lane'       => (int) $v['lane'],
            'start_time' => $start->format('H:i:s'),
            'end_time'   => $end->format('H:i:s'),

            'name'       => $v['name'] ?? null,
            'email'      => $v['email'] ?? null,
            'phone'      => $v['phone'] ?? null,
            'service_id' => $v['service_id'] ?? null,
            'notes'      => $v['notes'] ?? null,
        ]);

        $block->load('service');

        return response()->json($this->toResponse($block), 201);
    }

    /**
     * ブロック更新
     * PUT /admin/api/blocks/{id}
     */
    public function update(Request $request, $id)
    {
        $block = AdminBlock::findOrFail($id);

        $v = $request->validate([
            // フロント（Timetable.jsx）の payload に date が含まれる想定で受ける
            'date'             => ['required', 'date'],
            'lane'             => ['required', 'integer', 'in:2,3'],
            'start_time'       => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:600'],

            'name'             => ['nullable', 'string', 'max:255'],
            'email'            => ['nullable', 'email', 'max:255'],
            'phone'            => ['nullable', 'string', 'max:20'],
            'service_id'       => ['nullable', 'exists:services,id'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        if ($v['duration_minutes'] % 15 !== 0) {
            return response()->json(['message' => 'duration_minutes は15分刻みで指定してください'], 422);
        }

        $start = Carbon::createFromFormat('Y-m-d H:i', $v['date'] . ' ' . $v['start_time']);
        $end   = (clone $start)->addMinutes((int) $v['duration_minutes']);

        $block->update([
            'date'       => $v['date'],
            'lane'       => (int) $v['lane'],
            'start_time' => $start->format('H:i:s'),
            'end_time'   => $end->format('H:i:s'),

            'name'       => $v['name'] ?? null,
            'email'      => $v['email'] ?? null,
            'phone'      => $v['phone'] ?? null,
            'service_id' => $v['service_id'] ?? null,
            'notes'      => $v['notes'] ?? null,
        ]);

        $block->load('service');

        return response()->json($this->toResponse($block));
    }

    /**
     * ブロック削除
     * DELETE /admin/api/blocks/{id}
     */
    public function destroy($id)
    {
        $block = AdminBlock::findOrFail($id);
        $block->delete();

        return response()->json(['message' => 'ブロックを削除しました']);
    }

    /**
     * API返却用の整形（Timetable 側で使いやすい形）
     */
    private function toResponse(AdminBlock $block): array
    {
        return [
            'id'         => $block->id,
            'date'       => optional($block->date)->format('Y-m-d'),
            'lane'       => (int) $block->lane,
            'start_time' => $block->start_time, // "H:i:s"
            'end_time'   => $block->end_time,   // "H:i:s"

            'name'       => $block->name,
            'email'      => $block->email,
            'phone'      => $block->phone,
            'notes'      => $block->notes,

            'service_id'   => $block->service_id,
            'service_name' => $block->service?->name,
        ];
    }
}
