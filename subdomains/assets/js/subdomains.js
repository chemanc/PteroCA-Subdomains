/**
 * PteroCA Subdomains Plugin - Client-side JavaScript
 */
(function() {
    'use strict';

    const config = window.SUBDOMAIN_CONFIG || {};
    let checkTimeout = null;

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
            const minLen = config.minLength || 3;

            if (val.length < minLen || !availability) return;

            availability.innerHTML = '<span class="availability-checking"><i class="fas fa-spinner fa-spin"></i> Checking...</span>';

            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(function() {
                const formData = new FormData();
                formData.append('subdomain', val);
                formData.append('domain_id', domainId);

                fetch(config.checkUrl, {
                    method: 'POST',
                    body: new URLSearchParams(formData),
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.available) {
                        availability.innerHTML = '<span class="availability-available"><i class="fas fa-check-circle"></i> ' + data.message + '</span>';
                    } else {
                        availability.innerHTML = '<span class="availability-taken"><i class="fas fa-times-circle"></i> ' + data.message + '</span>';
                    }
                })
                .catch(function() {
                    availability.innerHTML = '';
                });
            }, 500);
        }

        input.addEventListener('input', function() {
            this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
            updatePreview();
            checkAvailability();
        });

        domainSelect.addEventListener('change', function() {
            updatePreview();
            if (input.value.trim().length >= (config.minLength || 3)) {
                checkAvailability();
            }
        });

        updatePreview();
    }

    // Initialize forms
    setupSubdomainInput('subdomainInput', 'domainSelect', 'createAvailability', 'createPreview', 'createPreviewAddress');
    setupSubdomainInput('changeSubdomainInput', 'changeDomainSelect', 'changeAvailability', 'changePreview', 'changePreviewAddress');

    // Copy to clipboard
    var copyBtn = document.getElementById('btnCopyAddress');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            var address = this.getAttribute('data-address');
            var btn = this;
            navigator.clipboard.writeText(address).then(function() {
                var original = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.classList.add('btn-copy-success');
                setTimeout(function() {
                    btn.innerHTML = original;
                    btn.classList.remove('btn-copy-success');
                }, 2000);
            }).catch(function() {
                alert('Failed to copy');
            });
        });
    }
})();
