<!-- Lesson Sections -->
@foreach($groupedLessons as $sectionTitle => $lessons)
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-gray-800">{{ $sectionTitle }}</h2>
            <div class="text-sm text-gray-500">
                {{ $lessons->where('is_completed', true)->count() }}/{{ $lessons->count() }} completed
            </div>
        </div>
        
        <!-- Section Progress -->
        @php
            $sectionProgress = $lessons->count() > 0 ? ($lessons->where('is_completed', true)->count() / $lessons->count()) * 100 : 0;
        @endphp
        <div class="bg-gray-200 rounded-full h-2 mb-4">
            <div class="bg-green-500 h-2 rounded-full transition-all duration-300" 
                 style="width: {{ $sectionProgress }}%"></div>
        </div>
        
        <!-- Lessons List -->
        <div class="space-y-3">
            @foreach($lessons as $lesson)
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors group">
                    <div class="flex items-center flex-1">
                        <!-- Completion Status -->
                        <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3 flex-shrink-0
                            {{ $lesson->is_completed ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-600' }}">
                            @if($lesson->is_completed)
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                {{ $lesson->order_index }}
                            @endif
                        </div>
                        
                        <!-- Lesson Info -->
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-gray-900 group-hover:text-blue-600 transition-colors">
                                {{ $lesson->title }}
                            </h3>
                            <div class="flex items-center mt-1 text-sm text-gray-500 space-x-4">
                                @if($lesson->duration)
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $lesson->duration }} min
                                    </span>
                                @endif
                                
                                @if($lesson->content_type)
                                    <span class="flex items-center">
                                        @switch($lesson->content_type)
                                            @case('video')
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                                @break
                                            @case('text')
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                @break
                                            @case('quiz')
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                @break
                                            @case('interactive')
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                </svg>
                                                @break
                                        @endswitch
                                        {{ ucfirst($lesson->content_type) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Button -->
                    <a href="{{ route('lesson.show', [$exam, $course->slug, $lesson->slug]) }}" 
                       class="px-4 py-2 rounded-lg transition-colors font-medium
                           {{ $lesson->is_completed 
                               ? 'bg-green-100 text-green-700 hover:bg-green-200' 
                               : 'bg-blue-600 text-white hover:bg-blue-700' }}">
                        {{ $lesson->is_completed ? 'Review' : 'Start' }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endforeach