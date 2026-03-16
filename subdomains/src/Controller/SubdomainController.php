<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Controller;

use App\Core\Service\Plugin\PluginSettingService;
use Doctrine\ORM\EntityManagerInterface;
use Plugins\Subdomains\Entity\Subdomain;
use Plugins\Subdomains\Entity\SubdomainDomain;
use Plugins\Subdomains\Entity\SubdomainLog;
use Plugins\Subdomains\Entity\Repository\SubdomainBlacklistRepository;
use Plugins\Subdomains\Entity\Repository\SubdomainRepository;
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
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'doctrine.orm.entity_manager' => '?' . EntityManagerInterface::class,
        ]);
    }

    private function em(): EntityManagerInterface
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }

    // =========================================================================
    // Show subdomain for a server
    // =========================================================================

    #[Route('/servers/{serverId}/subdomain', name: 'show', methods: ['GET'])]
    public function show(int $serverId, PluginSettingService $settings): Response
    {
        $server = $this->getAuthorizedServer($serverId);

        $em = $this->em();
        $subdomain = $em->getRepository(Subdomain::class)->findByServer($serverId);
        $domains = $em->getRepository(SubdomainDomain::class)->findActive();

        $cooldownHours = (int) $settings->get('subdomains', 'change_cooldown_hours', 24);
        $cooldownRemaining = $subdomain?->getCooldownRemaining($cooldownHours);

        return $this->render('@PluginSubdomains/client/manage.html.twig', [
            'server' => $server,
            'subdomain' => $subdomain,
            'domains' => $domains,
            'cooldownRemaining' => $cooldownRemaining,
            'minLength' => (int) $settings->get('subdomains', 'min_length', 3),
            'maxLength' => (int) $settings->get('subdomains', 'max_length', 32),
        ]);
    }

    // =========================================================================
    // Create subdomain
    // =========================================================================

    #[Route('/servers/{serverId}/subdomain', name: 'store', methods: ['POST'])]
    public function store(int $serverId, Request $request, CloudflareService $cloudflare, PluginSettingService $settings): Response
    {
        $server = $this->getAuthorizedServer($serverId);
        $em = $this->em();

        // Check server doesn't already have a subdomain
        if ($em->getRepository(Subdomain::class)->findByServer($serverId)) {
            $this->addFlash('error', 'This server already has a subdomain.');
            return $this->redirectToRoute('plugin_subdomains_show', ['serverId' => $serverId]);
        }

        $subdomainName = strtolower(trim($request->request->get('subdomain', '')));
        $domainId = (int) $request->request->get('domain_id', 0);

        // Validate
        $error = $this->validateSubdomain($subdomainName, $domainId, $settings);
        if ($error) {
            $this->addFlash('error', $error);
            return $this->redirectToRoute('plugin_subdomains_show', ['serverId' => $serverId]);
        }

        $domain = $em->getRepository(SubdomainDomain::class)->find($domainId);
        if (!$domain || !$domain->isActive()) {
            $this->addFlash('error', 'Invalid domain selected.');
            return $this->redirectToRoute('plugin_subdomains_show', ['serverId' => $serverId]);
        }

        // Create DNS records
        try {
            $serverIp = $this->getServerIp($server);
            $serverPort = $this->getServerPort($server);
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

            $em->persist($subdomain);
            $em->flush();

            // Log
            $logRepo = $em->getRepository(SubdomainLog::class);
            $logRepo->log('create', $subdomain, $this->getUser()->getId(), [
                'subdomain' => $subdomainName, 'domain' => $domain->getDomain(),
                'server_id' => $serverId, 'server_ip' => $serverIp, 'server_port' => $serverPort,
            ], $request->getClientIp());

            $this->addFlash('success', 'Subdomain created successfully! DNS may take a few minutes to propagate.');
        } catch (CloudflareException $e) {
            $this->addFlash('error', 'Failed to create DNS records: ' . $e->getMessage());
        }

        return $this->redirectToRoute('plugin_subdomains_show', ['serverId' => $serverId]);
    }

    // =========================================================================
    // Update subdomain
    // =========================================================================

    #[Route('/servers/{serverId}/subdomain/update', name: 'update', methods: ['POST'])]
    public function update(int $serverId, Request $request, CloudflareService $cloudflare, PluginSettingService $settings): Response
    {
        $server = $this->getAuthorizedServer($serverId);
        $em = $this->em();

        $existing = $em->getRepository(Subdomain::class)->findByServer($serverId);
        if (!$existing) {
            $this->addFlash('error', 'No subdomain found for this server.');
            return $this->redirectToRoute('plugin_subdomains_show', ['serverId' => $serverId]);
        }

        // Check cooldown
        $cooldownHours = (int) $settings->get('subdomains', 'change_cooldown_hours', 24);
        if ($existing->isOnCooldown($cooldownHours)) {
            $this->addFlash('error', 'Cooldown active. Please wait before changing your subdomain.');
            return $this->redirectToRoute('plugin_subdomains_show', ['serverId' => $serverId]);
        }

        $newName = strtolower(trim($request->request->get('subdomain', '')));
        $domainId = (int) $request->request->get('domain_id', 0);

        $error = $this->validateSubdomain($newName, $domainId, $settings, $existing->getId());
        if ($error) {
            $this->addFlash('error', $error);
            return $this->redirectToRoute('plugin_subdomains_show', ['serverId' => $serverId]);
        }

        $domain = $em->getRepository(SubdomainDomain::class)->find($domainId);

        try {
            $serverIp = $this->getServerIp($server);
            $serverPort = $this->getServerPort($server);
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

            $em->flush();

            $em->getRepository(SubdomainLog::class)->log('update', $existing, $this->getUser()->getId(), [
                'old' => $oldAddress, 'new' => $existing->getFullAddress(),
            ], $request->getClientIp());

            $this->addFlash('success', 'Subdomain updated successfully!');
        } catch (CloudflareException $e) {
            $this->addFlash('error', 'Failed to update DNS records: ' . $e->getMessage());
        }

        return $this->redirectToRoute('plugin_subdomains_show', ['serverId' => $serverId]);
    }

    // =========================================================================
    // Delete subdomain
    // =========================================================================

    #[Route('/servers/{serverId}/subdomain/delete', name: 'destroy', methods: ['POST'])]
    public function destroy(int $serverId, Request $request, CloudflareService $cloudflare): Response
    {
        $server = $this->getAuthorizedServer($serverId);
        $em = $this->em();

        $subdomain = $em->getRepository(Subdomain::class)->findByServer($serverId);
        if (!$subdomain) {
            $this->addFlash('error', 'No subdomain found.');
            return $this->redirectToRoute('plugin_subdomains_show', ['serverId' => $serverId]);
        }

        // Delete DNS
        try {
            $zoneId = $subdomain->getDomain()->getCloudflareZoneId();
            $cloudflare->deleteSubdomainRecords($zoneId, $subdomain->getCloudflareARecordId(), $subdomain->getCloudflareSrvRecordId());
        } catch (\Exception $e) {
            // Log but continue with DB deletion
        }

        $address = $subdomain->getFullAddress();
        $em->getRepository(SubdomainLog::class)->log('delete', $subdomain, $this->getUser()->getId(), [
            'subdomain' => $address, 'server_id' => $serverId,
        ], $request->getClientIp());

        $em->remove($subdomain);
        $em->flush();

        $this->addFlash('success', 'Subdomain deleted successfully.');
        return $this->redirectToRoute('plugin_subdomains_show', ['serverId' => $serverId]);
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
            $domain = $this->em()->getRepository(SubdomainDomain::class)->find($domainId);
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
        $blacklistRepo = $this->em()->getRepository(\Plugins\Subdomains\Entity\SubdomainBlacklist::class);
        if ($blacklistRepo->isBlacklisted($subdomain)) {
            return 'This subdomain is not allowed.';
        }

        // Uniqueness
        $subRepo = $this->em()->getRepository(Subdomain::class);
        if ($subRepo->existsBySubdomainAndDomain($subdomain, $domainId, $excludeId)) {
            return 'This subdomain is already taken.';
        }

        return null;
    }

    /**
     * Get and authorize the server (user must own it).
     */
    private function getAuthorizedServer(int $serverId): object
    {
        $em = $this->em();

        // PteroCA Server entity
        if (class_exists(\App\Core\Entity\Server::class)) {
            $server = $em->getRepository(\App\Core\Entity\Server::class)->find($serverId);
        } else {
            throw $this->createNotFoundException('Server model not found.');
        }

        if (!$server) {
            throw $this->createNotFoundException('Server not found.');
        }

        // Authorization: user must own the server
        $userId = $this->getUser()?->getId();
        $serverUserId = method_exists($server, 'getUser') ? $server->getUser()?->getId() : null;

        if ($userId === null || $serverUserId !== $userId) {
            throw $this->createAccessDeniedException('You do not have permission to manage this subdomain.');
        }

        return $server;
    }

    private function getServerIp(object $server): string
    {
        if (method_exists($server, 'getIp')) {
            return $server->getIp() ?? '0.0.0.0';
        }
        return '0.0.0.0';
    }

    private function getServerPort(object $server): int
    {
        if (method_exists($server, 'getPort')) {
            return (int) ($server->getPort() ?? 25565);
        }
        return 25565;
    }
}
