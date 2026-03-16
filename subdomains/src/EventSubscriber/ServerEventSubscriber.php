<?php

declare(strict_types=1);

namespace Plugins\Subdomains\EventSubscriber;

use App\Core\Service\Plugin\PluginSettingService;
use Doctrine\ORM\EntityManagerInterface;
use Plugins\Subdomains\Entity\Subdomain;
use Plugins\Subdomains\Entity\SubdomainLog;
use Plugins\Subdomains\Service\CloudflareService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles server lifecycle events for automatic DNS management.
 *
 * Listens to PteroCA server events to auto-suspend/delete DNS records
 * when servers change state.
 */
class ServerEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CloudflareService $cloudflare,
        private readonly PluginSettingService $pluginSettingService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        // PteroCA emits these events during server lifecycle.
        // Exact event class names depend on PteroCA version.
        $events = [];

        // Server suspended event
        if (class_exists('App\Core\Event\Server\ServerSuspendedEvent')) {
            $events['App\Core\Event\Server\ServerSuspendedEvent'] = 'onServerSuspended';
        }

        // Server unsuspended event
        if (class_exists('App\Core\Event\Server\ServerUnsuspendedEvent')) {
            $events['App\Core\Event\Server\ServerUnsuspendedEvent'] = 'onServerUnsuspended';
        }

        // Server deleted event
        if (class_exists('App\Core\Event\Server\ServerDeletedEvent')) {
            $events['App\Core\Event\Server\ServerDeletedEvent'] = 'onServerDeleted';
        }

        return $events;
    }

    /**
     * Auto-suspend DNS records when a server is suspended.
     */
    public function onServerSuspended(object $event): void
    {
        $autoSuspend = (bool) $this->pluginSettingService->get('subdomains', 'auto_suspend_on_suspend', true);
        if (!$autoSuspend) {
            return;
        }

        $serverId = $this->getServerIdFromEvent($event);
        if (!$serverId) {
            return;
        }

        $subdomain = $this->entityManager->getRepository(Subdomain::class)->findByServer($serverId);
        if (!$subdomain || $subdomain->getStatus() !== Subdomain::STATUS_ACTIVE) {
            return;
        }

        try {
            $zoneId = $subdomain->getDomain()->getCloudflareZoneId();
            $this->cloudflare->deleteSubdomainRecords($zoneId, $subdomain->getCloudflareARecordId(), $subdomain->getCloudflareSrvRecordId());

            $subdomain->setStatus(Subdomain::STATUS_SUSPENDED);
            $this->entityManager->flush();

            $logRepo = $this->entityManager->getRepository(SubdomainLog::class);
            $logRepo->log('suspend', $subdomain, null, [
                'reason' => 'Server suspended (auto-suspend)',
                'server_id' => $serverId,
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to suspend subdomain DNS', [
                'server_id' => $serverId,
                'error' => $e->getMessage(),
            ]);
            $subdomain->setStatus(Subdomain::STATUS_ERROR);
            $subdomain->setErrorMessage('Auto-suspend failed: ' . $e->getMessage());
            $this->entityManager->flush();
        }
    }

    /**
     * Restore DNS records when a server is unsuspended.
     */
    public function onServerUnsuspended(object $event): void
    {
        $serverId = $this->getServerIdFromEvent($event);
        if (!$serverId) {
            return;
        }

        $subdomain = $this->entityManager->getRepository(Subdomain::class)->findByServer($serverId);
        if (!$subdomain || $subdomain->getStatus() !== Subdomain::STATUS_SUSPENDED) {
            return;
        }

        try {
            $server = $this->getServerFromEvent($event);
            $zoneId = $subdomain->getDomain()->getCloudflareZoneId();
            $ttl = (int) $this->pluginSettingService->get('subdomains', 'default_ttl', 1);
            $serverIp = method_exists($server, 'getIp') ? ($server->getIp() ?? '0.0.0.0') : '0.0.0.0';
            $serverPort = method_exists($server, 'getPort') ? ((int) ($server->getPort() ?? 25565)) : 25565;

            $records = $this->cloudflare->createSubdomainRecords(
                $zoneId, $subdomain->getSubdomain(), $subdomain->getDomain()->getDomain(), $serverIp, $serverPort, $ttl
            );

            $subdomain->setStatus(Subdomain::STATUS_ACTIVE);
            $subdomain->setErrorMessage(null);
            $subdomain->setCloudflareARecordId($records['a_record_id']);
            $subdomain->setCloudflareSrvRecordId($records['srv_record_id']);
            $this->entityManager->flush();

            $logRepo = $this->entityManager->getRepository(SubdomainLog::class);
            $logRepo->log('unsuspend', $subdomain, null, [
                'reason' => 'Server unsuspended (auto-restore)',
                'server_id' => $serverId,
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to unsuspend subdomain DNS', [
                'server_id' => $serverId,
                'error' => $e->getMessage(),
            ]);
            $subdomain->setStatus(Subdomain::STATUS_ERROR);
            $subdomain->setErrorMessage('Auto-unsuspend failed: ' . $e->getMessage());
            $this->entityManager->flush();
        }
    }

    /**
     * Delete DNS records when a server is terminated.
     */
    public function onServerDeleted(object $event): void
    {
        $autoDelete = (bool) $this->pluginSettingService->get('subdomains', 'auto_delete_on_terminate', true);
        if (!$autoDelete) {
            return;
        }

        $serverId = $this->getServerIdFromEvent($event);
        if (!$serverId) {
            return;
        }

        $subdomain = $this->entityManager->getRepository(Subdomain::class)->findByServer($serverId);
        if (!$subdomain) {
            return;
        }

        try {
            $zoneId = $subdomain->getDomain()->getCloudflareZoneId();
            $this->cloudflare->deleteSubdomainRecords($zoneId, $subdomain->getCloudflareARecordId(), $subdomain->getCloudflareSrvRecordId());
        } catch (\Exception $e) {
            $this->logger->warning('Failed to delete DNS records on server termination', [
                'server_id' => $serverId,
                'error' => $e->getMessage(),
            ]);
        }

        $logRepo = $this->entityManager->getRepository(SubdomainLog::class);
        $logRepo->log('delete', $subdomain, null, [
            'reason' => 'Server terminated (auto-delete)',
            'server_id' => $serverId,
            'subdomain' => $subdomain->getFullAddress(),
        ]);

        $this->entityManager->remove($subdomain);
        $this->entityManager->flush();
    }

    /**
     * Extract server ID from event object.
     */
    private function getServerIdFromEvent(object $event): ?int
    {
        if (method_exists($event, 'getServer')) {
            $server = $event->getServer();
            return method_exists($server, 'getId') ? $server->getId() : null;
        }
        if (method_exists($event, 'getServerId')) {
            return $event->getServerId();
        }
        return null;
    }

    /**
     * Extract server object from event.
     */
    private function getServerFromEvent(object $event): ?object
    {
        return method_exists($event, 'getServer') ? $event->getServer() : null;
    }
}
