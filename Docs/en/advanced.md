# Advanced Features

## Deferred delivery (async)

You can schedule delivery at a future date/time using Symfony Messenger (worker required).

```php
use Neox\WrapNotificatorBundle\Notification\DeliveryContext;

$ctx = DeliveryContext::create(deferAt: new \DateTimeImmutable('+15 minutes'));
$facade->notifySms('Reminder in 15min', '+33600000000', [], $ctx);
```

### Accepted Formats (CLI)
- `--send-at`: ISO 8601 or `Y-m-d H:i`.
- `--in`: ISO 8601 `PT10M`, or short formats `15m`, `2h`, `1d`, `1h30m`.

Via CLI:
```bash
php bin/console notify:send --channel=sms --to=+33600000000 --text="Reminder" --in=15m
```

## Correlation & Idempotency

`DeliveryContext` allows managing deduplication to avoid duplicate sends.

```php
// Stable business key (prevents duplicates for 15min)
$ctx = DeliveryContext::for('reminder:user:42:2025-12-01', ttlSeconds: 900);
$facade->notifyEmail('Reminder', 'Hello', 'user@example.com', true, [], [], $ctx);
```

## Force delivery mode (transport)

You can force the Messenger transport (e.g., `asyncRabbitMq` or `sync`) for a specific notification, without changing global configuration.

```php
use Neox\WrapNotificatorBundle\Notification\DeliveryContext;

// 1) Force ASYNC (queue) on RabbitMQ
$ctx = DeliveryContext::create(viaTransport: 'asyncRabbitMq');
$facade->notifySms('Ping async', '+33600000000', [], $ctx);

// 2) Force SYNCHRONOUS (immediate)
$ctx = DeliveryContext::create(viaTransport: 'sync');
$facade->notifyChat('slack', 'Immediate', 'Urgent', ['channel' => 'ops'], [], $ctx);
```

**Notes:**
- Requires Messenger to force a transport. If the bus is unavailable, delivery will fail.
- `viaTransport: 'sync'` executes delivery in the current process (no automatic retry).
- Global routing remains the default value if `viaTransport` is not provided.

## Diagnostics

A command is available to test Mercure and Messenger connectivity:

```bash
php bin/console wrap:notificator:diagnose
```

## Logging & Status Notification

### Notification Logging
By default, if logging is enabled, the bundle uses standard Symfony logs (via Monolog).

1. Enable logging:
```yaml
wrap_notificator:
  logging:
    enabled: true
```
Notifications will then be visible in your log files (e.g., `var/log/dev.log`) with the `[WrapNotificator]` prefix.

### Custom Logging (Database)
To record notifications in a database, you must implement the `NotificationLoggerInterface`.

1. Create your service:
```php
namespace App\Service;

use Neox\WrapNotificatorBundle\Contract\NotificationLoggerInterface;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;

class MyNotificationLogger implements NotificationLoggerInterface
{
    public function log(DeliveryStatus $status): void
    {
        // Save $status->toArray() to your database
    }
}
```

### Status Notification via Mercure
If `mercure.notify_status` is enabled, the bundle will automatically publish a Mercure `Update` for each send.

The topic used is either:
- The `correlationId` if present in the `DeliveryContext`.
- The default topic `wrap_notificator/status`.

```php
$ctx = DeliveryContext::create(correlationId: 'user-unique-id');
$facade->send($dto, [], $ctx);
// An update will be published to the "user-unique-id" topic
```

## Live Flash (no refresh)

The bundle can publish Symfony flash messages through Mercure on `kernel.response`, so they can be displayed instantly in the browser (toasts) without reloading the page.

### Global enable

```yaml
wrap_notificator:
  live_flash:
    enabled: true
```

### Group multiple flash messages into a single popup

By default, each flash message is published and displayed as a distinct notification.

If you want to group all `FlashBag` messages into a single popup (as a list), enable:

```yaml
wrap_notificator:
  live_flash:
    group_messages: true
```

### Selective enable/disable with attribute

You can enable/disable locally with the `#[LiveFlash]` attribute (class or method):

```php
use Neox\WrapNotificatorBundle\Attribute\LiveFlash;

#[LiveFlash]
final class AdminController
{
    #[LiveFlash(enabled: false)]
    public function export(): Response
    {
        // ...
    }
}
```

### Mercure topic and Twig listener

By default, the topic is `wrap_notificator/flash/{sessionId}`. In your layout:

```twig
{{ wrap_notify_bootstrap() }}
{{ wrap_notify_browser(['wrap_notificator/flash/' ~ app.session.id]) }}
```

### SweetAlert2 rendering (toast) when available

If SweetAlert2 is loaded on the page (presence of `window.Swal.fire`), the bundle will automatically render notifications (Mercure and Live Flash) using **SweetAlert2 in toast mode**.

Otherwise, it falls back to **Bootstrap toasts**.

## Quick Troubleshooting

- **No toast showing?** Ensure `wrap_notify_bootstrap()` is included, `wrap_notificator.mercure.enabled=true`, and the Mercure public URL is correct.
- **No styles?** Check that assets are installed (`assets:install`).
- **Deferred (async) not working?** Make sure a Messenger worker is running and your transport supports `DelayStamp`.
