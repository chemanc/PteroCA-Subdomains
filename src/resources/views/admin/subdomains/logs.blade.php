@extends('layouts.admin')

@section('title', __('subdomains::subdomains.logs_title'))

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ __('subdomains::subdomains.logs_title') }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.subdomains.index') }}">{{ __('subdomains::subdomains.nav_subdomains') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('subdomains::subdomains.logs') }}</li>
                </ol>
            </nav>
        </div>
        <form action="{{ route('admin.subdomains.logs.clear') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm"
                    onclick="return confirm('{{ __('subdomains::subdomains.clear_logs_confirm') }}')">
                <i class="fas fa-trash"></i> {{ __('subdomains::subdomains.clear_logs') }}
            </button>
        </form>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filters --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('admin.subdomains.logs') }}" method="GET" class="row align-items-end">
                <div class="col-md-3 mb-2">
                    <label class="form-label">{{ __('subdomains::subdomains.filter_by_action') }}</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">{{ __('subdomains::subdomains.all_actions') }}</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" @selected(request('action') === $action)>
                                {{ __('subdomains::subdomains.log_action_' . $action) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label">{{ __('subdomains::subdomains.filter_by_date') }} ({{ __('subdomains::subdomains.create') }})</label>
                    <input type="date" class="form-control form-control-sm" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label">&nbsp;</label>
                    <input type="date" class="form-control form-control-sm" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter"></i> {{ __('subdomains::subdomains.filter') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Logs Table --}}
    <div class="card shadow">
        <div class="card-body">
            @if($logs->isEmpty())
                <p class="text-muted text-center py-4">{{ __('subdomains::subdomains.no_logs') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('subdomains::subdomains.log_action') }}</th>
                                <th>{{ __('subdomains::subdomains.log_user') }}</th>
                                <th>{{ __('subdomains::subdomains.log_subdomain') }}</th>
                                <th>{{ __('subdomains::subdomains.log_details') }}</th>
                                <th>{{ __('subdomains::subdomains.log_ip') }}</th>
                                <th>{{ __('subdomains::subdomains.log_date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>
                                        <span class="badge {{ $log->action_badge_class }}">
                                            {{ $log->action_label }}
                                        </span>
                                    </td>
                                    <td>{{ $log->user->name ?? $log->user->email ?? 'System' }}</td>
                                    <td>
                                        @if($log->subdomain)
                                            <code>{{ $log->subdomain->subdomain }}.{{ $log->subdomain->domain->domain ?? '' }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->details)
                                            <button type="button" class="btn btn-sm btn-outline-info"
                                                    data-bs-toggle="popover" data-bs-trigger="click"
                                                    data-bs-html="true" data-bs-placement="left"
                                                    data-bs-content="<pre class='mb-0 small'>{{ e(json_encode($log->details, JSON_PRETTY_PRINT)) }}</pre>">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><small>{{ $log->ip_address ?? '-' }}</small></td>
                                    <td>
                                        <small title="{{ $log->created_at }}">
                                            {{ $log->created_at->diffForHumans() }}
                                        </small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>

</div>

@push('scripts')
<script>
    // Initialize Bootstrap popovers for log details
    document.addEventListener('DOMContentLoaded', function() {
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function(el) {
            return new bootstrap.Popover(el);
        });
    });
</script>
@endpush
@endsection
