<!-- Course Header -->
<div class="bg-white rounded-lg shadow-lg p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $course->title }}</h1>
            @if($course->description)
                <p class="text-gray-600 mt-1">{{ Str::limit($course->description, 100) }}</p>
            @endif
        </div>
        <div class="text-right">
            <div class="text-sm text-gray-600 mb-1">
                Progress: {{ number_format($completionPercentage, 1) }}%
            </div>
            <div class="text-xs text-gray-500">
                {{ $course->lessons_count ?? $course->lessons->count() }} lessons
            </div>
        </div>
    </div>
    
    <!-- Progress Bar -->
    <div class="bg-gray-200 rounded-full h-3 mb-4">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-300" 
             style="width: {{ $completionPercentage }}%"></div>
    </div>
    
    <!-- Navigation Breadcrumb -->
    <nav class="flex items-center text-sm text-gray-500">
        <a href="{{ route('course.index', [$exam ?? '', '']) }}" 
           class="hover:text-blue-600 transition-colors">Courses</a>
        <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
        <span class="text-gray-700 font-medium">{{ $course->title }}</span>
    </nav>
</div>