<?php

namespace App\Rules;

use App\Models\SubdomainBlacklist;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotBlacklisted implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (SubdomainBlacklist::isBlacklisted(strtolower(trim($value)))) {
            $fail(__('subdomains::subdomains.subdomain_blacklisted'));
        }
    }
}
