<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'       => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'service_id' => ['required', 'exists:services,id'],
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255'],
            'phone'      => ['required', 'string', 'max:20'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'           => '日付を選択してください。',
            'date.date_format'        => '日付の形式が正しくありません。',
            'date.after_or_equal'     => '本日以降の日付を選択してください。',

            'start_time.required'     => '時間を選択してください。',
            'start_time.date_format'  => '時間の形式が正しくありません。',

            'service_id.required'     => 'メニューを選択してください。',
            'service_id.exists'       => '選択されたメニューは存在しません。',

            'name.required'           => 'お名前を入力してください。',
            'name.max'                => 'お名前は255文字以内で入力してください。',

            'email.required'          => 'メールアドレスを入力してください。',
            'email.email'             => '正しい形式のメールアドレスを入力してください。',
            'email.max'               => 'メールアドレスは255文字以内で入力してください。',

            'phone.required'          => '電話番号を入力してください。',
            'phone.max'               => '電話番号は20文字以内で入力してください。',

            'notes.max'               => '備考は1000文字以内で入力してください。',
        ];
    }
}
