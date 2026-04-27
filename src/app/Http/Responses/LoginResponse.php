<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = auth()->user();

        // 管理者なら管理者用勤怠一覧へ
        if ($user->role === 1) {
            return redirect()->route('admin.attendance.index');
        }

        // 一般ユーザーなら勤怠登録画面へ
        return redirect()->route('attendance.create');
    }
}
