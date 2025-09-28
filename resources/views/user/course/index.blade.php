@extends('user.layouts.app')

@push('styles')
    <link href="{{ asset('user/css/card.css') }}" rel="stylesheet">
    <style>
        .solution-section {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .solution-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: all 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .solution-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .solution-card:hover .solution-description,
        .solution-card:hover .solution-title,
        .solution-card:hover strong,
        .solution-card:hover ul li {
            color: white;
            /* Mengubah warna teks menjadi putih pada hover */
        }

        .solution-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 0.75rem;
        }

        .solution-description {
            font-size: 0.95rem;
            color: #6c757d;
        }

        .solution-card img {
            border-radius: 0.5rem;
            margin-top: 1rem;
            max-height: 160px;
            object-fit: cover;
            width: 100%;
        }

        .solution-card ul {
            font-size: 0.875rem;
            padding-left: 1rem;
            color: #495057;
        }

        .solution-card strong {
            color: #212529;
        }

        .badge {
            font-size: 0.75rem;
            margin-right: 0.5rem;
        }

        .meta-badges {
            margin: 0.5rem 0;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('category.index') }}">Category</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Course</li>
                </ol>
            </nav>

            <h1 class="h3 mb-0 text-gray-800">Course</h1>
            <form action="{{ route('course.index', $exam) }}" method="GET">
                <div class="form-group mb-0">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Search category ..."
                            value="{{ request('q') }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Course Cards -->
        <div class="solution-section">
            @forelse ($courses as $item)
                <div class="solution-card">
                    @if ($item->thumbnail_path)
                        <img src="{{ asset($item->thumbnail_path) }}" class="mb-2" alt="Thumbnail">
                    @endif

                    <div class="solution-title">{{ $item->title }}</div>
                    <div class="solution-description">{{ $item->description }}</div>

                    <div class="meta-badges mt-2 mb-2">
                        <span class="badge bg-primary text-white">{{ ucfirst($item->level) }}</span>
                        <span class="badge bg-info text-white">{{ $item->duration_minutes }} mins</span>
                        <span class="badge text-white {{ $item->is_published ? 'bg-success' : 'bg-danger' }}">
                            {{ $item->is_published ? 'Published' : 'Unpublished' }}
                        </span>
                        @if ($item->is_featured)
                            <span class="badge bg-warning text-dark">Featured</span>
                        @endif
                    </div>

                    @if (!empty($item->learning_objectives))
                        <div>
                            <strong>Learning Objectives:</strong>
                            <ul class="mb-2">
                                @foreach ($item->learning_objectives as $objective)
                                    <li>{{ $objective }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <button class="btn mt-auto btn-start-course" data-course-title="{{ $item->title }}"
                        style="color: #ffffff; background-color: #42c3ca; border: 1px solid #42c3ca;"
                        data-course-url="{{ route('lesson.index', [$exam, $item->slug]) }}" data-toggle="modal"
                        data-target="#startCourseModal">
                        Start Course
                    </button>
                </div>
            @empty
                <div class="text-center mt-5 w-100">
                    <h5 class="text-muted">No courses found{{ request('q') ? ' for "' . request('q') . '"' : '' }}.</h5>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if ($courses->count())
            <div class="mt-4">
                {!! $courses->links('vendor.pagination.bootstrap-5') !!}
            </div>
        @endif
    </div>

    <!-- Start Course Modal -->
    <div class="modal fade" id="startCourseModal" tabindex="-1" role="dialog" aria-labelledby="startCourseModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="startCourseModalLabel">Start Course</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to start the course <strong id="modalCourseTitle"></strong>?
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <a href="#" class="btn btn-primary" id="confirmStartCourse"
                        style="color: #ffffff; background-color: #42c3ca; border: 1px solid #42c3ca;">Yes, Start</a>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.btn-start-course').on('click', function() {
                var title = $(this).data('course-title');
                var url = $(this).data('course-url');
                $('#modalCourseTitle').text(title);
                $('#confirmStartCourse').attr('href', url);
            });
        });
    </script>
@endpush
