<?php

namespace App\Http\Requests;

use App\Models\Subdomain;
use App\Rules\NotBlacklisted;
use App\Rules\SubdomainAvailable;
use Illuminate\Foundation\Http\FormRequest;

class SubdomainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $minLen = (int) Subdomain::getSetting('min_length', 3);
        $maxLen = (int) Subdomain::getSetting('max_length', 32);
        $domainId = (int) $this->input('domain_id', 0);

        // For updates, exclude the current subdomain from uniqueness check
        $excludeId = null;
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $serverId = $this->route('server');
            $existing = Subdomain::forServer($serverId)->first();
            $excludeId = $existing?->id;
        }

        return [
            'subdomain' => [
                'required',
                'string',
                "min:{$minLen}",
                "max:{$maxLen}",
                'regex:/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/',
                'not_regex:/--/',
                new NotBlacklisted(),
                new SubdomainAvailable($domainId, $excludeId),
            ],
            'domain_id' => 'required|integer|exists:pteroca_subdomain_domains,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $minLen = (int) Subdomain::getSetting('min_length', 3);
        $maxLen = (int) Subdomain::getSetting('max_length', 32);

        return [
            'subdomain.required' => __('subdomains::subdomains.subdomain_invalid'),
            'subdomain.min' => __('subdomains::subdomains.subdomain_too_short', ['min' => $minLen]),
            'subdomain.max' => __('subdomains::subdomains.subdomain_too_long', ['max' => $maxLen]),
            'subdomain.regex' => __('subdomains::subdomains.subdomain_invalid'),
            'subdomain.not_regex' => __('subdomains::subdomains.subdomain_invalid_consecutive'),
            'domain_id.required' => __('subdomains::subdomains.invalid_domain'),
            'domain_id.exists' => __('subdomains::subdomains.invalid_domain'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('subdomain')) {
            $this->merge([
                'subdomain' => strtolower(trim($this->input('subdomain'))),
            ]);
        }
    }
}
