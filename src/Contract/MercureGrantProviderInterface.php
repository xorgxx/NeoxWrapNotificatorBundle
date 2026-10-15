<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Contract;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Fournit des grants d'abonnement Mercure propres à l'application
 * (topics métier : compagnies, identité utilisateur, canaux privés…).
 *
 * Chaque implémentation est taguée automatiquement et consultée par
 * {@see \Neox\WrapNotificatorBundle\EventSubscriber\MercureAuthorizationCookieSubscriber}
 * pour composer le JWT du cookie abonné. Le bundle ne connaît pas les
 * topics métier — il contribue seulement les siens (default_topics,
 * canal flash live) ; tout le reste vient des providers.
 *
 * getGrants() est appelé à chaque réponse HTTP d'un utilisateur
 * authentifié : les grants doivent être recalculés à chaque appel
 * (pas de cache) pour couvrir les bascules de contexte.
 */
#[AutoconfigureTag('wrap_notificator.mercure_grant_provider')]
interface MercureGrantProviderInterface
{
    /**
     * Topics autorisés pour l'utilisateur courant.
     *
     * @return iterable<string>
     */
    public function getGrants(Request $request): iterable;
}
