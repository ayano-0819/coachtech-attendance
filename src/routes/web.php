<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\CorrectionRequestController;

Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->name('admin.login');

Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
    ->name('admin.login.store');

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/attendance', [AttendanceController::class, 'create'])
        ->name('attendance.create');

    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])
        ->name('attendance.clockIn');

    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])
        ->name('attendance.breakStart');

    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])
        ->name('attendance.breakEnd');

    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])
        ->name('attendance.clockOut');

    Route::get('/attendance/list', [AttendanceController::class, 'index'])
        ->name('attendance.index');

    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])
        ->name('attendance.show');

    Route::post('/attendance/detail/{attendance_id}/correction-request', [CorrectionRequestController::class, 'store'])
        ->name('correction-requests.store');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'index'])
        ->name('correction-requests.index');
});

Route::middleware(['auth', 'admin'])->group(function () {

    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [CorrectionRequestController::class, 'show'])
        ->name('correction-requests.show');

    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [CorrectionRequestController::class, 'approve'])
        ->name('correction-requests.approve');

    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])
        ->name('admin.attendance.index');

    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show'])
        ->name('admin.attendance.show');

    Route::post('/admin/attendance/{id}', [AdminAttendanceController::class, 'update'])
        ->name('admin.attendance.update');

    Route::get('/admin/staff/list', [AdminStaffController::class, 'index'])
        ->name('admin.staff.index');

    Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendance'])
        ->name('admin.attendance.staff');

    Route::get('/admin/attendance/staff/{id}/csv', [AdminStaffController::class, 'exportCsv'])
        ->name('admin.staff.csv');
});
