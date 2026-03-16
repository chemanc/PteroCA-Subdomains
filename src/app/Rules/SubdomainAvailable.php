<?php

namespace App\Rules;

use App\Models\Subdomain;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SubdomainAvailable implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param int      $domainId  The domain ID to check uniqueness against
     * @param int|null $excludeId Optional subdomain ID to exclude (for updates)
     */
    public function __construct(
        protected int $domainId,
        protected ?int $excludeId = null
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = Subdomain::where('subdomain', strtolower(trim($value)))
            ->where('domain_id', $this->domainId);

        if ($this->excludeId) {
            $query->where('id', '!=', $this->excludeId);
        }

        if ($query->exists()) {
            $fail(__('subdomains::subdomains.subdomain_taken'));
        }
    }
}
