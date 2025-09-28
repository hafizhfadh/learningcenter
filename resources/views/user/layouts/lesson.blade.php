<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? $course->title }}</title>

    <!-- Bootstrap 4 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8fafc;
            margin: 0;
        }

        .lesson-wrapper {
            display: flex;
            flex-direction: row;
            min-height: 100vh;
        }

        .sidebar-wrapper {
            width: 320px;
            background-color: #ffffff;
            padding: 2rem 1.5rem;
            border-right: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            flex-shrink: 0;
        }

        .content-wrapper {
            flex: 1;
            padding: 2rem 3rem;
            background-color: #ffffff;
        }

        .progress-label {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .module-group-title,
        .sidebar-title {
            font-weight: 700;
            font-size: 15px;
            color: #374151;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .module-list {
            list-style: none;
            padding-left: 0;
        }

        .module-item {
            padding: 0.6rem 1rem;
            border-radius: 8px;
            background-color: #f1f5f9;
            margin-bottom: 6px;
            font-size: 14px;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }

        .module-item:hover {
            background-color: #e2e8f0;
        }

        .module-item.active {
            background-color: #1e40af;
            color: #ffffff;
        }

        pre {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
        }

        #countdown-timer {
            z-index: 1050;
            top: 0;
            left: 0;
            right: 0;
        }

        @media (max-width: 768px) {
            .lesson-wrapper {
                flex-direction: column;
            }

            .sidebar-wrapper {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
                padding: 1rem;
            }

            .content-wrapper {
                padding: 1.5rem;
            }

            .module-item {
                font-size: 13px;
                padding: 0.5rem 0.8rem;
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    {{-- Timer --}}
    <div id="countdown-timer" class="alert alert-info text-center fixed-top mb-0">
        Waktu tersisa: <span id="time">00:00</span>
    </div>

    {{-- Main Layout --}}
    <div class="lesson-wrapper mt-5 pt-2">
        {{-- Sidebar --}}
        <div class="sidebar-wrapper">
            {{-- Progress --}}
            <div class="progress mb-3" style="height: 24px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%;"
                    aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                    {{ $progress }}%
                </div>
            </div>
            <p class="mb-4 font-weight-bold">{{ $completedLessons }} dari {{ $totalLessons }} pelajaran selesai</p>

            {{-- Lessons List --}}
            @foreach ($groupedLessons as $group => $lessons)
                <div class="module-group-title">{{ $group }}</div>
                <ul class="module-list">
                    @foreach ($lessons as $l)
                        @php
                            $isCompleted =
                                isset($userProgress[$l->id]) &&
                                in_array($userProgress[$l->id]->status, ['completed', 'mastered']);
                        @endphp
                        <li
                            class="module-item d-flex align-items-center justify-content-between {{ $l->id === $lesson->id ? 'active' : '' }}">
                            <a href="{{ route('lesson.show', [$exam, $course->slug, $l->slug]) }}"
                                class="flex-grow-1 text-decoration-none {{ $l->id === $lesson->id ? 'text-white' : 'text-dark' }}">
                                {{ $l->title }}
                            </a>
                            @if ($isCompleted)
                                <i class="fas fa-check-circle text-success ml-2" title="Selesai"></i>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>


        {{-- Main Content --}}
        <div class="content-wrapper">
            @yield('content')
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
