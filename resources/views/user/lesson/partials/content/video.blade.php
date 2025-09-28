<div class="mb-6">
    <div class="aspect-w-16 aspect-h-9 bg-gray-900 rounded-lg overflow-hidden">
        @if($lesson->video_url)
            <video controls class="w-full h-full object-cover" preload="metadata">
                <source src="{{ $lesson->video_url }}" type="video/mp4">
                <p class="text-white p-4">Your browser does not support the video tag.</p>
            </video>
        @else
            <div class="flex items-center justify-center h-full">
                <div class="text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-gray-400">Video not available</p>
                </div>
            </div>
        @endif
    </div>
    
    @if($lesson->duration)
        <div class="mt-2 text-sm text-gray-600">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Duration: {{ $lesson->duration }} minutes
        </div>
    @endif
</div>

@if($lesson->content)
    <div class="prose max-w-none mb-6">
        {!! $lesson->content !!}
    </div>
@endif