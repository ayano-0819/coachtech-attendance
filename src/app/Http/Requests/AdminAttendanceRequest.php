<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminAttendanceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'clock_in_at' => ['required', 'date_format:H:i'],
            'clock_out_at' => ['required', 'date_format:H:i', 'after:clock_in_at'],
            'note' => ['required', 'string'],

            'breaks' => ['nullable', 'array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages()
    {
        return [
            'clock_in_at.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_in_at.date_format' => '出勤時間もしくは退勤時間が不適切な値です',

            'clock_out_at.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out_at.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out_at.after' => '出勤時間もしくは退勤時間が不適切な値です',

            'breaks.*.start.date_format' => '休憩時間が不適切な値です',
            'breaks.*.end.date_format' => '休憩時間もしくは退勤時間が不適切な値です',

            'note.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
{
    // バリデーション完了後に追加チェックを行う
    $validator->after(function ($validator) {

        // 出勤時間を取得（文字列のままでOK）
        $clockIn = $this->input('clock_in_at');

        // 退勤時間を取得（文字列のままでOK）
        $clockOut = $this->input('clock_out_at');

        // 休憩配列を取得（未入力なら空配列）
        $breaks = $this->input('breaks', []);

        // 休憩ごとにループ（breaks[0], breaks[1]...）
        foreach ($breaks as $index => $break) {

            // 各休憩の開始・終了を取得
            $breakStart = $break['start'] ?? null;
            $breakEnd = $break['end'] ?? null;

            // =========================
            // ① 両方空ならスルー
            // =========================
            // 例：空行（休憩3など）
            if (empty($breakStart) && empty($breakEnd)) {
                continue;
            }

            // =========================
            // ② エラーが出たらそれ以上チェックしないためのフラグ
            // =========================
            $hasError = false;

            // =========================
            // ③ 休憩開始が空（終了だけ入っている）
            // =========================
            if (empty($breakStart)) {
                $validator->errors()->add(
                    "breaks.$index.start",
                    '休憩時間が不適切な値です'
                );
                $hasError = true;
            }

            // =========================
            // ④ 休憩終了が空（開始だけ入っている）
            // =========================
            if (empty($breakEnd)) {
                $validator->errors()->add(
                    "breaks.$index.end",
                    '休憩時間もしくは退勤時間が不適切な値です'
                );
                $hasError = true;
            }

            // =========================
            // ⑤ 上記でエラーが出ていたら以降のチェックはしない
            // =========================
            if ($hasError) {
                continue;
            }

            // =========================
            // ⑥ 出勤時間との関係チェック
            // =========================
            if (!empty($clockIn)) {
                // 休憩開始 < 出勤 → NG
                if ($breakStart < $clockIn) {
                    $validator->errors()->add(
                        "breaks.$index.start",
                        '休憩時間が不適切な値です'
                    );
                    continue;
                }
            }

            // =========================
            // ⑦ 退勤時間との関係チェック
            // =========================
            if (!empty($clockOut)) {
                // 休憩開始 >= 退勤 → NG
                if ($breakStart >= $clockOut) {
                    $validator->errors()->add(
                        "breaks.$index.start",
                        '休憩時間が不適切な値です'
                    );
                    continue;
                }

                // 休憩終了 >= 退勤 → NG
                if ($breakEnd >= $clockOut) {
                    $validator->errors()->add(
                        "breaks.$index.end",
                        '休憩時間もしくは退勤時間が不適切な値です'
                        );
                        continue;
                    }
                }

                // =========================
                // ⑧ 休憩終了 <= 休憩開始 → NG
                // =========================
                if ($breakEnd <= $breakStart) {
                    $validator->errors()->add(
                        "breaks.$index.end",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }
            }
        });
    }
}
