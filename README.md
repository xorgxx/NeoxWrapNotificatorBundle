# WrapNotificatorBundle

[![Tests](https://github.com/xorgxx/WrapNotificatorBundle/actions/workflows/ci.yml/badge.svg)](https://github.com/xorgxx/WrapNotificatorBundle/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Un bundle Symfony 7.3 / PHP 8.3 pour unifier et simplifier l’envoi de notifications via Mailer, Notifier (SMS/Chat), Mercure (browser) et Web Push, avec une UX front moderne (toasts) et des fonctionnalités avancées (idempotence, corrélation, envoi différé async).

---

## 📚 Documentation

The documentation is available in several languages:

- [**Français**](Docs/fr/index.md)
- [**English**](Docs/en/index.md)

### Sommaire / Summary

1. [**Installation**](Docs/en/installation.md) ([FR](Docs/fr/installation.md))
2. [**Configuration**](Docs/en/configuration.md) ([FR](Docs/fr/configuration.md))
3. [**Usage**](Docs/en/usage.md) ([FR](Docs/fr/usage.md))
4. [**Plug & Play System**](Docs/en/plug-and-play.md) ([FR](Docs/fr/plug-and-play.md))
5. [**Advanced Features**](Docs/en/advanced.md) ([FR](Docs/fr/advanced.md))

---

## 🚀 Quick Start

```bash
composer require xorgxx/wrap-notificator-bundle
```

```php
// Send an email in 1 line
$facade->notifyEmail('Welcome', '<h1>Hello</h1>', 'user@example.com');

// Send a browser toast via Mercure
$facade->notifyBrowser('users:42', ['title' => 'Hello', 'body' => 'Welcome 👋', 'level' => 'success']);
```

---

## 🛠️ Requirements

- PHP 8.3+
- Symfony 7.3+

---

## 📄 License

MIT. See [LICENSE](LICENSE) for details.
