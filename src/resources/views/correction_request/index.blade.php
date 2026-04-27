@extends('layouts.app')

@section('title', '申請一覧')

@section('header-nav')
    <div class="header__right">
        <a href="{{ route('attendance.create') }}" class="header__link">勤怠</a>
        <a href="{{ route('attendance.index') }}" class="header__link">勤怠一覧</a>
        <a href="{{ route('correction-requests.index') }}" class="header__link">申請</a>

        <form method="POST" action="/logout" class="header__logout-form">
            @csrf
            <button type="submit" class="header__logout-button">ログアウト</button>
        </form>
    </div>
@endsection

@section('content')
    <div class="correction-request-index">
        <div class="correction-request-index__inner">
            <h1 class="correction-request-index__title">申請一覧</h1>

            <div class="correction-request-index__tabs">
                <a
                    href="{{ route('correction-requests.index', ['status' => 'pending']) }}"
                    class="correction-request-index__tab {{ $status === 'pending' ? 'correction-request-index__tab--active' : '' }}"
                >
                    承認待ち
                </a>

                <a
                    href="{{ route('correction-requests.index', ['status' => 'approved']) }}"
                    class="correction-request-index__tab {{ $status === 'approved' ? 'correction-request-index__tab--active' : '' }}"
                >
                    承認済み
                </a>
            </div>

            <table class="correction-request-index__table">
                <thead>
                    <tr>
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($correctionRequests as $correctionRequest)
                        <tr>
                            <td>
                                @if ($correctionRequest->status === 0)
                                    承認待ち
                                @elseif ($correctionRequest->status === 1)
                                    承認済み
                                @endif
                            </td>

                            <td>{{ $correctionRequest->user->name }}</td>

                            <td>
                                {{ optional($correctionRequest->attendance->work_date)->format('Y/m/d') }}
                            </td>

                            <td>{{ $correctionRequest->requested_note }}</td>

                            <td>
                                {{ $correctionRequest->created_at->format('Y/m/d') }}
                            </td>

                            <td>
                                <a href="{{ route('attendance.show', $correctionRequest->attendance->id) }}">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
