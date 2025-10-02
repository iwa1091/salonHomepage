<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Service;

class ServiceController extends Controller
{
    // 一覧ページ
    public function index()
    {
        $services = Service::orderBy('id', 'desc')->get();
        return Inertia::render('Admin/ServiceIndex', [
            'services' => $services,
        ]);
    }

    // 作成フォーム表示
    public function create()
    {
        return Inertia::render('Admin/ServiceForm', [
            'service' => null,
        ]);
    }

    // 作成保存
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'duration' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'is_active' => 'required|boolean',
        ]);

        $service = Service::create($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'サービスを作成しました');
    }

    // 編集フォーム表示
    public function edit(Service $service)
    {
        return Inertia::render('Admin/ServiceForm', [
            'service' => $service,
        ]);
    }

    // 更新
    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'duration' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'is_active' => 'required|boolean',
        ]);

        $service->update($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'サービスを更新しました');
    }

    // 削除
    public function destroy(Service $service)
    {
        $service->delete();

        return redirect()->route('admin.services.index')
            ->with('success', 'サービスを削除しました');
    }

    // 公開/非公開切替
    public function toggleActive(Service $service)
    {
        $service->is_active = !$service->is_active;
        $service->save();

        return response()->json([
            'success' => true,
            'is_active' => $service->is_active,
        ]);
    }
}