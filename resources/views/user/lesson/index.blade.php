@extends('user.layouts.lesson')

@section('content')
    @if ($lesson)
        <h2 class="mb-4 font-weight-bold">{{ $lesson->title }}</h2>
        <hr>

        {{-- Konten lesson --}}
        @if ($lesson->content_type === 'video')
            <div class="embed-responsive embed-responsive-16by9 mb-4">
                <video controls class="embed-responsive-item" preload="metadata">
                    <source src="{{ $lesson->video_url }}" type="video/mp4">
                    Browser Anda tidak mendukung video.
                </video>
            </div>
        @elseif ($lesson->content_type === 'text')
            <div class="lesson-text-content mb-4">
                {!! \Illuminate\Support\Str::markdown($lesson->content, ['html_input' => 'allow']) !!}
            </div>
        @elseif ($lesson->content_type === 'quiz')
            @php $quiz = json_decode($lesson->content, true); @endphp
            <h4 class="mb-3">Quiz</h4>
            @foreach ($quiz['questions'] as $index => $q)
                <div class="card mb-3 p-3 shadow-sm">
                    <strong>{{ $index + 1 }}. {{ $q['question'] }}</strong>
                    <ul class="mt-2">
                        @foreach ($q['options'] as $option)
                            <li>{{ $option }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        @elseif ($lesson->content_type === 'interactive')
            @php $interactive = json_decode($lesson->content, true); @endphp
            <h4 class="mb-3">Exercises</h4>
            <ul class="list-group mb-4">
                @foreach ($interactive['exercises'] as $exercise)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $exercise['title'] }}</strong><br>
                            <small>{{ $exercise['description'] }}</small>
                        </div>
                        <span class="badge badge-primary badge-pill">{{ ucfirst($exercise['difficulty']) }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="alert alert-warning">Jenis konten tidak dikenali.</div>
        @endif

        {{-- Navigasi --}}
        <div class="d-flex justify-content-between align-items-center mt-5 pt-4 border-top">
            <span></span>

            @if ($nextLesson)
                <a href="{{ route('lesson.show', [$exam, $course->slug, $nextLesson->slug]) }}" class="btn btn-primary">
                    Selanjutnya <i class="fas fa-arrow-right ml-1"></i>
                </a>
            @endif
        </div>
    @else
        <div class="alert alert-info text-center py-5">
            <h4>Pelajaran belum tersedia</h4>
            <p>Pelajaran untuk course ini belum ditemukan. Silakan coba lagi nanti atau hubungi administrator.</p>
        </div>
    @endif
@endsection

@push('scripts')
    @if ($lesson)
        <script>
            let duration = {{ $lesson->duration_minutes * 60 }}; // detik
            const display = document.getElementById('time');

            const countdown = setInterval(() => {
                const minutes = Math.floor(duration / 60);
                const seconds = duration % 60;

                display.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                if (--duration < 0) {
                    clearInterval(countdown);
                    alert("Waktu belajar habis!");
                }
            }, 1000);
        </script>
    @endif
@endpush
