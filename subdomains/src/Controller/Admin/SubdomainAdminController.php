<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Plugins\Subdomains\Entity\Subdomain;
use Plugins\Subdomains\Entity\SubdomainBlacklist;
use Plugins\Subdomains\Entity\SubdomainDomain;
use Plugins\Subdomains\Entity\SubdomainLog;
use Plugins\Subdomains\Entity\Repository\SubdomainBlacklistRepository;
use Plugins\Subdomains\Entity\Repository\SubdomainDomainRepository;
use Plugins\Subdomains\Entity\Repository\SubdomainLogRepository;
use Plugins\Subdomains\Entity\Repository\SubdomainRepository;
use Plugins\Subdomains\Exception\CloudflareException;
use Plugins\Subdomains\Service\CloudflareService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Admin controller for subdomain management.
 */
#[Route('/admin', name: 'plugin_subdomains_admin_')]
class SubdomainAdminController extends AbstractController
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
    // Dashboard
    // =========================================================================

    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $subRepo = $this->em()->getRepository(Subdomain::class);
        $domainRepo = $this->em()->getRepository(SubdomainDomain::class);

        return $this->render('@PluginSubdomains/admin/dashboard.html.twig', [
            'stats' => $subRepo->getStats(),
            'recentSubdomains' => $subRepo->findRecent(10),
            'domains' => $domainRepo->findAll(),
        ]);
    }

    // =========================================================================
    // Settings (Cloudflare test connection)
    // =========================================================================

    #[Route('/test-connection', name: 'test_connection', methods: ['POST'])]
    public function testConnection(Request $request, CloudflareService $cloudflare): JsonResponse
    {
        $zoneId = $request->request->get('zone_id', '');

        if (empty($zoneId)) {
            return $this->json(['success' => false, 'message' => 'Zone ID is required'], 422);
        }

        try {
            $result = $cloudflare->testConnection($zoneId);
            return $this->json([
                'success' => true,
                'message' => 'Connection successful!',
                'data' => $result,
            ]);
        } catch (CloudflareException $e) {
            return $this->json(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()], 422);
        }
    }

    // =========================================================================
    // Domain Management
    // =========================================================================

    #[Route('/domains', name: 'domains', methods: ['GET'])]
    public function domains(): Response
    {
        $domainRepo = $this->em()->getRepository(SubdomainDomain::class);

        return $this->render('@PluginSubdomains/admin/domains.html.twig', [
            'domains' => $domainRepo->findBy([], ['isDefault' => 'DESC', 'domain' => 'ASC']),
        ]);
    }

    #[Route('/domains/add', name: 'domains_add', methods: ['POST'])]
    public function addDomain(Request $request): Response
    {
        $em = $this->em();
        $domainRepo = $em->getRepository(SubdomainDomain::class);

        $domainName = strtolower(trim($request->request->get('domain', '')));
        $zoneId = trim($request->request->get('cloudflare_zone_id', ''));
        $isDefault = (bool) $request->request->get('is_default', false);

        if (empty($domainName) || empty($zoneId)) {
            $this->addFlash('error', 'Domain and Zone ID are required.');
            return $this->redirectToRoute('plugin_subdomains_admin_domains');
        }

        if ($domainRepo->findOneBy(['domain' => $domainName])) {
            $this->addFlash('error', 'This domain already exists.');
            return $this->redirectToRoute('plugin_subdomains_admin_domains');
        }

        if ($isDefault) {
            $domainRepo->clearDefaults();
        }

        $domain = new SubdomainDomain();
        $domain->setDomain($domainName);
        $domain->setCloudflareZoneId($zoneId);
        $domain->setIsDefault($isDefault);

        $em->persist($domain);
        $em->flush();

        $this->addFlash('success', 'Domain added successfully.');
        return $this->redirectToRoute('plugin_subdomains_admin_domains');
    }

    #[Route('/domains/{id}/delete', name: 'domains_delete', methods: ['POST'])]
    public function deleteDomain(int $id): Response
    {
        $em = $this->em();
        $domain = $em->getRepository(SubdomainDomain::class)->find($id);

        if (!$domain) {
            $this->addFlash('error', 'Domain not found.');
            return $this->redirectToRoute('plugin_subdomains_admin_domains');
        }

        if ($domain->hasActiveSubdomains()) {
            $this->addFlash('error', 'Cannot delete domain with active subdomains.');
            return $this->redirectToRoute('plugin_subdomains_admin_domains');
        }

        $em->remove($domain);
        $em->flush();

        $this->addFlash('success', 'Domain deleted successfully.');
        return $this->redirectToRoute('plugin_subdomains_admin_domains');
    }

    // =========================================================================
    // Blacklist Quick Actions
    // =========================================================================

    #[Route('/blacklist/load-defaults', name: 'blacklist_load_defaults', methods: ['POST'])]
    public function loadDefaultBlacklist(): Response
    {
        $repo = $this->em()->getRepository(SubdomainBlacklist::class);
        $defaults = SubdomainBlacklistRepository::getDefaultBlacklist();
        $count = 0;

        foreach ($defaults as $word) {
            if ($repo->insertIfNotExists($word, 'Default blacklist')) {
                $count++;
            }
        }

        $this->addFlash('success', "Default blacklist loaded ({$count} new words added).");
        return $this->redirectToRoute('plugin_subdomains_admin_dashboard');
    }

    // =========================================================================
    // Logs Quick Actions
    // =========================================================================

    #[Route('/logs/clear', name: 'logs_clear', methods: ['POST'])]
    public function clearLogs(): Response
    {
        $this->em()->getRepository(SubdomainLog::class)->clearAll();
        $this->addFlash('success', 'All logs cleared.');
        return $this->redirectToRoute('plugin_subdomains_admin_dashboard');
    }

    // =========================================================================
    // Bulk Operations
    // =========================================================================

    #[Route('/sync', name: 'sync', methods: ['POST'])]
    public function syncDns(CloudflareService $cloudflare): Response
    {
        $subRepo = $this->em()->getRepository(Subdomain::class);
        $logRepo = $this->em()->getRepository(SubdomainLog::class);
        $subdomains = $subRepo->findActive();
        $synced = 0;
        $errors = 0;

        foreach ($subdomains as $subdomain) {
            try {
                $zoneId = $subdomain->getDomain()->getCloudflareZoneId();
                $fullName = $subdomain->getFullAddress();
                $aRecord = $cloudflare->recordExists($zoneId, $fullName, 'A');

                if (!$aRecord && $subdomain->getCloudflareARecordId()) {
                    $subdomain->setStatus(Subdomain::STATUS_ERROR);
                    $subdomain->setErrorMessage('A record not found during sync');
                    $logRepo->log('error', $subdomain, null, ['message' => 'A record missing during sync']);
                    $errors++;
                    continue;
                }
                $synced++;
            } catch (CloudflareException $e) {
                $subdomain->setStatus(Subdomain::STATUS_ERROR);
                $subdomain->setErrorMessage('Sync failed: ' . $e->getMessage());
                $errors++;
            }
        }

        $this->em()->flush();
        $type = $errors > 0 ? 'warning' : 'success';
        $this->addFlash($type, "DNS Sync: {$synced} OK, {$errors} errors.");
        return $this->redirectToRoute('plugin_subdomains_admin_dashboard');
    }

    #[Route('/export', name: 'export', methods: ['GET'])]
    public function exportSubdomains(): StreamedResponse
    {
        $subdomains = $this->em()->getRepository(Subdomain::class)->findAll();

        return new StreamedResponse(function () use ($subdomains) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Subdomain', 'Domain', 'Full Address', 'Server ID', 'User ID', 'Status', 'A Record ID', 'SRV Record ID', 'Created At']);

            foreach ($subdomains as $s) {
                fputcsv($handle, [
                    $s->getId(), $s->getSubdomain(), $s->getDomain()->getDomain(),
                    $s->getFullAddress(), $s->getServerId(), $s->getUserId(),
                    $s->getStatus(), $s->getCloudflareARecordId(), $s->getCloudflareSrvRecordId(),
                    $s->getCreatedAt()->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="subdomains_' . date('Y-m-d') . '.csv"',
        ]);
    }
}
