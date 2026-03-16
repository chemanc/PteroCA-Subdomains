@extends('layouts.admin')

@section('title', __('subdomains::subdomains.blacklist_title'))

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ __('subdomains::subdomains.blacklist_title') }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.subdomains.index') }}">{{ __('subdomains::subdomains.nav_subdomains') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('subdomains::subdomains.blacklist') }}</li>
                </ol>
            </nav>
        </div>
        <span class="badge bg-secondary fs-6">{{ __('subdomains::subdomains.blacklist_count', ['count' => $totalCount]) }}</span>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- Add to Blacklist + Bulk Actions --}}
        <div class="col-lg-4 mb-4">
            {{-- Add Word --}}
            <div class="card shadow mb-3">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('subdomains::subdomains.add_to_blacklist') }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.subdomains.blacklist.add') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="word" class="form-label">{{ __('subdomains::subdomains.blacklist_word') }}</label>
                            <input type="text" class="form-control" id="word" name="word"
                                   placeholder="{{ __('subdomains::subdomains.blacklist_word_placeholder') }}"
                                   required maxlength="63">
                            @error('word')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">{{ __('subdomains::subdomains.blacklist_reason') }}</label>
                            <input type="text" class="form-control" id="reason" name="reason"
                                   placeholder="{{ __('subdomains::subdomains.blacklist_reason_placeholder') }}"
                                   maxlength="255">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus"></i> {{ __('subdomains::subdomains.add_to_blacklist') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Bulk Actions --}}
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('subdomains::subdomains.bulk_operations') }}</h6>
                </div>
                <div class="card-body">
                    {{-- Import --}}
                    <form action="{{ route('admin.subdomains.blacklist.import') }}" method="POST" enctype="multipart/form-data" class="mb-3">
                        @csrf
                        <label class="form-label">{{ __('subdomains::subdomains.import_blacklist') }}</label>
                        <div class="input-group">
                            <input type="file" class="form-control form-control-sm" name="file" accept=".txt,.csv">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-upload"></i> {{ __('subdomains::subdomains.import') }}
                            </button>
                        </div>
                        <small class="form-text text-muted">{{ __('subdomains::subdomains.import_blacklist_help') }}</small>
                    </form>

                    {{-- Export --}}
                    <a href="{{ route('admin.subdomains.blacklist.export') }}" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                        <i class="fas fa-download"></i> {{ __('subdomains::subdomains.export_blacklist') }}
                    </a>

                    {{-- Load Defaults --}}
                    <form action="{{ route('admin.subdomains.blacklist.default') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning btn-sm w-100"
                                onclick="return confirm('{{ __('subdomains::subdomains.default_blacklist_confirm') }}')">
                            <i class="fas fa-list"></i> {{ __('subdomains::subdomains.default_blacklist') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Blacklist Table --}}
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('subdomains::subdomains.blacklist_title') }}</h6>
                    {{-- Search --}}
                    <form action="{{ route('admin.subdomains.blacklist') }}" method="GET" class="d-flex">
                        <input type="text" class="form-control form-control-sm me-2" name="search"
                               value="{{ request('search') }}" placeholder="{{ __('subdomains::subdomains.search') }}...">
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    @if($blacklist->isEmpty())
                        <p class="text-muted text-center py-4">{{ __('subdomains::subdomains.blacklist_empty') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('subdomains::subdomains.blacklist_word') }}</th>
                                        <th>{{ __('subdomains::subdomains.blacklist_reason') }}</th>
                                        <th>{{ __('subdomains::subdomains.table_created') }}</th>
                                        <th width="80">{{ __('subdomains::subdomains.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($blacklist as $item)
                                        <tr>
                                            <td><code>{{ $item->word }}</code></td>
                                            <td>{{ $item->reason ?? '-' }}</td>
                                            <td>{{ $item->created_at->diffForHumans() }}</td>
                                            <td>
                                                <form action="{{ route('admin.subdomains.blacklist.remove', $item->id) }}"
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center mt-3">
                            {{ $blacklist->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
