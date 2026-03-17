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

        $response = $event->getResponse();
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

        // Get user's active subdomains
        try {
            $subdomains = $this->entityManager->getRepository(Subdomain::class)
                ->findBy(['userId' => $user->getId(), 'status' => Subdomain::STATUS_ACTIVE]);
        } catch (\Exception $e) {
            return; // Silently fail
        }

        if (empty($subdomains)) {
            return;
        }

        // Build server_id => address map
        $map = [];
        foreach ($subdomains as $sub) {
            $map[$sub->getServerId()] = $sub->getFullAddress();
        }

        $jsonMap = json_encode($map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

        $script = <<<JS
<script data-subdomain-replacer>
(function(){
var m=$jsonMap;
function r(){document.querySelectorAll('[data-ip]').forEach(function(e){
if(e.getAttribute('data-sub-replaced'))return;
var t=e.textContent.trim();
if(!t||e.classList.contains('placeholder'))return;
var ip=t.split(':')[0];
for(var sid in m){
var found=false;
var card=e.closest('[data-server-id]');
if(card&&card.getAttribute('data-server-id')==sid){found=true;}
if(!found){var row=e.closest('tr,div,.card,[class*=server]');
if(row&&row.textContent.indexOf(ip)>-1){found=true;}}
if(found){e.textContent=m[sid];e.setAttribute('data-sub-replaced','1');break;}}});}
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
