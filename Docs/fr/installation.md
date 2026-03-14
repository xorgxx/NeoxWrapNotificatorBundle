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

## Notifier : installation des bridges (Slack, Telegram, Discord, ...)

Le canal **Chat** du bundle repose sur **Symfony Notifier**.

- Le bundle construit un `ChatMessage` et le transmet à `ChatterInterface`.
- Le transport réel (Slack, Telegram, Discord, etc.) est fourni par un **bridge Symfony** installé dans votre projet.
- Une fois le bridge installé et un DSN configuré, `notifyChat()` utilise simplement `transport` (ex: `slack`, `telegram`, `discord`) et Symfony se charge du reste.

### Slack

```bash
composer require symfony/slack-notifier
```

Exemple `.env` :

```env
SLACK_DSN=slack://xoxb-***@default?channel=my-channel
```

### Telegram

```bash
composer require symfony/telegram-notifier
```

Exemple `.env` :

```env
TELEGRAM_DSN=telegram://bot-token@default?channel=@my_channel
```

### Discord

```bash
composer require symfony/discord-notifier
```

Exemple `.env` (webhook) :

```env
DISCORD_DSN=discord://TOKEN@default?webhook_id=ID
```

Exemple `.env` (bot) :

```env
DISCORD_DSN=discord+bot://BOT_TOKEN@default
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
