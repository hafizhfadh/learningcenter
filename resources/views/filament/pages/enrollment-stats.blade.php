<div class="space-y-4">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Total Enrollments -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <x-heroicon-o-users class="h-6 w-6 text-blue-500" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Total Enrollments
                    </p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ \App\Models\Enrollment::count() }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Active Enrollments -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <x-heroicon-o-check-circle class="h-6 w-6 text-green-500" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Active
                    </p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ \App\Models\Enrollment::where('enrollment_status', 'enrolled')->count() }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Completed Enrollments -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <x-heroicon-o-star class="h-6 w-6 text-purple-500" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Completed
                    </p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ \App\Models\Enrollment::where('enrollment_status', 'completed')->count() }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Dropped Enrollments -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-500" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Dropped
                    </p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ \App\Models\Enrollment::where('enrollment_status', 'dropped')->count() }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Distribution -->
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Progress Distribution</h3>
        <div class="space-y-2">
            @php
                $progressRanges = [
                    '0-25%' => \App\Models\Enrollment::whereBetween('progress', [0, 25])->count(),
                    '26-50%' => \App\Models\Enrollment::whereBetween('progress', [26, 50])->count(),
                    '51-75%' => \App\Models\Enrollment::whereBetween('progress', [51, 75])->count(),
                    '76-100%' => \App\Models\Enrollment::whereBetween('progress', [76, 100])->count(),
                ];
                $total = array_sum($progressRanges);
            @endphp

            @foreach($progressRanges as $range => $count)
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $range }}</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-primary-500 h-2 rounded-full" style="width: {{ $total > 0 ? ($count / $total) * 100 : 0 }}%"></div>
                        </div>
                        <span class="text-sm text-gray-600 dark:text-gray-400 w-8">{{ $count }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Recent Enrollments</h3>
        <div class="space-y-3">
            @php
                $recentEnrollments = \App\Models\Enrollment::with(['user', 'course'])
                    ->latest('enrolled_at')
                    ->take(5)
                    ->get();
            @endphp

            @forelse($recentEnrollments as $enrollment)
                <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ substr($enrollment->user->name ?? 'U', 0, 1) }}
                                </span>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $enrollment->user->name ?? 'Unknown User' }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                {{ $enrollment->course->title ?? 'Unknown Course' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($enrollment->enrollment_status === 'enrolled') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                            @elseif($enrollment->enrollment_status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                            @elseif($enrollment->enrollment_status === 'dropped') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                            @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                            @endif">
                            {{ ucfirst($enrollment->enrollment_status) }}
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $enrollment->progress }}%
                        </span>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                    No recent enrollments found.
                </p>
            @endforelse
        </div>
    </div>

    <!-- Status Breakdown -->
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Status Breakdown</h3>
        <div class="grid grid-cols-3 gap-3">
            @php
                $statusCounts = \App\Models\Enrollment::selectRaw('enrollment_status, COUNT(*) as count')
                    ->groupBy('enrollment_status')
                    ->pluck('count', 'enrollment_status')
                    ->toArray();
                $totalEnrollments = array_sum($statusCounts);
            @endphp

            @foreach(['enrolled' => 'primary', 'completed' => 'success', 'dropped' => 'danger'] as $status => $color)
                @php
                    $count = $statusCounts[$status] ?? 0;
                    $percentage = $totalEnrollments > 0 ? round(($count / $totalEnrollments) * 100, 1) : 0;
                @endphp
                <div class="text-center">
                    <div class="text-xl font-bold text-{{ $color }}-600 dark:text-{{ $color }}-400">{{ $count }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ ucfirst($status) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-500">{{ $percentage }}%</div>
                </div>
            @endforeach
        </div>
    </div>
</div>