<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        if ($request->input('redirect_to') === 'admin') {
            return redirect()->route('admin.login');
        }

        return redirect()->route('login');
    }
}
