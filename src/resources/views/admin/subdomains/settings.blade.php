@extends('layouts.admin')

@section('title', __('subdomains::subdomains.settings'))

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ __('subdomains::subdomains.settings') }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.subdomains.index') }}">{{ __('subdomains::subdomains.nav_subdomains') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('subdomains::subdomains.settings') }}</li>
                </ol>
            </nav>
        </div>
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

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.subdomains.settings.update') }}" method="POST">
        @csrf

        <div class="row">
            {{-- Cloudflare Settings --}}
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-cloud"></i> {{ __('subdomains::subdomains.cloudflare_settings') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="cloudflare_api_token" class="form-label">{{ __('subdomains::subdomains.api_token') }}</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="cloudflare_api_token"
                                       name="cloudflare_api_token"
                                       value="{{ $settings['cloudflare_api_token'] }}"
                                       placeholder="{{ __('subdomains::subdomains.api_token_placeholder') }}">
                                <button type="button" class="btn btn-outline-secondary" id="toggleTokenVisibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">{{ __('subdomains::subdomains.api_token_help') }}</small>
                        </div>

                        {{-- Test Connection --}}
                        <div class="mb-3">
                            <label class="form-label">{{ __('subdomains::subdomains.connection_status') }}</label>
                            <div>
                                <button type="button" class="btn btn-outline-info btn-sm" id="btnTestConnection">
                                    <i class="fas fa-plug"></i> {{ __('subdomains::subdomains.test_connection') }}
                                </button>
                                <span id="connectionResult" class="ms-2"></span>
                            </div>
                        </div>

                        <small class="text-muted">
                            <i class="fas fa-shield-alt"></i> {{ __('subdomains::subdomains.help_api_token_security') }}
                        </small>
                    </div>
                </div>
            </div>

            {{-- Subdomain Settings --}}
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-sliders-h"></i> {{ __('subdomains::subdomains.subdomain_settings') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="min_length" class="form-label">{{ __('subdomains::subdomains.min_length') }}</label>
                                <input type="number" class="form-control" id="min_length" name="min_length"
                                       value="{{ old('min_length', $settings['min_length']) }}" min="1" max="63">
                                <small class="form-text text-muted">{{ __('subdomains::subdomains.min_length_help') }}</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="max_length" class="form-label">{{ __('subdomains::subdomains.max_length') }}</label>
                                <input type="number" class="form-control" id="max_length" name="max_length"
                                       value="{{ old('max_length', $settings['max_length']) }}" min="1" max="63">
                                <small class="form-text text-muted">{{ __('subdomains::subdomains.max_length_help') }}</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="change_cooldown_hours" class="form-label">{{ __('subdomains::subdomains.change_cooldown') }}</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="change_cooldown_hours"
                                       name="change_cooldown_hours"
                                       value="{{ old('change_cooldown_hours', $settings['change_cooldown_hours']) }}"
                                       min="0">
                                <span class="input-group-text">{{ __('subdomains::subdomains.hours') }}</span>
                            </div>
                            <small class="form-text text-muted">{{ __('subdomains::subdomains.change_cooldown_help') }}</small>
                        </div>

                        <div class="mb-3">
                            <label for="default_ttl" class="form-label">{{ __('subdomains::subdomains.default_ttl') }}</label>
                            <select class="form-select" id="default_ttl" name="default_ttl">
                                <option value="1" @selected($settings['default_ttl'] == '1')>{{ __('subdomains::subdomains.ttl_auto') }}</option>
                                <option value="60" @selected($settings['default_ttl'] == '60')>{{ __('subdomains::subdomains.ttl_1min') }}</option>
                                <option value="300" @selected($settings['default_ttl'] == '300')>{{ __('subdomains::subdomains.ttl_5min') }}</option>
                                <option value="1800" @selected($settings['default_ttl'] == '1800')>{{ __('subdomains::subdomains.ttl_30min') }}</option>
                                <option value="3600" @selected($settings['default_ttl'] == '3600')>{{ __('subdomains::subdomains.ttl_1hour') }}</option>
                                <option value="43200" @selected($settings['default_ttl'] == '43200')>{{ __('subdomains::subdomains.ttl_12hours') }}</option>
                                <option value="86400" @selected($settings['default_ttl'] == '86400')>{{ __('subdomains::subdomains.ttl_1day') }}</option>
                            </select>
                            <small class="form-text text-muted">{{ __('subdomains::subdomains.default_ttl_help') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('subdomains::subdomains.auto_delete') }}</label>
                            <select class="form-select" name="auto_delete_on_terminate">
                                <option value="true" @selected($settings['auto_delete_on_terminate'] === 'true')>{{ __('subdomains::subdomains.enabled') }}</option>
                                <option value="false" @selected($settings['auto_delete_on_terminate'] === 'false')>{{ __('subdomains::subdomains.disabled') }}</option>
                            </select>
                            <small class="form-text text-muted">{{ __('subdomains::subdomains.auto_delete_help') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('subdomains::subdomains.auto_suspend') }}</label>
                            <select class="form-select" name="auto_suspend_on_suspend">
                                <option value="true" @selected($settings['auto_suspend_on_suspend'] === 'true')>{{ __('subdomains::subdomains.enabled') }}</option>
                                <option value="false" @selected($settings['auto_suspend_on_suspend'] === 'false')>{{ __('subdomains::subdomains.disabled') }}</option>
                            </select>
                            <small class="form-text text-muted">{{ __('subdomains::subdomains.auto_suspend_help') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Save Button --}}
        <div class="text-end mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> {{ __('subdomains::subdomains.save') }}
            </button>
        </div>
    </form>

    {{-- Domain Management --}}
    <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-globe"></i> {{ __('subdomains::subdomains.domain_management') }}
            </h6>
        </div>
        <div class="card-body">
            {{-- Existing Domains --}}
            @if($domains->isNotEmpty())
                <div class="table-responsive mb-4">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('subdomains::subdomains.domain_name') }}</th>
                                <th>{{ __('subdomains::subdomains.zone_id') }}</th>
                                <th>{{ __('subdomains::subdomains.is_default') }}</th>
                                <th>{{ __('subdomains::subdomains.is_active') }}</th>
                                <th>{{ __('subdomains::subdomains.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($domains as $domain)
                                <tr>
                                    <td><strong>{{ $domain->domain }}</strong></td>
                                    <td><code>{{ $domain->cloudflare_zone_id }}</code></td>
                                    <td>
                                        @if($domain->is_default)
                                            <span class="badge bg-primary">{{ __('subdomains::subdomains.yes') }}</span>
                                        @else
                                            <span class="text-muted">{{ __('subdomains::subdomains.no') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($domain->is_active)
                                            <span class="badge bg-success">{{ __('subdomains::subdomains.enabled') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('subdomains::subdomains.disabled') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.subdomains.domains.delete', $domain->id) }}"
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('{{ __('subdomains::subdomains.confirm_delete') }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted text-center">{{ __('subdomains::subdomains.no_domains') }}</p>
            @endif

            {{-- Add Domain Form --}}
            <hr>
            <h6 class="mb-3">{{ __('subdomains::subdomains.add_domain') }}</h6>
            <form action="{{ route('admin.subdomains.domains.add') }}" method="POST">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-4 mb-2">
                        <label for="domain" class="form-label">{{ __('subdomains::subdomains.domain_name') }}</label>
                        <input type="text" class="form-control" id="domain" name="domain"
                               placeholder="{{ __('subdomains::subdomains.domain_name_placeholder') }}" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label for="cloudflare_zone_id" class="form-label">{{ __('subdomains::subdomains.cloudflare_zone') }}</label>
                        <input type="text" class="form-control" id="cloudflare_zone_id" name="cloudflare_zone_id"
                               placeholder="{{ __('subdomains::subdomains.zone_id_placeholder') }}" required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1">
                            <label class="form-check-label" for="is_default">{{ __('subdomains::subdomains.is_default') }}</label>
                        </div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-plus"></i> {{ __('subdomains::subdomains.add_domain') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    // Toggle API token visibility
    document.getElementById('toggleTokenVisibility').addEventListener('click', function() {
        const input = document.getElementById('cloudflare_api_token');
        const icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });

    // Test Cloudflare connection
    document.getElementById('btnTestConnection').addEventListener('click', function() {
        const btn = this;
        const resultSpan = document.getElementById('connectionResult');
        const zoneIdInput = document.querySelector('input[name="cloudflare_zone_id"]');

        // Try to get zone ID from the first domain in the table, or from the add form
        let zoneId = '';
        const zoneCell = document.querySelector('table td code');
        if (zoneCell) {
            zoneId = zoneCell.textContent.trim();
        } else if (zoneIdInput) {
            zoneId = zoneIdInput.value.trim();
        }

        if (!zoneId) {
            resultSpan.innerHTML = '<span class="text-danger">{{ __("subdomains::subdomains.zone_id_placeholder") }}</span>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("subdomains::subdomains.testing_connection") }}';
        resultSpan.innerHTML = '';

        fetch('{{ route("admin.subdomains.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ zone_id: zoneId }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultSpan.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> ' + data.message + '</span>';
            } else {
                resultSpan.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> ' + data.message + '</span>';
            }
        })
        .catch(error => {
            resultSpan.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Connection failed</span>';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plug"></i> {{ __("subdomains::subdomains.test_connection") }}';
        });
    });
</script>
@endpush
@endsection
