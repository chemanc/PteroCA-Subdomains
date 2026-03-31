<?php

declare(strict_types=1);

namespace Plugins\Subdomains\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Plugins\Subdomains\Entity\Subdomain;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Injects a small inline JS into HTML responses to replace server IPs
 * with subdomain addresses across all panel pages.
 */
class AddressReplacerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -128], // Low priority, run last
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Skip AJAX requests (tab content loaded via fetch)
        if ($request->isXmlHttpRequest()) {
            return;
        }

        $contentType = $response->headers->get('Content-Type', '');

        // Only process HTML responses
        if (!str_contains($contentType, 'text/html')) {
            return;
        }

        $content = $response->getContent();
        if (!$content || !str_contains($content, '</body>')) {
            return;
        }

        // Only if page has data-ip elements (server pages)
        if (!str_contains($content, 'data-ip')) {
            return;
        }

        // Get current user
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        if (!$user || !method_exists($user, 'getId')) {
            return;
        }

        // Get user's active subdomains with server info
        try {
            $subdomains = $this->entityManager->getRepository(Subdomain::class)
                ->findBy(['userId' => $user->getId(), 'status' => Subdomain::STATUS_ACTIVE]);
        } catch (\Exception $e) {
            return; // Silently fail
        }

        if (empty($subdomains)) {
            return;
        }

        // Build maps: server_id => address AND pterodactyl_identifier => address
        $map = [];
        $identifierMap = [];
        foreach ($subdomains as $sub) {
            $map[$sub->getServerId()] = $sub->getFullAddress();
            // Also get the pterodactyl identifier for server detail page matching
            try {
                $server = $this->entityManager->getRepository(\App\Core\Entity\Server::class)
                    ->find($sub->getServerId());
                if ($server) {
                    $identifierMap[$server->getPterodactylServerIdentifier()] = $sub->getFullAddress();
                }
            } catch (\Exception $e) {
                // Skip if server not found
            }
        }

        $jsonMap = json_encode($map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
        $jsonIdMap = json_encode($identifierMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

        // m = {db_server_id: address}, im = {pterodactyl_identifier: address}
        $script = <<<JS
<script data-subdomain-replacer>
(function(){
var m=$jsonMap,im=$jsonIdMap;
function r(){
// 1. Dashboard & server list: elements inside [data-server-id] containers
document.querySelectorAll('[data-server-id]').forEach(function(card){
var sid=card.getAttribute('data-server-id');
if(!m[sid])return;
var ipEl=card.querySelector('[data-ip]');
if(!ipEl||ipEl.getAttribute('data-sub-replaced'))return;
var t=ipEl.textContent.trim();
if(!t||ipEl.classList.contains('placeholder'))return;
ipEl.textContent=m[sid];ipEl.setAttribute('data-sub-replaced','1');
});
// 2. Server detail page: match by pterodactyl identifier in URL
var p=new URLSearchParams(window.location.search);
if(p.get('routeName')==='server'&&p.get('id')){
var pid=p.get('id');
if(im[pid]){
document.querySelectorAll('[data-ip]').forEach(function(e){
if(e.getAttribute('data-sub-replaced'))return;
var t=e.textContent.trim();
if(!t||e.classList.contains('placeholder'))return;
e.textContent=im[pid];e.setAttribute('data-sub-replaced','1');
});}}}
var o=new MutationObserver(function(){setTimeout(r,200);});
o.observe(document.querySelector('main')||document.body,{childList:true,subtree:true,characterData:true});
setTimeout(r,500);setTimeout(r,2000);setTimeout(r,5000);
})();
</script>
JS;

        $content = str_replace('</body>', $script . '</body>', $content);
        $response->setContent($content);
    }
}
