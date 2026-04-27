<?php

namespace App\Http\Controllers;

use App\Models\User;

class AdminStaffController extends Controller
{
    /**
     * スタッフ一覧表示
     */
    public function index()
    {
        $staffs = User::where('role', 0)
            ->orderBy('id')
            ->get();

        return view('admin.staff.index', compact('staffs'));
    }
}
