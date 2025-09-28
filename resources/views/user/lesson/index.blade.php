@extends('layouts.app')

@section('title', $course->title . ' - Lessons')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        @include('user.lesson.partials.course-header', [
            'course' => $course,
            'completionPercentage' => $completionPercentage,
            'exam' => $exam
        ])

        @if($groupedLessons->isNotEmpty())
            @include('user.lesson.partials.lesson-sections', [
                'groupedLessons' => $groupedLessons,
                'exam' => $exam,
                'course' => $course
            ])
        @else
            @include('user.lesson.partials.no-lessons', [
                'exam' => $exam
            ])
        @endif
    </div>
</div>
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
