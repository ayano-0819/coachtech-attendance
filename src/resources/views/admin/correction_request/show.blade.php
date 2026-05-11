@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/correction_request/show.css') }}">
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
    <div class="correction-request-show">
        <div class="correction-request-show__inner">
            <h1 class="correction-request-show__title">勤怠詳細</h1>

            <table class="correction-request-show__table">
                <tr>
                    <th>名前</th>
                    <td class="correction-request-show__name">
                        {{ $correctionRequest->user->name }}
                    </td>
                </tr>

                <tr>
                    <th>日付</th>
                    <td>
                        <div class="correction-request-show__date">
                            <span>{{ $correctionRequest->attendance->work_date->format('Y年') }}</span>
                            <span>{{ $correctionRequest->attendance->work_date->format('n月j日') }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        <div class="correction-request-show__time-text">
                            <span>{{ $correctionRequest->requested_clock_in_at->format('H:i') }}</span>
                            <span>〜</span>
                            <span>{{ $correctionRequest->requested_clock_out_at->format('H:i') }}</span>
                        </div>
                    </td>
                </tr>

                @foreach ($correctionRequest->correctionRequestBreaks as $index => $break)
                    <tr>
                        <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                        <td>
                            <div class="correction-request-show__time-text">
                                <span>{{ $break->requested_break_start_at->format('H:i') }}</span>
                                <span>〜</span>
                                <span>{{ $break->requested_break_end_at->format('H:i') }}</span>
                            </div>
                        </td>
                    </tr>
                @endforeach

                <tr>
                    <th>備考</th>
                    <td class="correction-request-show__note">
                        {{ $correctionRequest->requested_note }}
                    </td>
                </tr>
            </table>

            <div class="correction-request-show__button-wrap">
                @if ($correctionRequest->status === 0)
                    <form method="POST" action="{{ route('correction-requests.approve', $correctionRequest->id) }}">
                        @csrf
                        <button type="submit" class="correction-request-show__submit-button">
                            承認
                        </button>
                    </form>
                @else
                    <button type="button" class="correction-request-show__submit-button correction-request-show__submit-button--disabled" disabled>
                        承認済み
                    </button>
                @endif
            </div>
        </div>
    </div>
@endsection
