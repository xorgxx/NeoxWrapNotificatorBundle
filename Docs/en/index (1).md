# WrapNotificatorBundle Documentation

A Symfony 7.3 / PHP 8.3 bundle to unify Mailer, Notifier (SMS/Chat), Mercure (browser) and Web Push with a modern front‑end UX (toasts) and advanced features (idempotency, correlation, deferred async send).

## Overview
Provides `NotifierFacade`, a `MessageFactory` and a `TypedSender` to send Email, SMS/Chat, Mercure (browser) and Web Push with a simple API and normalized `DeliveryStatus`.

### Key features
- **Mercure Listeners** injectable in Twig with modern UI (toasts, hover-to-pause, icons, light/dark theme, external CSS by default).
- **Idempotency** (deduplication) and correlation through `DeliveryContext`.
- **Deferred delivery** (date/time) in async mode using Symfony Messenger.
- **Plug & Play System** with DTOs, automatic form generation, and a Twig widget.

## Summary
- [Installation](installation.md)
- [Configuration](configuration.md)
- [Usage](usage.md)
- [Plug & Play System (DTO & Forms)](plug-and-play.md)
- [Twig Widget](twig-widget.md)
- [Advanced Features (Async, Dedupe)](advanced.md)
- [Diagnostic (Mercure & Messenger)](advanced.md#diagnostics)
- [Tests & Quality](about.md#tests--quality)
- [License](about.md#license)
