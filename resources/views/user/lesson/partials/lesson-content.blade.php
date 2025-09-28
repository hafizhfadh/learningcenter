<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <!-- Lesson Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6">
        <h1 class="text-2xl font-bold mb-2">{{ $lesson->title }}</h1>
        <p class="text-blue-100">{{ $course->title }}</p>
        @if($lesson->description)
            <p class="text-blue-200 text-sm mt-2">{{ $lesson->description }}</p>
        @endif
    </div>

    <!-- Lesson Content -->
    <div class="p-6">
        @if($lesson->content_type === 'video')
            @include('user.lesson.partials.content.video', ['lesson' => $lesson])
        @elseif($lesson->content_type === 'text')
            @include('user.lesson.partials.content.text', ['lesson' => $lesson])
        @elseif($lesson->lesson_type === 'quiz')
            @include('user.lesson.partials.content.quiz', ['lesson' => $lesson])
        @elseif($lesson->lesson_type === 'interactive')
            @include('user.lesson.partials.content.interactive', ['lesson' => $lesson])
        @else
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <p class="text-yellow-700">Content type not recognized.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Lesson Navigation -->
    <div class="border-t bg-gray-50 px-6 py-4">
        <div class="flex justify-between items-center">
            @if($previousLesson)
                <a href="{{ route('lesson.show', [$exam, $course->slug, $previousLesson->slug]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Previous
                </a>
            @else
                <div></div>
            @endif

            @if($nextLesson)
                <form action="{{ route('lesson.next', [$exam, $course->slug, $lesson->slug]) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Complete & Next
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </form>
            @else
                <form action="{{ route('lesson.next', [$exam, $course->slug, $lesson->slug]) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Complete Course
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>