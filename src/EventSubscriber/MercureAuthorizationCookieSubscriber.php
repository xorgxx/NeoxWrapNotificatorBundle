<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\EventSubscriber;

use Neox\WrapNotificatorBundle\Contract\MercureGrantProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Pose le cookie d'autorisation abonné Mercure (`mercureAuthorization`)
 * à chaque réponse d'un utilisateur authentifié, et demande son
 * expiration quand le navigateur arrive anonyme avec un cookie résiduel
 * (logout, expiration de session).
 *
 * Grants natifs du bundle : `mercure.default_topics` (topics publics du
 * JS) + le canal flash live `{default_topic_prefix}/{sessionId}`. Les
 * grants métier viennent des services tagués
 * `wrap_notificator.mercure_grant_provider`
 * ({@see MercureGrantProviderInterface}).
 *
 * Priorité > SetCookieSubscriber du mercure-bundle (0) : l'attribut
 * `_mercure_authorization_cookies` doit être posé avant qu'il ne soit
 * transformé en en-tête Set-Cookie.
 *
 * Le cookie n'a de sens que si le hub est joint same-origin (proxy
 * `/.well-known/mercure` ou même domaine) — sinon
 * Authorization::setCookie lève une RuntimeException sur le domaine.
 */
final class MercureAuthorizationCookieSubscriber implements EventSubscriberInterface
{
    /**
     * @param iterable<MercureGrantProviderInterface> $grantProviders
     */
    public function __construct(
        private readonly ?Authorization $authorization,
        private readonly ?HubInterface $hub,
        private readonly ?TokenStorageInterface $tokenStorage,
        private readonly iterable $grantProviders,
        private readonly array $mercureConfig,
        private readonly array $liveFlashConfig,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['onResponse', 10]];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || null === $this->authorization || null === $this->hub) {
            return;
        }

        $request = $event->getRequest();
        $cookieName = $this->hub->getCookieName();

        if (null === $this->tokenStorage?->getToken()?->getUser()) {
            // Déconnexion : le navigateur présente encore le cookie →
            // on demande son expiration (Set-Cookie passé en même temps).
            if ($request->cookies->has($cookieName)) {
                try {
                    $this->authorization->clearCookie($request);
                } catch (RuntimeException) {
                    // Domaine public_url incompatible avec l'hôte requis — rien à purger.
                }
            }

            return;
        }

        $topics = [];
        $grants = [];

        // Topics natifs du bundle : canaux publics souscrits par le JS
        // + canal flash live spécifique à la session.
        foreach ((array) ($this->mercureConfig['default_topics'] ?? []) as $topic) {
            $topics[(string) $topic] = true;
        }

        if ($request->hasSession() && ($session = $request->getSession())->isStarted()) {
            $prefix = (string) ($this->liveFlashConfig['default_topic_prefix'] ?? 'wrap_notificator/flash');
            $topics[rtrim($prefix, '/').'/'.$session->getId()] = true;
        }

        // Grants applicatifs : chaque provider autoconfigure-tagué
        // contribue ses topics métier (compagnies, identité…).
        foreach ($this->grantProviders as $provider) {
            foreach ($provider->getGrants($request) as $grant) {
                if ($grant instanceof Grant) {
                    $grants[] = $grant;
                } else {
                    $topics[(string) $grant] = true;
                }
            }
        }

        try {
            $this->authorization->setCookie($request, [...array_keys($topics), ...$grants]);
        } catch (\Throwable $e) {
            // Hôte incompatible avec public_url ou autre erreur :
            // le privé est désactivé pour cette requête, pas de 500.
            ($this->logger ?? new NullLogger())->warning('WrapNotificator: Mercure subscriber cookie non posé — {class}: {msg}', [
                'class' => $e::class,
                'msg' => $e->getMessage(),
            ]);
        }
    }
}
