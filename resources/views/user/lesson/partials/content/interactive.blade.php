@php
    $interactive = null;
    if ($lesson->content) {
        $interactive = json_decode($lesson->content, true);
    } elseif (isset($lesson->interactive_data)) {
        $interactive = is_string($lesson->interactive_data) ? json_decode($lesson->interactive_data, true) : $lesson->interactive_data;
    }
@endphp

<div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
    <div class="flex items-center mb-4">
        <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
        <h3 class="text-lg font-semibold text-green-800">Interactive Exercise</h3>
    </div>
    
    @if($interactive && isset($interactive['exercises']))
        <p class="text-green-700 mb-4">Complete the interactive exercises to proceed.</p>
        
        <div class="space-y-4">
            @foreach($interactive['exercises'] as $index => $exercise)
                <div class="bg-white border border-green-200 rounded-lg p-4">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-medium text-gray-900">{{ $exercise['title'] ?? 'Exercise ' . ($index + 1) }}</h4>
                        @if(isset($exercise['difficulty']))
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                {{ $exercise['difficulty'] === 'easy' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $exercise['difficulty'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $exercise['difficulty'] === 'hard' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ ucfirst($exercise['difficulty']) }}
                            </span>
                        @endif
                    </div>
                    
                    @if(isset($exercise['description']))
                        <p class="text-gray-600 text-sm mb-3">{{ $exercise['description'] }}</p>
                    @endif
                    
                    @if(isset($exercise['type']))
                        <div class="text-xs text-gray-500 mb-2">Type: {{ ucfirst($exercise['type']) }}</div>
                    @endif
                    
                    <button type="button" class="bg-green-600 text-white px-3 py-1 text-sm rounded hover:bg-green-700 transition-colors">
                        Start Exercise
                    </button>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-green-700">Interactive content is not properly configured.</p>
    @endif
</div>