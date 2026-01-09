# Installation

## Prérequis
- PHP 8.3+
- Symfony 7.3+
- Optionnels :
  - Mailer, Notifier (Chatter/Texter), Mercure Hub, Web Push (minishlink/web-push)

## Installation rapide

```bash
composer require xorgxx/wrap-notificator-bundle
composer require symfony/mailer symfony/notifier symfony/mercure-bundle minishlink/web-push
```

Si l’auto‑découverte n’est pas active :

```php
// config/bundles.php
return [
    Neox\WrapNotificatorBundle\WrapNotificatorBundle::class => ['all' => true],
];
```

## Publication des assets

Pour utiliser les CSS des toasts par défaut, publiez les assets :

```bash
php bin/console assets:install --symlink --relative
```
