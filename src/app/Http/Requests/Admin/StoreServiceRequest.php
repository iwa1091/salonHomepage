<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255', 'unique:services,name'],
            'description'      => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'price'            => ['required', 'integer', 'min:0'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
            'is_active'        => ['required'],
            'is_popular'       => ['nullable'],
            'category_id'      => ['nullable', 'exists:categories,id'],
            'image'            => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:1024'],
            'features'         => ['nullable', 'array'],
            'features.*'       => ['string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'             => 'サービス名',
            'description'      => '説明',
            'duration_minutes' => '所要時間',
            'price'            => '価格',
            'sort_order'       => '表示順序',
            'is_active'        => '公開設定',
            'category_id'      => 'カテゴリ',
            'image'            => '画像',
            'features'         => '特徴',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'サービス名は必須です。',
            'name.max'                  => 'サービス名は255文字以内で入力してください。',
            'name.unique'               => 'このサービス名は既に使用されています。',
            'duration_minutes.required' => '所要時間は必須です。',
            'duration_minutes.integer'  => '所要時間は整数で入力してください。',
            'duration_minutes.min'      => '所要時間は1分以上で入力してください。',
            'duration_minutes.max'      => '所要時間は480分以内で入力してください。',
            'price.required'            => '価格は必須です。',
            'price.integer'             => '価格は整数で入力してください。',
            'price.min'                 => '価格は0以上で入力してください。',
            'sort_order.integer'        => '表示順序は整数で入力してください。',
            'sort_order.min'            => '表示順序は0以上で入力してください。',
            'category_id.exists'        => '選択したカテゴリは存在しません。',
            'image.image'               => '画像ファイルを選択してください。',
            'image.mimes'               => '画像はjpeg・png・jpg・gif・webp形式のみ対応しています。',
            'image.max'                 => '画像は1MB以内のファイルを選択してください。',
            'features.*.max'            => '各特徴は255文字以内で入力してください。',
        ];
    }
}
