@extends('user.layouts.lesson')

@section('content')
    <h2 class="mb-4">{{ $lesson->title }}</h2>
    <hr>

    {{-- Konten --}}
    @if ($lesson->content_type === 'video')
        <div class="mb-4">
            <video src="{{ $lesson->video_url }}" controls width="100%" poster="{{ $lesson->thumbnail_path }}"></video>
        </div>
    @elseif ($lesson->content_type === 'text')
        {!! \Illuminate\Support\Str::markdown($lesson->content) !!}
    @else
        <div class="alert alert-warning">Jenis konten tidak dikenali.</div>
    @endif

    {{-- Navigasi Modul --}}
    <div class="d-flex justify-content-between align-items-center mt-5 pt-4 border-top">
        @php
            $lessons = \App\Models\Lesson::where('course_id', $course->id)->orderBy('order')->get();
            $previousLesson = $lessons->where('order', $lesson->order - 1)->first();
            $nextLesson = $lessons->where('order', $lesson->order + 1)->first();
        @endphp

        @if ($previousLesson)
            <a href="{{ route('lesson.show', [$exam, $course->slug, $previousLesson->slug]) }}"
               class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Sebelumnya
            </a>
        @else
            <span></span>
        @endif

        @if ($nextLesson)
            <a href="{{ route('lesson.show', [$exam, $course->slug, $nextLesson->slug]) }}" class="btn btn-primary">
                Selanjutnya <i class="fas fa-arrow-right"></i>
            </a>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        let duration = {{ $lesson->duration_minutes * 60 ?? 0 }};
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
@endpush
