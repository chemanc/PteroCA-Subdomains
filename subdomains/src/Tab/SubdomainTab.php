<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Tab;

use App\Core\Contract\Tab\ServerTabInterface;
use App\Core\DTO\ServerTabContext;

/**
 * Adds a "Subdomain" tab to the server management page.
 */
class SubdomainTab implements ServerTabInterface
{
    public function getId(): string
    {
        return 'subdomain';
    }

    public function getLabel(): string
    {
        return 'Subdomain';
    }

    public function getPriority(): int
    {
        return 45; // Between Network (60) and Databases (40)
    }

    public function isVisible(ServerTabContext $context): bool
    {
        return true; // Visible to all users with server access
    }

    public function isDefault(): bool
    {
        return false;
    }

    public function getTemplate(): string
    {
        return '@PluginSubdomains/client/tab_subdomain.html.twig';
    }

    public function getStylesheets(): array
    {
        return [];
    }

    public function getJavascripts(): array
    {
        return [];
    }

    public function requiresFullReload(): bool
    {
        return false;
    }
}
