<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Controller;

use App\Core\Entity\Server;
use App\Core\Service\Plugin\PluginSettingService;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use Doctrine\ORM\EntityManagerInterface;
use Plugins\Subdomains\Entity\Subdomain;
use Plugins\Subdomains\Entity\SubdomainDomain;
use Plugins\Subdomains\Entity\SubdomainLog;
use Plugins\Subdomains\Exception\CloudflareException;
use Plugins\Subdomains\Service\CloudflareService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Client controller for subdomain management (per server).
 */
#[Route(name: 'plugin_subdomains_')]
class SubdomainController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PterodactylApplicationService $pterodactylService,
    ) {
    }

    /**
     * Redirect back to the server page's subdomain tab.
     */
    private function redirectToServerTab(object $server): Response
    {
        $identifier = $server->getPterodactylServerIdentifier();
        return $this->redirect("/panel?routeName=server&id={$identifier}#subdomain");
    }

    // =========================================================================
    // Show subdomain for a server
    // =========================================================================

    #[Route('/servers/{serverId}/subdomain', name: 'show', methods: ['GET'])]
    public function show(int $serverId, Request $request, PluginSettingService $settings): Response
    {
        $server = $this->getAuthorizedServer($serverId);

        $subdomain = $this->entityManager->getRepository(Subdomain::class)->findByServer($serverId);
        $domains = $this->entityManager->getRepository(SubdomainDomain::class)->findActive();

        $cooldownHours = (int) $settings->get('subdomains', 'change_cooldown_hours', 24);
        $cooldownRemaining = $subdomain?->getCooldownRemaining($cooldownHours);

        $templateData = [
            'server' => $server,
            'subdomain' => $subdomain,
            'domains' => $domains,
            'cooldownRemaining' => $cooldownRemaining,
            'minLength' => (int) $settings->get('subdomains', 'min_length', 3),
            'maxLength' => (int) $settings->get('subdomains', 'max_length', 32),
        ];

        // If AJAX request, return partial HTML (for tab embed)
        if ($request->isXmlHttpRequest()) {
            return $this->render('@PluginSubdomains/client/manage_partial.html.twig', $templateData);
        }

        return $this->render('@PluginSubdomains/client/manage.html.twig', $templateData);
    }

    // =========================================================================
    // Create subdomain
    // =========================================================================

    #[Route('/servers/{serverId}/subdomain', name: 'store', methods: ['POST'])]
    public function store(int $serverId, Request $request, CloudflareService $cloudflare, PluginSettingService $settings): Response
    {
        $server = $this->getAuthorizedServer($serverId);

        // Check server doesn't already have a subdomain
        if ($this->entityManager->getRepository(Subdomain::class)->findByServer($serverId)) {
            $this->addFlash('error', 'This server already has a subdomain.');
            return $this->redirectToServerTab($server);
        }

        $subdomainName = strtolower(trim($request->request->get('subdomain', '')));
        $domainId = (int) $request->request->get('domain_id', 0);

        // Validate
        $error = $this->validateSubdomain($subdomainName, $domainId, $settings);
        if ($error) {
            $this->addFlash('error', $error);
            return $this->redirectToServerTab($server);
        }

        $domain = $this->entityManager->getRepository(SubdomainDomain::class)->find($domainId);
        if (!$domain || !$domain->isActive()) {
            $this->addFlash('error', 'Invalid domain selected.');
            return $this->redirectToServerTab($server);
        }

        // Create DNS records
        try {
            [$serverIp, $serverPort] = $this->getServerAllocation($server);
            $ttl = (int) $settings->get('subdomains', 'default_ttl', 1);

            $records = $cloudflare->createSubdomainRecords(
                $domain->getCloudflareZoneId(), $subdomainName, $domain->getDomain(), $serverIp, $serverPort, $ttl
            );

            $subdomain = new Subdomain();
            $subdomain->setServerId($serverId);
            $subdomain->setUserId($this->getUser()->getId());
            $subdomain->setSubdomain($subdomainName);
            $subdomain->setDomain($domain);
            $subdomain->setCloudflareARecordId($records['a_record_id']);
            $subdomain->setCloudflareSrvRecordId($records['srv_record_id']);
            $subdomain->setStatus(Subdomain::STATUS_ACTIVE);
            $subdomain->setLastChangedAt(new \DateTimeImmutable());

            $this->entityManager->persist($subdomain);
            $this->entityManager->flush();

            // Log
            $logRepo = $this->entityManager->getRepository(SubdomainLog::class);
            $logRepo->log('create', $subdomain, $this->getUser()->getId(), [
                'subdomain' => $subdomainName, 'domain' => $domain->getDomain(),
                'server_id' => $serverId, 'server_ip' => $serverIp, 'server_port' => $serverPort,
            ], $request->getClientIp());

            $this->addFlash('success', 'Subdomain created successfully! DNS may take a few minutes to propagate.');
        } catch (CloudflareException $e) {
            $this->addFlash('error', 'Failed to create DNS records: ' . $e->getMessage());
        }

        return $this->redirectToServerTab($server);
    }

    // =========================================================================
    // Update subdomain
    // =========================================================================

    #[Route('/servers/{serverId}/subdomain/update', name: 'update', methods: ['POST'])]
    public function update(int $serverId, Request $request, CloudflareService $cloudflare, PluginSettingService $settings): Response
    {
        $server = $this->getAuthorizedServer($serverId);

        $existing = $this->entityManager->getRepository(Subdomain::class)->findByServer($serverId);
        if (!$existing) {
            $this->addFlash('error', 'No subdomain found for this server.');
            return $this->redirectToServerTab($server);
        }

        // Check cooldown
        $cooldownHours = (int) $settings->get('subdomains', 'change_cooldown_hours', 24);
        if ($existing->isOnCooldown($cooldownHours)) {
            $this->addFlash('error', 'Cooldown active. Please wait before changing your subdomain.');
            return $this->redirectToServerTab($server);
        }

        $newName = strtolower(trim($request->request->get('subdomain', '')));
        $domainId = (int) $request->request->get('domain_id', 0);

        $error = $this->validateSubdomain($newName, $domainId, $settings, $existing->getId());
        if ($error) {
            $this->addFlash('error', $error);
            return $this->redirectToServerTab($server);
        }

        $domain = $this->entityManager->getRepository(SubdomainDomain::class)->find($domainId);

        try {
            [$serverIp, $serverPort] = $this->getServerAllocation($server);
            $ttl = (int) $settings->get('subdomains', 'default_ttl', 1);

            // Delete old DNS
            $oldZoneId = $existing->getDomain()->getCloudflareZoneId();
            $cloudflare->deleteSubdomainRecords($oldZoneId, $existing->getCloudflareARecordId(), $existing->getCloudflareSrvRecordId());

            // Create new DNS
            $records = $cloudflare->createSubdomainRecords(
                $domain->getCloudflareZoneId(), $newName, $domain->getDomain(), $serverIp, $serverPort, $ttl
            );

            $oldAddress = $existing->getFullAddress();

            $existing->setSubdomain($newName);
            $existing->setDomain($domain);
            $existing->setCloudflareARecordId($records['a_record_id']);
            $existing->setCloudflareSrvRecordId($records['srv_record_id']);
            $existing->setStatus(Subdomain::STATUS_ACTIVE);
            $existing->setErrorMessage(null);
            $existing->setLastChangedAt(new \DateTimeImmutable());
            $existing->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->flush();

            $this->entityManager->getRepository(SubdomainLog::class)->log('update', $existing, $this->getUser()->getId(), [
                'old' => $oldAddress, 'new' => $existing->getFullAddress(),
            ], $request->getClientIp());

            $this->addFlash('success', 'Subdomain updated successfully!');
        } catch (CloudflareException $e) {
            $this->addFlash('error', 'Failed to update DNS records: ' . $e->getMessage());
        }

        return $this->redirectToServerTab($server);
    }

    // =========================================================================
    // Delete subdomain
    // =========================================================================

    #[Route('/servers/{serverId}/subdomain/delete', name: 'destroy', methods: ['POST'])]
    public function destroy(int $serverId, Request $request, CloudflareService $cloudflare): Response
    {
        $server = $this->getAuthorizedServer($serverId);

        $subdomain = $this->entityManager->getRepository(Subdomain::class)->findByServer($serverId);
        if (!$subdomain) {
            $this->addFlash('error', 'No subdomain found.');
            return $this->redirectToServerTab($server);
        }

        // Delete DNS
        try {
            $zoneId = $subdomain->getDomain()->getCloudflareZoneId();
            $cloudflare->deleteSubdomainRecords($zoneId, $subdomain->getCloudflareARecordId(), $subdomain->getCloudflareSrvRecordId());
        } catch (\Exception $e) {
            // Log but continue with DB deletion
        }

        $address = $subdomain->getFullAddress();
        $this->entityManager->getRepository(SubdomainLog::class)->log('delete', $subdomain, $this->getUser()->getId(), [
            'subdomain' => $address, 'server_id' => $serverId,
        ], $request->getClientIp());

        $this->entityManager->remove($subdomain);
        $this->entityManager->flush();

        $this->addFlash('success', 'Subdomain deleted successfully.');
        return $this->redirectToServerTab($server);
    }

    // =========================================================================
    // AJAX: Check availability
    // =========================================================================

    #[Route('/api/check-availability', name: 'check_availability', methods: ['POST'])]
    public function checkAvailability(Request $request, CloudflareService $cloudflare, PluginSettingService $settings): JsonResponse
    {
        $subdomainName = strtolower(trim($request->request->get('subdomain', '')));
        $domainId = (int) $request->request->get('domain_id', 0);

        $error = $this->validateSubdomain($subdomainName, $domainId, $settings);
        if ($error) {
            return $this->json(['available' => false, 'message' => $error]);
        }

        // Check Cloudflare
        try {
            $domain = $this->entityManager->getRepository(SubdomainDomain::class)->find($domainId);
            if ($domain) {
                $fullName = $subdomainName . '.' . $domain->getDomain();
                $existing = $cloudflare->recordExists($domain->getCloudflareZoneId(), $fullName, 'A');
                if ($existing) {
                    return $this->json(['available' => false, 'message' => 'This subdomain already exists in DNS.']);
                }
            }
        } catch (CloudflareException $e) {
            // If CF check fails, allow (will be caught on creation)
        }

        return $this->json(['available' => true, 'message' => 'Subdomain is available!']);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function validateSubdomain(string $subdomain, int $domainId, PluginSettingService $settings, ?int $excludeId = null): ?string
    {
        $minLen = (int) $settings->get('subdomains', 'min_length', 3);
        $maxLen = (int) $settings->get('subdomains', 'max_length', 32);

        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $subdomain)) {
            return 'Invalid subdomain format. Use only lowercase letters, numbers, and hyphens.';
        }
        if (preg_match('/--/', $subdomain)) {
            return 'Subdomain cannot have consecutive hyphens.';
        }
        if (strlen($subdomain) < $minLen) {
            return "Subdomain must be at least {$minLen} characters.";
        }
        if (strlen($subdomain) > $maxLen) {
            return "Subdomain cannot exceed {$maxLen} characters.";
        }

        // Blacklist
        $blacklistRepo = $this->entityManager->getRepository(\Plugins\Subdomains\Entity\SubdomainBlacklist::class);
        if ($blacklistRepo->isBlacklisted($subdomain)) {
            return 'This subdomain is not allowed.';
        }

        // Uniqueness
        $subRepo = $this->entityManager->getRepository(Subdomain::class);
        if ($subRepo->existsBySubdomainAndDomain($subdomain, $domainId, $excludeId)) {
            return 'This subdomain is already taken.';
        }

        return null;
    }

    /**
     * Get and authorize the server (user must own it).
     */
    private function getAuthorizedServer(int $serverId): Server
    {
        $server = $this->entityManager->getRepository(Server::class)->find($serverId);

        if (!$server) {
            throw $this->createNotFoundException('Server not found.');
        }

        // Authorization: user must own the server
        $userId = $this->getUser()?->getId();
        $serverUserId = $server->getUser()?->getId();

        if ($userId === null || $serverUserId !== $userId) {
            throw $this->createAccessDeniedException('You do not have permission to manage this subdomain.');
        }

        return $server;
    }

    /**
     * Get server IP and port from Pterodactyl API.
     * @return array{0: string, 1: int} [ip, port]
     */
    private function getServerAllocation(Server $server): array
    {
        try {
            $pterodactylServer = $this->pterodactylService
                ->getApplicationApi()
                ->servers()
                ->getServer((string) $server->getPterodactylServerId(), ['allocations']);

            $allocations = $pterodactylServer->get('relationships')['allocations'] ?? null;
            $primaryId = $pterodactylServer->get('allocation') ?? null;
            $primary = null;

            if ($allocations instanceof \App\Core\DTO\Pterodactyl\Collection) {
                foreach ($allocations as $a) {
                    if ($a->get('id') === $primaryId) {
                        $primary = $a;
                        break;
                    }
                }
                if ($primary === null && !$allocations->isEmpty()) {
                    $primary = $allocations->first();
                }
            }

            if ($primary) {
                $ip = $primary->get('alias') ?? $primary->get('ip') ?? '0.0.0.0';
                $port = (int) ($primary->get('port') ?? 25565);
                return [$ip, $port];
            }
        } catch (\Exception $e) {
            // Fall through to defaults
        }

        return ['0.0.0.0', 25565];
    }
}
