<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'    => ['required'],
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return [
            'email'    => 'メールアドレス',
            'password' => 'パスワード',
        ];
    }

    public function messages(): array
    {
        return [
            'required'  => ':attributeは必須です。',
            'email'     => ':attributeは有効な形式で入力してください。',
            'confirmed' => 'パスワードが一致しません。',
        ];
    }
}
