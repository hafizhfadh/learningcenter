<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Quick Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <x-heroicon-o-book-open class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">My Courses</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_courses'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <x-heroicon-o-users class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Students</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_students'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <x-heroicon-o-clock class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Submissions</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['pending_submissions'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <x-heroicon-o-academic-cap class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Enrollments</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['active_enrollments'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @can('grade_submissions')
                    <div class="flex items-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <x-heroicon-o-clipboard-document-check class="w-8 h-8 text-red-600 dark:text-red-400" />
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-900 dark:text-red-100">Grade Submissions</p>
                            <p class="text-xs text-red-600 dark:text-red-400">{{ $stats['pending_submissions'] }} pending</p>
                        </div>
                    </div>
                    @endcan

                    @can('monitor_student_progress')
                    <div class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <x-heroicon-o-chart-bar class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-900 dark:text-blue-100">Student Progress</p>
                            <p class="text-xs text-blue-600 dark:text-blue-400">Track learning progress</p>
                        </div>
                    </div>
                    @endcan

                    @can('ViewAny:Task')
                    <div class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <x-heroicon-o-clipboard-document-list class="w-8 h-8 text-green-600 dark:text-green-400" />
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-900 dark:text-green-100">Manage Tasks</p>
                            <p class="text-xs text-green-600 dark:text-green-400">Create and edit tasks</p>
                        </div>
                    </div>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- My Courses -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">My Courses</h3>
                </div>
                <div class="p-6">
                    @if($courses->count() > 0)
                        <div class="space-y-4">
                            @foreach($courses as $course)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $course->title }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $course->enrollments_count }} students • {{ $course->lessons_count }} lessons
                                    </p>
                                </div>
                                <div class="text-blue-600 dark:text-blue-400">
                                    <x-heroicon-o-arrow-right class="w-5 h-5" />
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <x-heroicon-o-book-open class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                            <p class="text-gray-500 dark:text-gray-400">No courses assigned yet.</p>
                            @can('Create:Course')
                            <div class="mt-2 inline-flex items-center text-blue-600 dark:text-blue-400">
                                <x-heroicon-o-plus class="w-4 h-4 mr-1" />
                                Create your first course
                            </div>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>

            <!-- Pending Submissions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pending Submissions</h3>
                </div>
                <div class="p-6">
                    @if($pendingSubmissions->count() > 0)
                        <div class="space-y-4">
                            @foreach($pendingSubmissions->take(5) as $submission)
                            <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $submission->student->name }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $submission->task->lesson->course->title }} - {{ $submission->task->title }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500">
                                        Submitted {{ $submission->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="text-yellow-600 dark:text-yellow-400">
                                    <x-heroicon-o-eye class="w-5 h-5" />
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @if($pendingSubmissions->count() > 5)
                        <div class="mt-4 text-center">
                            <div class="text-blue-600 dark:text-blue-400 text-sm">
                                View all {{ $pendingSubmissions->count() }} pending submissions
                            </div>
                        </div>
                        @endif
                    @else
                        <div class="text-center py-8">
                            <x-heroicon-o-clipboard-document-check class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                            <p class="text-gray-500 dark:text-gray-400">No pending submissions.</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                Submissions will appear here when students complete tasks
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Progress -->
        @if($recentProgress->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Student Progress</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($recentProgress->take(5) as $progress)
                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $progress->user->name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $progress->enrollment->course->title }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">
                                Progress: {{ $progress->completion_percentage }}% • {{ $progress->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="ml-4">
                            <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $progress->completion_percentage }}%"></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Teaching Tips -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6">
            <div class="flex items-start">
                <x-heroicon-o-light-bulb class="w-6 h-6 text-blue-600 dark:text-blue-400 mt-1 mr-3 flex-shrink-0" />
                <div>
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Teaching Tips</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <div>
                            <p class="font-medium text-gray-800 dark:text-gray-200">📚 Course Management</p>
                            <p>Keep your course content organized with clear lesson structures and regular updates.</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-gray-200">⏰ Timely Feedback</p>
                            <p>Grade submissions promptly to keep students engaged and motivated.</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-gray-200">📊 Track Progress</p>
                            <p>Monitor student progress regularly to identify those who need additional support.</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-gray-200">💬 Communication</p>
                            <p>Maintain open communication channels with your students for better learning outcomes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>