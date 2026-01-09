# CHANGELOG

All notable changes to this project will be documented in this file.

## [1.3.1] - 2026-01-09
- EN — Documentation and examples update:
    - Added examples for Plug & Play forms and Twig widgets in `examples/`.
    - Updated `usage.md` and `twig-widget.md` with dynamic form usage and `wrap_notify_form()` Twig function.
    - Added `notify_widgets` route in example controller.
- FR — Mise à jour de la documentation et des exemples :
    - Ajout d'exemples pour les formulaires Plug & Play et les widgets Twig dans `examples/`.
    - Mise à jour de `usage.md` et `twig-widget.md` avec l'utilisation des formulaires dynamiques et de la fonction Twig `wrap_notify_form()`.
    - Ajout de la route `notify_widgets` dans le contrôleur d'exemple.

## [1.3.0] - 2026-01-08
- EN — Added logging and delivery status notifications:
    - New `NotificationLoggerInterface` for recording notifications.
    - Added default `PsrNotificationLogger` (logs to standard Symfony/Monolog logs).
    - Toggleable database logging via `wrap_notificator.logging.enabled`.
    - Toggleable delivery status notifications via Mercure (`wrap_notificator.mercure.notify_status`).
    - Integrated logging and status notification into `NotifierFacade::finalize()`.
- FR — Ajout du logging et des notifications de statut de livraison :
    - Nouvelle interface `NotificationLoggerInterface` pour l'enregistrement des notifications.
    - Ajout de `PsrNotificationLogger` par défaut (écrit dans les logs standard Symfony/Monolog).
    - Logging activable via `wrap_notificator.logging.enabled`.
    - Notifications de statut de livraison activables via Mercure (`wrap_notificator.mercure.notify_status`).
    - Intégration du logging et de la notification de statut dans `NotifierFacade::finalize()`.

## [1.2.0] - 2026-01-08
- EN — Added "Plug & Play" system:
    - Centralized `send()` method in `NotifierFacade` supporting DTOs.
    - New DTOs for all channels: `EmailNotificationDto`, `SmsNotificationDto`, `ChatNotificationDto`, `BrowserNotificationDto`, `PushNotificationDto`.
    - Automatic validation of DTOs using Symfony Validator.
    - `GenericNotificationType`: A dynamic form that adapts automatically to any Notification DTO.
    - `NotificationWidgetController`: Ready-to-use controller for rendering and handling notification forms.
    - Twig extension with `wrap_notify_form(type)` function to easily include notification forms.
    - Base Twig template for forms and delivery status display.
    - Multi-language documentation in `Docs/`.
- FR — Ajout du système « Plug & Play » :
    - Méthode `send()` centralisée dans `NotifierFacade` supportant les DTO.
    - Nouveaux DTO pour tous les canaux : `EmailNotificationDto`, `SmsNotificationDto`, `ChatNotificationDto`, `BrowserNotificationDto`, `PushNotificationDto`.
    - Validation automatique des DTO via le Validator Symfony.
    - `GenericNotificationType` : Formulaire dynamique s'adaptant automatiquement à n'importe quel DTO de notification.
    - `NotificationWidgetController` : Contrôleur prêt à l'emploi pour le rendu et la gestion des formulaires de notification.
    - Extension Twig avec la fonction `wrap_notify_form(type)` pour inclure facilement des formulaires.
    - Template Twig de base pour l'affichage des formulaires et du statut de livraison.
    - Documentation multilingue dans `Docs/`.

## [1.1.0] - 2025-11-08
- EN — Added diagnostic CLI command `wrap:notificator:diagnose` for Mercure & Messenger
  - Publishes a Mercure test Update and/or dispatches a Messenger ping
  - Prints a JSON report and returns 0/1 depending on success
  - Documented in README under "Diagnostic Mercure & Messenger"
- FR — Ajout de la commande CLI de diagnostic `wrap:notificator:diagnose` pour Mercure & Messenger
  - Publie un Update Mercure de test et/ou envoie un ping Messenger
  - Affiche un rapport JSON et renvoie 0/1 selon le succès
  - Documentée dans le README (section « Diagnostic Mercure & Messenger »)

## [1.0.0] - 2025-11-08
- EN — Initial release of WrapNotificatorBundle
  - Facade API over Mailer + Notifier + Mercure + Web Push
  - MessageFactory, TypedSender, NotifierFacade
  - CLI command `notify:send`
  - DTOs: DeliveryStatus, BrowserPayload, WebPushMessage
  - Examples, tests, CI, and quality tooling
- FR — Première version de WrapNotificatorBundle
  - Façade au‑dessus de Mailer + Notifier + Mercure + Web Push
  - MessageFactory, TypedSender, NotifierFacade
  - Commande CLI `notify:send`
  - DTOs: DeliveryStatus, BrowserPayload, WebPushMessage
  - Exemples, tests, CI et outillage qualité
