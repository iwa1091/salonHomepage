<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product; // Productモデルを忘れずにインポート
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
    /**
     * 一般ユーザー向け商品一覧ページを表示する
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // データベースからすべての商品を取得し、ページネーションを適用
        $products = Product::paginate(12); // 1ページに表示する件数を指定（ここでは12件）

        // online-store.indexビューにデータを渡して表示
        return view('online-store.index', compact('products'));
    }

    /**
     * 一般ユーザー向け商品詳細ページを表示する
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\View\View
     */
    public function show(Product $product)
    {
        // online-store.showビューにデータを渡して表示
        return view('online-store.show', compact('product'));
    }
}