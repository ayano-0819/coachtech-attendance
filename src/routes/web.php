<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\CorrectionRequestController;

/*
|--------------------------------------------------------------------------
| 管理者ログイン画面
|--------------------------------------------------------------------------
*/
Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->name('admin.login');

/*
|--------------------------------------------------------------------------
| ルートアクセス時 → ログイン画面へリダイレクト
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect('/login');
});

/*
|--------------------------------------------------------------------------
| 一般ユーザー用ルート（メール認証あり）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // 勤怠登録画面
    Route::get('/attendance', [AttendanceController::class, 'create'])
        ->name('attendance.create');

    // 出勤処理
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])
        ->name('attendance.clockIn');

    // 休憩入処理
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])
        ->name('attendance.breakStart');

    // 休憩戻処理
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])
        ->name('attendance.breakEnd');

    // 退勤処理
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])
        ->name('attendance.clockOut');

    // 勤怠一覧画面
    Route::get('/attendance/list', [AttendanceController::class, 'index'])
        ->name('attendance.index');

    // 勤怠詳細画面
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])
        ->name('attendance.show');

    // 勤怠修正内容保存（一般ユーザーのみ）
    Route::post('/attendance/detail/{attendance_id}/correction-request', [CorrectionRequestController::class, 'store'])
        ->name('correction-requests.store');
});

/*
|--------------------------------------------------------------------------
| ログインユーザー共通ルート（管理者も一般もOK）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // 修正申請一覧画面（一般ユーザー・管理者共通）
    Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'index'])
        ->name('correction-requests.index');

    // 修正申請承認画面表示（管理者）
    Route::get('/stamp_correction_request/approve/{correction_request_id}', [CorrectionRequestController::class, 'show'])
        ->name('correction-requests.show');
    
    // 修正申請承認更新処理（管理者）
    Route::post('/stamp_correction_request/approve/{correction_request_id}', [CorrectionRequestController::class, 'approve'])
        ->name('correction-requests.approve');

    /*
    |--------------------------------------------------------------------------
    | 管理者用ルート
    |--------------------------------------------------------------------------
    */

    // 管理者用勤怠一覧画面
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])
        ->name('admin.attendance.index');

    // 管理者：勤怠詳細（表示）
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show'])
        ->name('admin.attendance.show');

    // 管理者：勤怠詳細（更新）
    Route::post('/admin/attendance/{id}', [AdminAttendanceController::class, 'update'])
        ->name('admin.attendance.update');

    // 管理者：スタッフ一覧画面
    Route::get('/admin/staff/list', [AdminStaffController::class, 'index'])
        ->name('admin.staff.index');
    
    // 管理者：スタッフ別勤怠一覧画面
    Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendance'])
        ->name('admin.attendance.staff');

    // 管理者：スタッフ別勤怠一覧画面CSV出力
    Route::get('/admin/attendance/staff/{id}/csv', [AdminStaffController::class, 'exportCsv'])
        ->middleware('auth')
        ->name('admin.staff.csv');
});
