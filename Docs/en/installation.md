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

## Notifier: install bridges (Slack, Telegram, Discord, ...)

The bundle **Chat** channel relies on **Symfony Notifier**.

- The bundle builds a `ChatMessage` and sends it through `ChatterInterface`.
- The actual transport (Slack, Telegram, Discord, ...) is provided by a **Symfony bridge** installed in your project.
- Once the bridge is installed and a DSN is configured, `notifyChat()` only needs the transport name (e.g. `slack`, `telegram`, `discord`) and Symfony handles the rest.

### Slack

```bash
composer require symfony/slack-notifier
```

Example `.env`:

```env
SLACK_DSN=slack://xoxb-***@default?channel=my-channel
```

### Telegram

```bash
composer require symfony/telegram-notifier
```

Example `.env`:

```env
TELEGRAM_DSN=telegram://bot-token@default?channel=@my_channel
```

### Discord

```bash
composer require symfony/discord-notifier
```

Example `.env` (webhook):

```env
DISCORD_DSN=discord://TOKEN@default?webhook_id=ID
```

Example `.env` (bot):

```env
DISCORD_DSN=discord+bot://BOT_TOKEN@default
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
