<?php 

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

// ✅ FormRequest（Admin配下）
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;

class ProductController extends Controller
{
    /**
     * 商品一覧ページを表示（React / Inertia）
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // 検索機能
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // 並び替え機能
        $sort = match ($request->input('sort')) {
            'high_price' => 'price',
            'low_price'  => 'price',
            default      => 'created_at',
        };
        $order = match ($request->input('sort')) {
            'high_price' => 'desc',
            'low_price'  => 'asc',
            default      => 'desc',
        };

        $products = $query->orderBy($sort, $order)->paginate(12);

        return Inertia::render('Admin/Product/Index', [
            'products' => $products,
            'filters'  => $request->only(['keyword', 'sort']),
        ]);
    }

    /**
     * 新規商品登録フォームを表示
     */
    public function create()
    {
        return Inertia::render('Admin/Product/Create');
    }

    /**
     * 新規商品を登録
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        // ✅ put() は失敗時に false になり得るため、store() でパス文字列を確実に取得する
        $imagePath = $request->file('image')->store('products', 'public');

        if (!$imagePath) {
            return redirect()->back()->with('error', '画像のアップロードに失敗しました。');
        }

        Product::create([
            'name'        => $validated['name'],
            'description' => $validated['description'],
            'price'       => $validated['price'],
            'stock'       => $validated['stock'],
            'image_path'  => $imagePath,
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', '商品を登録しました。');
    }

    /**
     * 商品詳細
     */
    public function show(Product $product)
    {
        return Inertia::render('Admin/Product/Show', [
            'product' => $product,
        ]);
    }

    /**
     * 編集ページ
     */
    public function edit(Product $product)
    {
        return Inertia::render('Admin/Product/Edit', [
            'product' => $product,
        ]);
    }

    /**
     * 商品を更新
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        // 画像更新処理
        if ($request->hasFile('image')) {
            if ($product->image_path && $product->image_path !== '0') {
                Storage::disk('public')->delete($product->image_path);
            }

            // ✅ put() は失敗時に false になり得るため、store() でパス文字列を確実に取得する
            $imagePath = $request->file('image')->store('products', 'public');

            if (!$imagePath) {
                return redirect()->back()->with('error', '画像のアップロードに失敗しました。');
            }
        } else {
            $imagePath = $product->image_path;
        }

        $product->update([
            'name'        => $validated['name'],
            'description' => $validated['description'],
            'price'       => $validated['price'],
            'stock'       => $validated['stock'],
            'image_path'  => $imagePath,
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', '商品が更新されました。');
    }

    /**
     * 商品を削除
     */
    public function destroy(Product $product)
    {
        if ($product->image_path && $product->image_path !== '0') {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', '商品が削除されました。');
    }
}
