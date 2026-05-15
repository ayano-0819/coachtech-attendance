@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance/show.css') }}">
@endsection

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
    <div class="admin-attendance-show">
        <div class="admin-attendance-show__inner">
            <h1 class="admin-attendance-show__title">勤怠詳細</h1>

            <form method="POST" action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}">
                @csrf

                <table class="admin-attendance-show__table">
                    <tr>
                        <th>名前</th>
                        <td class="admin-attendance-show__name">
                            {{ $attendance->user->name }}
                        </td>
                    </tr>

                    <tr>
                        <th>日付</th>
                        <td>
                            <div class="admin-attendance-show__date">
                                <span>{{ $attendance->work_date->format('Y年') }}</span>
                                <span>{{ $attendance->work_date->format('n月j日') }}</span>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>出勤・退勤</th>
                        <td>
                            <input
                                type="time"
                                name="clock_in_at"
                                value="{{ old('clock_in_at', optional($attendance->clock_in_at)->format('H:i')) }}"
                                @if ($isPending) disabled @endif
                            >

                            〜

                            <input
                                type="time"
                                name="clock_out_at"
                                value="{{ old('clock_out_at', optional($attendance->clock_out_at)->format('H:i')) }}"
                                @if ($isPending) disabled @endif
                            >

                            @error('clock_in_at')
                                <p class="admin-attendance-show__error">{{ $message }}</p>
                            @enderror

                            @error('clock_out_at')
                                <p class="admin-attendance-show__error">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>

                    @foreach ($displayBreaks as $index => $break)
                        <tr>
                            <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                            <td>
                                <input
                                    type="time"
                                    name="breaks[{{ $index }}][start]"
                                    value="{{ old("breaks.$index.start", optional($break?->break_start_at)->format('H:i')) }}"
                                    @if ($isPending) disabled @endif
                                >

                                〜

                                <input
                                    type="time"
                                    name="breaks[{{ $index }}][end]"
                                    value="{{ old("breaks.$index.end", optional($break?->break_end_at)->format('H:i')) }}"
                                    @if ($isPending) disabled @endif
                                >

                                @error("breaks.$index.start")
                                    <p class="admin-attendance-show__error">{{ $message }}</p>
                                @enderror

                                @error("breaks.$index.end")
                                    <p class="admin-attendance-show__error">{{ $message }}</p>
                                @enderror
                            </td>
                        </tr>
                    @endforeach

                    <tr>
                        <th>備考</th>
                        <td>
                            <textarea
                                name="note"
                                rows="4"
                                @if ($isPending) disabled @endif
                            >{{ old('note', $attendance->note) }}</textarea>

                            @error('note')
                                <p class="admin-attendance-show__error">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>
                </table>

                <div class="admin-attendance-show__button-wrap">
                    @if ($isPending)
                        <p class="admin-attendance-show__pending-message">
                            *承認待ちのため修正はできません。
                        </p>
                    @elseif (session('pendingCorrectionError'))
                        <p class="admin-attendance-show__pending-message">
                            *{{ session('pendingCorrectionError') }}
                        </p>
                    @else
                        <button type="submit" class="admin-attendance-show__submit-button">
                            修正
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection
