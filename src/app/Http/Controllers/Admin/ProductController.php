<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Inertia\Inertia; // 🚀 ここを追加

class ProductController extends Controller
{
    /**
     * 商品一覧ページを表示する
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // 検索機能
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
        }

        // 並び替え機能
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');

        $query->orderBy($sort, $order);

        $products = $query->paginate(12);

        // 🚀 view() から Inertia::render() に変更
        return Inertia::render('Admin/Product/Index', [
            'products' => $products,
            'filters' => $request->only(['keyword', 'sort', 'order']), // フィルタ情報も渡す（オプション）
        ]);
        // NOTE: コンポーネントのパスは 'Admin/Product/Index' と仮定しています。
        // 実際のファイルパスが resources/js/Pages/Admin/Product/Index.vue/jsx であることを確認してください。
    }

    /**
     * 新規商品登録フォームを表示する
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        // 🚀 view() から Inertia::render() に変更
        return Inertia::render('Admin/Product/Create');
    }

    /**
     * フォームから送信された商品を保存する
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // バリデーション
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'description' => 'required',
            'price' => 'required|integer',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MBまで
        ]);

        // 画像の保存
        $imagePath = Storage::disk('public')->put('products', $request->file('image'));

        // データベースに保存
        Product::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'price' => $validatedData['price'],
            'image_path' => $imagePath,
        ]);

        return redirect()->route('admin.products.index')->with('success', '商品を登録しました。');
    }

    /**
     * 商品詳細ページを表示する
     *
     * @param  \App\Models\Product  $product
     * @return \Inertia\Response
     */
    public function show(Product $product)
    {
        // 🚀 view() から Inertia::render() に変更
        return Inertia::render('Admin/Product/Show', [
            'product' => $product,
        ]);
    }
    
    /**
     * 商品情報を更新する
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Product $product)
    {
        // バリデーション
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'description' => 'required',
            'price' => 'required|integer',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // 新しい画像がアップロードされた場合
        if ($request->hasFile('image')) {
            // 古い画像を削除
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            // 新しい画像を保存
            $imagePath = Storage::disk('public')->put('products', $request->file('image'));
        } else {
            // 画像が更新されない場合は既存のパスを維持
            $imagePath = $product->image_path;
        }

        // データベースを更新
        $product->update([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'price' => $validatedData['price'],
            'image_path' => $imagePath,
        ]);

        return redirect()->route('admin.products.index')->with('success', '商品が更新されました。');
    }

    /**
     * 商品を削除する
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Product $product)
    {
        // 画像を削除
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        // データベースから商品を削除
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', '商品が削除されました。');
    }
}
