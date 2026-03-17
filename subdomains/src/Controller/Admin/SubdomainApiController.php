<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Plugins\Subdomains\Entity\Subdomain;
use Plugins\Subdomains\Entity\SubdomainBlacklist;
use Plugins\Subdomains\Entity\SubdomainDomain;
use Plugins\Subdomains\Entity\SubdomainLog;
use Plugins\Subdomains\Entity\Repository\SubdomainBlacklistRepository;
use Plugins\Subdomains\Exception\CloudflareException;
use Plugins\Subdomains\Service\CloudflareService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Handles admin API endpoints (form submissions, AJAX, exports).
 * These are standard Symfony routes, not EasyAdmin CRUD actions.
 * Requires ROLE_ADMIN for all endpoints.
 */
#[Route('/admin/subdomains-api', name: 'plugin_subdomains_api_')]
#[IsGranted('ROLE_ADMIN')]
class SubdomainApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    private function redirectToDashboard(): RedirectResponse
    {
        return $this->redirect(
            $this->adminUrlGenerator
                ->unsetAll()
                ->setController(SubdomainCrudController::class)
                ->setAction('index')
                ->generateUrl()
        );
    }

    private function redirectToDomains(): RedirectResponse
    {
        return $this->redirect(
            $this->adminUrlGenerator
                ->unsetAll()
                ->setController(DomainCrudController::class)
                ->setAction('index')
                ->generateUrl()
        );
    }

    // =========================================================================
    // Cloudflare Test Connection
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

    #[Route('/domains/add', name: 'domains_add', methods: ['POST'])]
    public function addDomain(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin_action', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToDomains();
        }

        $domainRepo = $this->entityManager->getRepository(SubdomainDomain::class);

        $domainName = strtolower(trim($request->request->get('domain', '')));
        $zoneId = trim($request->request->get('cloudflare_zone_id', ''));
        $isDefault = (bool) $request->request->get('is_default', false);

        if (empty($domainName) || empty($zoneId)) {
            $this->addFlash('error', 'Domain and Zone ID are required.');
            return $this->redirectToDomains();
        }

        // Validate domain format
        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*\.[a-z]{2,}$/', $domainName)) {
            $this->addFlash('error', 'Invalid domain format.');
            return $this->redirectToDomains();
        }

        // Validate zone ID format (32-char hex)
        if (!preg_match('/^[a-f0-9]{32}$/', $zoneId)) {
            $this->addFlash('error', 'Invalid Zone ID format.');
            return $this->redirectToDomains();
        }

        if ($domainRepo->findOneBy(['domain' => $domainName])) {
            $this->addFlash('error', 'This domain already exists.');
            return $this->redirectToDomains();
        }

        if ($isDefault) {
            $domainRepo->clearDefaults();
        }

        $domain = new SubdomainDomain();
        $domain->setDomain($domainName);
        $domain->setCloudflareZoneId($zoneId);
        $domain->setIsDefault($isDefault);

        $this->entityManager->persist($domain);
        $this->entityManager->flush();

        $this->addFlash('success', 'Domain added successfully.');
        return $this->redirectToDomains();
    }

    #[Route('/domains/{id}/delete', name: 'domains_delete', methods: ['POST'])]
    public function deleteDomain(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin_action', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToDomains();
        }

        $domain = $this->entityManager->getRepository(SubdomainDomain::class)->find($id);

        if (!$domain) {
            $this->addFlash('error', 'Domain not found.');
            return $this->redirectToDomains();
        }

        if ($domain->hasActiveSubdomains()) {
            $this->addFlash('error', 'Cannot delete domain with active subdomains.');
            return $this->redirectToDomains();
        }

        $this->entityManager->remove($domain);
        $this->entityManager->flush();

        $this->addFlash('success', 'Domain deleted successfully.');
        return $this->redirectToDomains();
    }

    // =========================================================================
    // Blacklist Quick Actions
    // =========================================================================

    #[Route('/blacklist/load-defaults', name: 'blacklist_load_defaults', methods: ['POST'])]
    public function loadDefaultBlacklist(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin_action', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToDashboard();
        }

        $repo = $this->entityManager->getRepository(SubdomainBlacklist::class);
        $defaults = SubdomainBlacklistRepository::getDefaultBlacklist();
        $count = 0;

        foreach ($defaults as $word) {
            if ($repo->insertIfNotExists($word, 'Default blacklist')) {
                $count++;
            }
        }

        $this->addFlash('success', "Default blacklist loaded ({$count} new words added).");
        return $this->redirectToDashboard();
    }

    // =========================================================================
    // Logs Quick Actions
    // =========================================================================

    #[Route('/logs/clear', name: 'logs_clear', methods: ['POST'])]
    public function clearLogs(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin_action', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToDashboard();
        }

        $this->entityManager->getRepository(SubdomainLog::class)->clearAll();
        $this->addFlash('success', 'All logs cleared.');
        return $this->redirectToDashboard();
    }

    // =========================================================================
    // Bulk Operations
    // =========================================================================

    #[Route('/sync', name: 'sync', methods: ['POST'])]
    public function syncDns(Request $request, CloudflareService $cloudflare): Response
    {
        if (!$this->isCsrfTokenValid('admin_action', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToDashboard();
        }

        $subRepo = $this->entityManager->getRepository(Subdomain::class);
        $logRepo = $this->entityManager->getRepository(SubdomainLog::class);
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

        $this->entityManager->flush();
        $type = $errors > 0 ? 'warning' : 'success';
        $this->addFlash($type, "DNS Sync: {$synced} OK, {$errors} errors.");
        return $this->redirectToDashboard();
    }

    #[Route('/export', name: 'export', methods: ['GET'])]
    public function exportSubdomains(): StreamedResponse
    {
        $subdomains = $this->entityManager->getRepository(Subdomain::class)->findAll();

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
