@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@section('header-nav')
    <div class="header__right">
        <a href="{{ route('admin.attendance.index') }}" class="header__link">勤怠一覧</a>
        <a href="{{ route('admin.staff.index') }}" class="header__link">スタッフ一覧</a>
        <a href="{{ route('correction-requests.index') }}" class="header__link">申請一覧</a>

        <form method="POST" action="/logout" class="header__logout-form">
            @csrf
            <input type="hidden" name="redirect_to" value="admin">
            <button type="submit" class="header__logout-button">ログアウト</button>
        </form>
    </div>
@endsection

@section('content')
    <div class="admin-attendance-staff">
        <div class="admin-attendance-staff__inner">
            <h1 class="admin-attendance-staff__title">
                {{ $user->name }}さんの勤怠
            </h1>

            <div class="month-nav">
    <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $prevMonth]) }}">
        ← 前月
    </a>

    <span>
        {{ $currentMonth->format('Y/m') }}
    </span>

    <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $nextMonth]) }}">
        翌月 →
    </a>
</div>

            <table>
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>

                <tbody>
    @foreach ($attendances as $attendance)
        <tr>
            <td>{{ $attendance->work_date->isoFormat('MM/DD(ddd)') }}</td>
            <td>{{ optional($attendance->clock_in_at)->format('H:i') }}</td>
            <td>{{ optional($attendance->clock_out_at)->format('H:i') }}</td>
            <td>{{ $attendance->break_total }}</td>
            <td>{{ $attendance->work_total }}</td>
            <td>
                @if ($attendance->id)
                    <a href="{{ route('admin.attendance.show', ['id' => $attendance->id]) }}">
                        詳細
                    </a>
                @endif
            </td>
        </tr>
    @endforeach
</tbody>
            </table>
        </div>
    </div>
@endsection
