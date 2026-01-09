# Installation

## Requirements
- PHP 8.3+
- Symfony 7.3+
- Optional services:
  - Mailer, Notifier (Chatter/Texter), Mercure Hub, Web Push (minishlink/web-push)

## Quick Start

```bash
composer require xorgxx/wrap-notificator-bundle
composer require symfony/mailer symfony/notifier symfony/mercure-bundle minishlink/web-push
```

If auto-discovery is disabled:

```php
// config/bundles.php
return [
    Neox\WrapNotificatorBundle\WrapNotificatorBundle::class => ['all' => true],
];
```

## Publish assets

To use default toast styles, publish assets:

```bash
php bin/console assets:install --symlink --relative
```
