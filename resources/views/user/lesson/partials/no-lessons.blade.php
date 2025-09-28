<div class="bg-white rounded-lg shadow-lg p-8 text-center">
    <div class="text-gray-400 mb-4">
        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.832 18.477 19.246 18 17.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
        </svg>
    </div>
    <h3 class="text-xl font-semibold text-gray-700 mb-2">No Lessons Available</h3>
    <p class="text-gray-500 mb-4">This course doesn't have any published lessons yet.</p>
    
    <div class="space-y-2 text-sm text-gray-400">
        <p>• Lessons may be added by the instructor soon</p>
        <p>• Check back later for updates</p>
        <p>• Contact support if you believe this is an error</p>
    </div>
    
    <div class="mt-6">
        <a href="{{ route('course.index', [$exam ?? '', '']) }}" 
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Courses
        </a>
    </div>
</div>