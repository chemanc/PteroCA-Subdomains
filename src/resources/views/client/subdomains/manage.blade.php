@extends('layouts.app')

@section('title', __('subdomains::subdomains.your_subdomain'))

@section('content')
<div class="container">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
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

    @if($subdomain)
        {{-- ================================================================ --}}
        {{-- HAS SUBDOMAIN: Show current subdomain info                       --}}
        {{-- ================================================================ --}}
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-globe"></i> {{ __('subdomains::subdomains.your_subdomain') }}
                </h5>
                {{-- Status Badge --}}
                @switch($subdomain->status)
                    @case('active')
                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> {{ __('subdomains::subdomains.status_active') }}</span>
                        @break
                    @case('pending')
                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> {{ __('subdomains::subdomains.status_pending') }}</span>
                        @break
                    @case('suspended')
                        <span class="badge bg-secondary"><i class="fas fa-pause-circle"></i> {{ __('subdomains::subdomains.status_suspended') }}</span>
                        @break
                    @case('error')
                        <span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> {{ __('subdomains::subdomains.status_error') }}</span>
                        @break
                @endswitch
            </div>
            <div class="card-body">

                @if($subdomain->status === 'error' && $subdomain->error_message)
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> {{ $subdomain->error_message }}
                    </div>
                @endif

                @if($subdomain->status === 'pending')
                    <div class="alert alert-info">
                        <i class="fas fa-spinner fa-spin"></i> {{ __('subdomains::subdomains.dns_propagating') }}
                    </div>
                @endif

                @if($subdomain->status === 'suspended')
                    <div class="alert alert-warning">
                        <i class="fas fa-pause-circle"></i> {{ __('subdomains::subdomains.dns_suspended') }}
                    </div>
                @endif

                {{-- Server Address Display --}}
                @if($subdomain->status === 'active')
                    <div class="bg-light rounded p-4 mb-4 text-center">
                        <small class="text-muted d-block mb-1">{{ __('subdomains::subdomains.minecraft_connect') }}</small>
                        <h3 class="mb-2 font-monospace" id="serverAddress">{{ $subdomain->full_address }}</h3>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnCopyAddress"
                                data-address="{{ $subdomain->full_address }}">
                            <i class="fas fa-copy"></i> {{ __('subdomains::subdomains.copy_address') }}
                        </button>
                        <small class="d-block mt-2 text-success">
                            <i class="fas fa-info-circle"></i> {{ __('subdomains::subdomains.minecraft_port_note') }}
                        </small>
                    </div>
                @endif

                {{-- Subdomain Details --}}
                <div class="row mb-4">
                    <div class="col-md-4">
                        <strong>{{ __('subdomains::subdomains.subdomain') }}:</strong>
                        <br><code>{{ $subdomain->subdomain }}</code>
                    </div>
                    <div class="col-md-4">
                        <strong>{{ __('subdomains::subdomains.domain') }}:</strong>
                        <br>{{ $subdomain->domain->domain ?? 'N/A' }}
                    </div>
                    <div class="col-md-4">
                        <strong>{{ __('subdomains::subdomains.table_created') }}:</strong>
                        <br>{{ $subdomain->created_at->diffForHumans() }}
                    </div>
                </div>

                <hr>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2">
                    {{-- Change Subdomain --}}
                    @if($cooldownRemaining)
                        <button type="button" class="btn btn-outline-warning" disabled
                                title="{{ __('subdomains::subdomains.cooldown_active', ['time' => $cooldownRemaining]) }}">
                            <i class="fas fa-clock"></i> {{ __('subdomains::subdomains.change_subdomain') }}
                            <small>({{ $cooldownRemaining }})</small>
                        </button>
                    @else
                        <button type="button" class="btn btn-outline-warning" data-bs-toggle="collapse" data-bs-target="#changeForm">
                            <i class="fas fa-edit"></i> {{ __('subdomains::subdomains.change_subdomain') }}
                        </button>
                    @endif

                    {{-- Delete Subdomain --}}
                    <form action="{{ route('client.subdomain.destroy', $server->id) }}" method="POST"
                          onsubmit="return confirm('{{ __('subdomains::subdomains.confirm_delete_text') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fas fa-trash"></i> {{ __('subdomains::subdomains.delete_subdomain') }}
                        </button>
                    </form>
                </div>

                {{-- Change Subdomain Form (collapsed) --}}
                @if(!$cooldownRemaining)
                    <div class="collapse mt-3" id="changeForm">
                        <div class="card card-body bg-light">
                            <p class="text-warning small mb-3">
                                <i class="fas fa-exclamation-triangle"></i> {{ __('subdomains::subdomains.confirm_change_text') }}
                            </p>
                            <form action="{{ route('client.subdomain.update', $server->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row align-items-end">
                                    <div class="col-md-5 mb-2">
                                        <label class="form-label">{{ __('subdomains::subdomains.subdomain') }}</label>
                                        <input type="text" class="form-control" name="subdomain" id="changeSubdomainInput"
                                               value="{{ old('subdomain') }}"
                                               placeholder="{{ __('subdomains::subdomains.subdomain_placeholder') }}"
                                               pattern="[a-z0-9][a-z0-9\-]*[a-z0-9]"
                                               minlength="{{ $minLength }}" maxlength="{{ $maxLength }}" required>
                                        <div id="changeAvailability" class="small mt-1"></div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">{{ __('subdomains::subdomains.domain') }}</label>
                                        <select class="form-select" name="domain_id" id="changeDomainSelect">
                                            @foreach($domains as $d)
                                                <option value="{{ $d->id }}" @selected($d->id === $subdomain->domain_id)>
                                                    .{{ $d->domain }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <button type="submit" class="btn btn-warning w-100">
                                            <i class="fas fa-save"></i> {{ __('subdomains::subdomains.update') }}
                                        </button>
                                    </div>
                                </div>
                                <div id="changePreview" class="small text-muted mt-2" style="display:none;">
                                    {{ __('subdomains::subdomains.subdomain_preview') }}
                                    <strong id="changePreviewAddress"></strong>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    @else
        {{-- ================================================================ --}}
        {{-- NO SUBDOMAIN: Show creation form                                 --}}
        {{-- ================================================================ --}}
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-globe"></i> {{ __('subdomains::subdomains.create_subdomain') }}
                </h5>
            </div>
            <div class="card-body">
                @if($domains->isEmpty())
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> {{ __('subdomains::subdomains.domain_not_configured') }}
                    </div>
                @else
                    <p class="text-muted mb-4">{{ __('subdomains::subdomains.no_subdomain_hint') }}</p>

                    <form action="{{ route('client.subdomain.store', $server->id) }}" method="POST">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-5 mb-3">
                                <label for="subdomain" class="form-label">{{ __('subdomains::subdomains.subdomain') }}</label>
                                <input type="text" class="form-control" id="subdomainInput" name="subdomain"
                                       value="{{ old('subdomain') }}"
                                       placeholder="{{ __('subdomains::subdomains.subdomain_placeholder') }}"
                                       pattern="[a-z0-9][a-z0-9\-]*[a-z0-9]"
                                       minlength="{{ $minLength }}" maxlength="{{ $maxLength }}" required
                                       autocomplete="off">
                                <div id="createAvailability" class="small mt-1"></div>
                                <small class="form-text text-muted">{{ __('subdomains::subdomains.help_subdomain_format') }}</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="domain_id" class="form-label">{{ __('subdomains::subdomains.domain') }}</label>
                                <select class="form-select" id="domainSelect" name="domain_id">
                                    @foreach($domains as $d)
                                        <option value="{{ $d->id }}" @selected($d->is_default)>.{{ $d->domain }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <button type="submit" class="btn btn-primary w-100" id="btnCreate">
                                    <i class="fas fa-plus"></i> {{ __('subdomains::subdomains.create_subdomain') }}
                                </button>
                            </div>
                        </div>

                        {{-- Live Preview --}}
                        <div id="createPreview" class="bg-light rounded p-3 mt-2" style="display:none;">
                            <small class="text-muted">{{ __('subdomains::subdomains.subdomain_preview') }}</small>
                            <h5 class="mb-0 font-monospace" id="createPreviewAddress"></h5>
                        </div>
                    </form>

                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> {{ __('subdomains::subdomains.help_dns_propagation') }}
                        </small>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> {{ __('subdomains::subdomains.help_srv_record') }}
                        </small>
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>

@push('scripts')
<script>
(function() {
    'use strict';

    let checkTimeout = null;
    const csrfToken = '{{ csrf_token() }}';
    const checkUrl = '{{ route("api.subdomain.check") }}';

    /**
     * Setup live validation and preview for a subdomain input.
     */
    function setupSubdomainInput(inputId, domainSelectId, availabilityId, previewId, previewAddressId) {
        const input = document.getElementById(inputId);
        const domainSelect = document.getElementById(domainSelectId);
        const availability = document.getElementById(availabilityId);
        const preview = document.getElementById(previewId);
        const previewAddress = document.getElementById(previewAddressId);

        if (!input || !domainSelect) return;

        function updatePreview() {
            const val = input.value.toLowerCase().trim();
            const domainText = domainSelect.options[domainSelect.selectedIndex]?.text || '';

            if (val.length > 0 && preview && previewAddress) {
                preview.style.display = '';
                previewAddress.textContent = val + domainText;
            } else if (preview) {
                preview.style.display = 'none';
            }
        }

        function checkAvailability() {
            const val = input.value.toLowerCase().trim();
            const domainId = domainSelect.value;

            if (val.length < {{ $minLength }}) {
                availability.innerHTML = '';
                return;
            }

            availability.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin"></i> {{ __("subdomains::subdomains.checking_availability") }}</span>';

            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(function() {
                fetch(checkUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ subdomain: val, domain_id: domainId }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.available) {
                        availability.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> ' + data.message + '</span>';
                    } else {
                        availability.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> ' + data.message + '</span>';
                    }
                })
                .catch(() => {
                    availability.innerHTML = '';
                });
            }, 500);
        }

        input.addEventListener('input', function() {
            // Force lowercase
            this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
            updatePreview();
            checkAvailability();
        });

        domainSelect.addEventListener('change', function() {
            updatePreview();
            if (input.value.trim().length >= {{ $minLength }}) {
                checkAvailability();
            }
        });

        // Initial preview if value exists
        updatePreview();
    }

    // Create form
    setupSubdomainInput('subdomainInput', 'domainSelect', 'createAvailability', 'createPreview', 'createPreviewAddress');

    // Change form
    setupSubdomainInput('changeSubdomainInput', 'changeDomainSelect', 'changeAvailability', 'changePreview', 'changePreviewAddress');

    // Copy to clipboard
    const copyBtn = document.getElementById('btnCopyAddress');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const address = this.getAttribute('data-address');
            navigator.clipboard.writeText(address).then(() => {
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> {{ __("subdomains::subdomains.copied") }}';
                this.classList.replace('btn-outline-primary', 'btn-success');
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.classList.replace('btn-success', 'btn-outline-primary');
                }, 2000);
            }).catch(() => {
                alert('{{ __("subdomains::subdomains.copy_failed") }}');
            });
        });
    }
})();
</script>
@endpush
@endsection
