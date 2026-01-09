# Fonctionnalités avancées

## Envoi différé (async)

Vous pouvez planifier un envoi à une date/heure future via Symfony Messenger (un worker est requis).

```php
use Neox\WrapNotificatorBundle\Notification\DeliveryContext;

$ctx = DeliveryContext::create(deferAt: new \DateTimeImmutable('+15 minutes'));
$facade->notifySms('Rappel dans 15min', '+33600000000', [], $ctx);
```

### Formats acceptés (CLI)
- `--send-at` : ISO 8601 ou `Y-m-d H:i`.
- `--in` : ISO 8601 `PT10M`, ou formats courts `15m`, `2h`, `1d`, `1h30m`.

Via le CLI :
```bash
php bin/console notify:send --channel=sms --to=+33600000000 --text="Rappel" --in=15m
```

## Corrélation & Idempotence

Le `DeliveryContext` permet de gérer la déduplication pour éviter les envois en double.

```php
// Clé métier stable (empêche les doublons pendant 15min)
$ctx = DeliveryContext::for('reminder:user:42:2025-12-01', ttlSeconds: 900);
$facade->notifyEmail('Rappel', 'Bonjour', 'user@example.com', true, [], [], $ctx);
```

## Forcer le mode d'envoi (transport)

Vous pouvez forcer le transport Messenger (ex: `asyncRabbitMq` ou `sync`) pour une notification spécifique, sans changer la configuration globale.

```php
use Neox\WrapNotificatorBundle\Notification\DeliveryContext;

// 1) Forcer l'ASYNC (file) sur RabbitMQ
$ctx = DeliveryContext::create(viaTransport: 'asyncRabbitMq');
$facade->notifySms('Ping async', '+33600000000', [], $ctx);

// 2) Forcer le SYNCHRONE (immédiat)
$ctx = DeliveryContext::create(viaTransport: 'sync');
$facade->notifyChat('slack', 'Immédiat', 'Urgent', ['channel' => 'ops'], [], $ctx);
```

**Notes :**
- Requiert Messenger pour forcer un transport. Si le bus est indisponible, l'envoi échouera.
- `viaTransport: 'sync'` exécute l'envoi dans le processus courant (pas de retry automatique).
- Le routage global reste la valeur par défaut si `viaTransport` n'est pas fourni.

## Diagnostic

Une commande est disponible pour tester la connectivité Mercure et Messenger :

```bash
php bin/console wrap:notificator:diagnose
```

## Logging & Notification de statut

### Logging des notifications
Par défaut, si le logging est activé, le bundle utilise les logs standard de Symfony (via Monolog).

1. Activez le logging :
```yaml
wrap_notificator:
  logging:
    enabled: true
```
Les notifications seront alors visibles dans vos fichiers de logs (ex: `var/log/dev.log`) avec le préfixe `[WrapNotificator]`.

### Logging personnalisé (Base de données)
Pour enregistrer les notifications dans une base de données, vous devez implémenter l'interface `NotificationLoggerInterface`.

1. Créez votre service :
```php
namespace App\Service;

use Neox\WrapNotificatorBundle\Contract\NotificationLoggerInterface;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;

class MyNotificationLogger implements NotificationLoggerInterface
{
    public function log(DeliveryStatus $status): void
    {
        // Enregistrez $status->toArray() dans votre base de données
    }
}
```

### Notification de statut via Mercure
Si `mercure.notify_status` est activé, le bundle publiera automatiquement un `Update` Mercure pour chaque envoi.

Le topic utilisé est soit :
- Le `correlationId` s'il est présent dans le `DeliveryContext`.
- Le topic par défaut `wrap_notificator/status`.

```php
$ctx = DeliveryContext::create(correlationId: 'user-unique-id');
$facade->send($dto, [], $ctx);
// Un update sera publié sur le topic "user-unique-id"
```

## Dépannage rapide

- **Aucun toast ?** Vérifiez `wrap_notify_bootstrap()`, `wrap_notificator.mercure.enabled=true`, et l’URL publique Mercure.
- **Pas de styles ?** Vérifiez que les assets sont installés (`assets:install`).
- **Différé (async) ne fonctionne pas ?** Assurez‑vous qu’un worker Messenger tourne et que le transport supporte `DelayStamp`.
