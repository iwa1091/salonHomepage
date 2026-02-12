<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * このリクエストを実行できるか判定
     * 管理者認証済みなら true を返す
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'stock'       => ['required', 'integer', 'min:0'],
            'image'       => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:1024'],
        ];
    }

    /**
     * エラーメッセージ内の :attribute を日本語に置き換える
     */
    public function attributes(): array
    {
        return [
            'name'        => '商品名',
            'description' => '商品説明',
            'price'       => '価格',
            'stock'       => '在庫数',
            'image'       => '商品画像',
        ];
    }

    /**
     * 日本語バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'name.required'        => '商品名は必須です。',
            'name.string'          => '商品名は文字列で入力してください。',
            'name.max'             => '商品名は255文字以内で入力してください。',

            'description.required' => '商品説明は必須です。',
            'description.string'   => '商品説明は文字列で入力してください。',

            'price.required'       => '価格は必須です。',
            'price.numeric'        => '価格は数値で入力してください。',
            'price.min'            => '価格は0以上で入力してください。',

            'stock.required'       => '在庫数は必須です。',
            'stock.integer'        => '在庫数は整数で入力してください。',
            'stock.min'            => '在庫数は0以上で入力してください。',

            'image.required'       => '商品画像は必須です。',
            'image.image'          => '商品画像には画像ファイルを選択してください。',
            'image.mimes'          => '商品画像はjpeg・png・jpg・gif・webp形式のみ対応しています。',
            'image.max'            => '商品画像は1MB以内のファイルを選択してください。',
        ];
    }
}
