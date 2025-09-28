@extends('user.layouts.app')

@push('styles')
    <link href="{{ asset('user/css/card.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">Category</li>
                </ol>
            </nav>
            
            <h1 class="h3 mb-0 text-gray-800">Category</h1>
            <form action="{{ route('category.index') }}" method="GET">
                <div class="form-group mb-0">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="search category ..." value="{{ request('q') }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> SEARCH
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Solution Cards -->
        <div class="solution-section">
            @if ($categories->count() > 0)
                <div class="solution-row">
                    @foreach ($categories as $item)
                        <div class="solution-card">
                            <div class="solution-title">{{ $item->name }}</div>
                            <div class="solution-description">
                                {{ $item->description }}
                            </div>
                            <a href="{{ route('course.index', $item->slug) }}" class="btn btn-primary">Read More</a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center mt-5">
                    <h5 class="text-muted">No categories found{{ request('q') ? ' for "' . request('q') . '"' : '' }}.</h5>
                </div>
            @endif
        </div>

        <!-- Pagination -->
        @if ($categories->count())
            {!! $categories->links('vendor.pagination.bootstrap-5') !!}
        @endif
    </div>
@endsection
