@extends('layouts.app')

@section('title', '勤怠詳細')

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
    @php
        $isPending = $pendingCorrection ?? false;
    @endphp

    <div class="attendance-detail">
        <h1>勤怠詳細</h1>

        <form method="POST" action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}">
            @csrf

            <table>
                <tr>
                    <th>名前</th>
                    <td>{{ $attendance->user->name }}</td>
                </tr>

                <tr>
                    <th>日付</th>
                    <td>{{ $attendance->work_date->format('Y年n月j日') }}</td>
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
                            <p>{{ $message }}</p>
                        @enderror

                        @error('clock_out_at')
                            <p>{{ $message }}</p>
                        @enderror
                    </td>
                </tr>

                @php
                    $breakCount = $attendance->attendanceBreaks->count();
                @endphp

                @for ($i = 0; $i <= $breakCount; $i++)
                    @php
                        $break = $attendance->attendanceBreaks[$i] ?? null;
                    @endphp

                    <tr>
                        <th>休憩{{ $i + 1 }}</th>
                        <td>
                            <input
                                type="time"
                                name="breaks[{{ $i }}][start]"
                                value="{{ old("breaks.$i.start", optional($break?->break_start_at)->format('H:i')) }}"
                                @if ($isPending) disabled @endif
                            >
                            @error("breaks.$i.start")
                                <p>{{ $message }}</p>
                            @enderror

                            〜

                            <input
                                type="time"
                                name="breaks[{{ $i }}][end]"
                                value="{{ old("breaks.$i.end", optional($break?->break_end_at)->format('H:i')) }}"
                                @if ($isPending) disabled @endif
                            >
                            @error("breaks.$i.end")
                                <p>{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>
                @endfor

                <tr>
                    <th>備考</th>
                    <td>
                        <textarea
                            name="note"
                            @if ($isPending) disabled @endif
                        >{{ old('note', $attendance->note) }}</textarea>

                        @error('note')
                            <p>{{ $message }}</p>
                        @enderror

                        @if ($isPending || session('error'))
                            <p>*承認待ちのため修正はできません。</p>
                        @endif
                    </td>
                </tr>
            </table>

            @if (!$isPending)
                <button type="submit">修正</button>
            @endif
        </form>
    </div>
@endsection

