<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        // 管理者画面のログアウトフォームから送られた場合
        if ($request->input('redirect_to') === 'admin') {
            return redirect('/admin/login');
        }

        // それ以外は一般ユーザーのログイン画面へ
        return redirect('/login');
    }
}
