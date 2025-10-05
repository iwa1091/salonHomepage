<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Service;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    // 管理者用一覧ページ
    public function index()
    {
        $services = Service::with('category')
            ->orderBy('sort_order', 'asc')
            ->orderByDesc('id')
            ->get();

        $serviceData = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
                'sort_order' => $service->sort_order,
                'is_active' => $service->is_active,
                'image_url' => $service->image ? Storage::url($service->image) : null,
                'features' => $service->features ?? [],
                'category' => $service->category ? $service->category->name : null,
                'category_id' => $service->category_id,
                'is_popular' => $service->is_popular,
            ];
        });

        return Inertia::render('Admin/ServiceIndex', [
            'services' => $serviceData,
            'categories' => Category::orderBy('sort_order')
                                    ->get(['id', 'name']),
        ]);
    }

    // 一般ユーザー向けサービス一覧
    public function publicIndex()
    {
        $services = Service::with('category')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderByDesc('id')
            ->get()
            ->map(function ($service) {
                return (object)[
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'duration' => $service->duration_minutes,
                    'price' => $service->price,
                    'image_url' => $service->image ? Storage::url($service->image) : null,
                    'features' => $service->features ?? [],
                    'category' => $service->category ? $service->category->name : null,
                    'is_popular' => $service->is_popular,
                ];
            });

        return view('menu_price', ['services' => $services]);
    }

    // 作成フォーム表示
    public function create()
    {
        $categories = Category::orderBy('sort_order')
                              ->get(['id', 'name']);

        return Inertia::render('Admin/ServiceForm', [
            'service' => null,
            'categories' => $categories,
        ]);
    }

    // 作成保存
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:services,name',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1|max:480',
            'price' => 'required|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'image' => 'nullable|image|max:2048',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'is_popular' => 'boolean',
        ]);

        if (isset($validated['features'])) {
            $validated['features'] = json_encode($validated['features'], JSON_UNESCAPED_UNICODE);
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('services', 'public');
        }

        Service::create($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'サービスを作成しました');
    }

    // 編集フォーム表示
    public function edit(Service $service)
    {
        $categories = Category::orderBy('sort_order')
                              ->get(['id', 'name']);

        return Inertia::render('Admin/ServiceForm', [
            'service' => $service->load('category'),
            'categories' => $categories,
        ]);
    }

    // 更新
    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:services,name,' . $service->id,
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1|max:480',
            'price' => 'required|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'image' => 'nullable|image|max:2048',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'is_popular' => 'boolean',
        ]);

        if (isset($validated['features'])) {
            $validated['features'] = json_encode($validated['features'], JSON_UNESCAPED_UNICODE);
        }

        if ($request->hasFile('image')) {
            if ($service->image) {
                Storage::disk('public')->delete($service->image);
            }
            $validated['image'] = $request->file('image')->store('services', 'public');
        }

        $service->update($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'サービスを更新しました');
    }

    // 削除
    public function destroy(Service $service)
    {
        if ($service->image) {
            Storage::disk('public')->delete($service->image);
        }

        $service->delete();

        return redirect()->route('admin.services.index')
            ->with('success', 'サービスを削除しました');
    }

    // 公開/非公開切替 (AJAX)
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
