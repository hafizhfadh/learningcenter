@php
    $quiz = null;
    if ($lesson->content) {
        $quiz = json_decode($lesson->content, true);
    } elseif (isset($lesson->quiz_data)) {
        $quiz = is_string($lesson->quiz_data) ? json_decode($lesson->quiz_data, true) : $lesson->quiz_data;
    }
@endphp

<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
    <div class="flex items-center mb-4">
        <svg class="w-6 h-6 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h3 class="text-lg font-semibold text-yellow-800">Quiz</h3>
    </div>
    
    @if($quiz && isset($quiz['questions']))
        <p class="text-yellow-700 mb-4">Complete the quiz to proceed to the next lesson.</p>
        
        <div class="space-y-4">
            @foreach($quiz['questions'] as $index => $question)
                <div class="bg-white border border-yellow-200 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3">
                        {{ $index + 1 }}. {{ $question['question'] }}
                    </h4>
                    
                    @if(isset($question['options']))
                        <div class="space-y-2">
                            @foreach($question['options'] as $optionIndex => $option)
                                <label class="flex items-center">
                                    <input type="radio" name="question_{{ $index }}" value="{{ $optionIndex }}" 
                                           class="mr-2 text-yellow-600 focus:ring-yellow-500">
                                    <span class="text-gray-700">{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        
        <div class="mt-6">
            <button type="button" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                Submit Quiz
            </button>
        </div>
    @else
        <p class="text-yellow-700">Quiz content is not properly configured.</p>
    @endif
</div>