# Documentation de WrapNotificatorBundle

Un bundle Symfony 7.3 / PHP 8.3 pour unifier et simplifier l’envoi de notifications via Mailer, Notifier (SMS/Chat), Mercure (browser) et Web Push, avec une UX front moderne (toasts) et des fonctionnalités avancées (idempotence, corrélation, envoi différé async).

## Présentation
Fournit une façade `NotifierFacade`, un `MessageFactory` et un `TypedSender` pour adresser Email, SMS/Chat, Mercure (navigateur) et Web Push avec une API simple et des statuts normalisés (`DeliveryStatus`).

### Fonctionnalités clés
- **Écouteurs Mercure** injectables en Twig avec UI moderne (toasts, pause au survol, icônes, thème clair/sombre, CSS externe par défaut).
- **Idempotence** (déduplication) et corrélation via `DeliveryContext`.
- **Envoi différé** (date/heure) en mode asynchrone via Symfony Messenger.
- **Système Plug & Play** complet incluant DTO, Validation, Formulaires automatiques et Widget Twig.

## Sommaire
- [Installation](installation.md)
- [Configuration](configuration.md)
- [Utilisation](usage.md)
- [Système Plug & Play (DTO & Formulaires)](plug-and-play.md)
- [Widget Twig](twig-widget.md)
- [Fonctionnalités avancées (Async, Déduplication)](advanced.md)
- [Diagnostic (Mercure & Messenger)](advanced.md#diagnostic)
- [Tests & Qualité](about.md#tests--qualité)
- [Licence](about.md#licence)
