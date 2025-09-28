<div class="mt-6 bg-white rounded-lg shadow p-4">
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700">Lesson Duration</span>
        <span id="lesson-timer" class="text-lg font-bold text-blue-600">{{ $lesson->duration }} min</span>
    </div>
    <div class="bg-gray-200 rounded-full h-2">
        <div id="timer-progress" class="bg-blue-600 h-2 rounded-full transition-all duration-1000" style="width: 0%"></div>
    </div>
    <div class="mt-2 text-xs text-gray-500 text-center">
        <span id="timer-status">Timer will start when you begin the lesson</span>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const duration = {{ $lesson->duration ?? 0 }}; // in minutes
    const totalSeconds = duration * 60;
    let currentSeconds = 0;
    let timerInterval;
    
    const timerElement = document.getElementById('lesson-timer');
    const progressElement = document.getElementById('timer-progress');
    const statusElement = document.getElementById('timer-status');
    
    function startTimer() {
        if (timerInterval) return; // Already started
        
        statusElement.textContent = 'Timer started';
        
        timerInterval = setInterval(function() {
            currentSeconds++;
            
            const remainingSeconds = Math.max(0, totalSeconds - currentSeconds);
            const remainingMinutes = Math.floor(remainingSeconds / 60);
            const remainingSecondsDisplay = remainingSeconds % 60;
            
            // Update timer display
            timerElement.textContent = `${remainingMinutes}:${remainingSecondsDisplay.toString().padStart(2, '0')}`;
            
            // Update progress bar
            const progressPercentage = Math.min(100, (currentSeconds / totalSeconds) * 100);
            progressElement.style.width = progressPercentage + '%';
            
            // Update status
            if (remainingSeconds === 0) {
                clearInterval(timerInterval);
                statusElement.textContent = 'Time completed!';
                timerElement.classList.add('text-green-600');
                progressElement.classList.remove('bg-blue-600');
                progressElement.classList.add('bg-green-600');
            }
        }, 1000);
    }
    
    // Start timer when user interacts with the lesson content
    const lessonContent = document.querySelector('.lesson-content, video, .prose');
    if (lessonContent) {
        lessonContent.addEventListener('click', startTimer, { once: true });
        
        // For video content, start timer when video plays
        const video = document.querySelector('video');
        if (video) {
            video.addEventListener('play', startTimer, { once: true });
        }
    }
    
    // Auto-start timer after 5 seconds if no interaction
    setTimeout(function() {
        if (!timerInterval) {
            startTimer();
        }
    }, 5000);
});
</script>
@endpush