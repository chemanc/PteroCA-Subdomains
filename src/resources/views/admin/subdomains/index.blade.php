@extends('layouts.admin')

@section('title', __('subdomains::subdomains.admin_title'))

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ __('subdomains::subdomains.admin_title') }}</h1>
            <p class="text-muted mb-0">{{ __('subdomains::subdomains.admin_description') }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.subdomains.settings') }}" class="btn btn-outline-primary">
                <i class="fas fa-cog"></i> {{ __('subdomains::subdomains.settings') }}
            </a>
            <a href="{{ route('admin.subdomains.blacklist') }}" class="btn btn-outline-warning">
                <i class="fas fa-ban"></i> {{ __('subdomains::subdomains.blacklist') }}
            </a>
            <a href="{{ route('admin.subdomains.logs') }}" class="btn btn-outline-info">
                <i class="fas fa-history"></i> {{ __('subdomains::subdomains.logs') }}
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                {{ __('subdomains::subdomains.total_subdomains') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] }}</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-globe fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                {{ __('subdomains::subdomains.active_subdomains') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active'] }}</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                {{ __('subdomains::subdomains.suspended_subdomains') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['suspended'] }}</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-pause-circle fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                {{ __('subdomains::subdomains.error_subdomains') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['error'] }}</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary Stats Row --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card shadow h-100">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                        {{ __('subdomains::subdomains.subdomains_today') }}
                    </div>
                    <div class="h4 mb-0 font-weight-bold">{{ $stats['today'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow h-100">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                        {{ __('subdomains::subdomains.subdomains_this_week') }}
                    </div>
                    <div class="h4 mb-0 font-weight-bold">{{ $stats['this_week'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow h-100">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                        {{ __('subdomains::subdomains.subdomains_this_month') }}
                    </div>
                    <div class="h4 mb-0 font-weight-bold">{{ $stats['this_month'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Recent Subdomains Table --}}
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('subdomains::subdomains.subdomains') }}</h6>
                    <div>
                        <form action="{{ route('admin.subdomains.sync') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary"
                                    onclick="return confirm('{{ __('subdomains::subdomains.sync_dns_confirm') }}')">
                                <i class="fas fa-sync"></i> {{ __('subdomains::subdomains.sync_dns') }}
                            </button>
                        </form>
                        <a href="{{ route('admin.subdomains.export') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download"></i> {{ __('subdomains::subdomains.export') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($recentSubdomains->isEmpty())
                        <p class="text-muted text-center py-4">{{ __('subdomains::subdomains.no_results') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('subdomains::subdomains.table_subdomain') }}</th>
                                        <th>{{ __('subdomains::subdomains.table_domain') }}</th>
                                        <th>{{ __('subdomains::subdomains.table_server') }}</th>
                                        <th>{{ __('subdomains::subdomains.table_user') }}</th>
                                        <th>{{ __('subdomains::subdomains.table_status') }}</th>
                                        <th>{{ __('subdomains::subdomains.table_created') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentSubdomains as $sub)
                                        <tr>
                                            <td><strong>{{ $sub->subdomain }}</strong></td>
                                            <td>{{ $sub->domain->domain ?? 'N/A' }}</td>
                                            <td>#{{ $sub->server_id }}</td>
                                            <td>{{ $sub->user->name ?? $sub->user->email ?? 'N/A' }}</td>
                                            <td>
                                                @switch($sub->status)
                                                    @case('active')
                                                        <span class="badge bg-success">{{ __('subdomains::subdomains.status_active') }}</span>
                                                        @break
                                                    @case('pending')
                                                        <span class="badge bg-warning text-dark">{{ __('subdomains::subdomains.status_pending') }}</span>
                                                        @break
                                                    @case('suspended')
                                                        <span class="badge bg-secondary">{{ __('subdomains::subdomains.status_suspended') }}</span>
                                                        @break
                                                    @case('error')
                                                        <span class="badge bg-danger" title="{{ $sub->error_message }}">{{ __('subdomains::subdomains.status_error') }}</span>
                                                        @break
                                                @endswitch
                                            </td>
                                            <td>{{ $sub->created_at->diffForHumans() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Domains Panel --}}
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('subdomains::subdomains.domains') }}</h6>
                </div>
                <div class="card-body">
                    @if($domains->isEmpty())
                        <p class="text-muted text-center">{{ __('subdomains::subdomains.no_domains') }}</p>
                        <a href="{{ route('admin.subdomains.settings') }}" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-plus"></i> {{ __('subdomains::subdomains.add_domain') }}
                        </a>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($domains as $domain)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $domain->domain }}</strong>
                                        @if($domain->is_default)
                                            <span class="badge bg-primary ms-1">{{ __('subdomains::subdomains.is_default') }}</span>
                                        @endif
                                        @if(!$domain->is_active)
                                            <span class="badge bg-secondary ms-1">{{ __('subdomains::subdomains.disabled') }}</span>
                                        @endif
                                    </div>
                                    <span class="badge bg-info rounded-pill">{{ $domain->subdomains_count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
