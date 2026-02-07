# CHANGELOG

All notable changes to this project will be documented in this file.

## [Unreleased]

## [1.4.0] - 2026-02-07
- EN — Added Live Flash feature:
    - New attribute `#[LiveFlash]` to enable/disable live flash publishing per controller/method.
    - New `wrap_notificator.live_flash` configuration (global enable + topic prefix + consume + optional grouping).
    - Flash messages can be published via Mercure on `kernel.response` and displayed instantly as toasts.
- EN — Added Twig helper `wrap_notify_flashes(app.flashes, options)` to render classic Symfony flash messages (without Mercure) using the WrapNotificator UI/theme.
- EN — Docs: added examples to trigger browser toasts from a link/button via `window.wrapNotify.notifyBrowser(...)`.
- EN — Added SweetAlert2 toast auto-detection:
    - Replaced SweetAlert2 renderer with iziToast (`window.iziToast`) for browser notifications.
    - Added `wrap_notificator.mercure.ui.renderer` to choose between `auto`, `izitoast` and `bootstrap`.
- EN — UI toast theming & styling:
    - Added `wrap_notificator.mercure.ui.force_theme` (`auto|dark|light`) to force dark/light UI.
    - Added `wrap_notificator.mercure.ui.toast_theme` (`default|amazon|google|dark`) to apply a toast skin.
    - CSS is now split into `wrap_notificator.base.css` + optional theme CSS (`amazon|google|dark`) and can be auto-injected by `wrap_notify_bootstrap()`.
    - When `toast_theme != default` and `renderer=auto`, Bootstrap renderer is preferred so the skin applies consistently (iziToast ignores `toast_theme`).
    - Modernized grouped flash toasts (flash_group): improved layout, optional icon removal, and per-item colored dot list.
    - Scoped iziToast dark overrides to bundle-created toasts via `.wrap-notify-izi`.
- FR — Ajout de la fonctionnalité Live Flash :
    - Nouvel attribut `#[LiveFlash]` pour activer/désactiver le live flash par contrôleur/méthode.
    - Nouvelle configuration `wrap_notificator.live_flash` (activation globale + préfixe de topic + consommation + regroupement optionnel).
    - Publication des flashes via Mercure sur `kernel.response` pour affichage instantané en toasts.
- FR — Ajout du helper Twig `wrap_notify_flashes(app.flashes, options)` pour afficher les flash messages Symfony classiques (sans Mercure) avec le thème/UI WrapNotificator.
- FR — Docs : ajout d'exemples pour déclencher un toast navigateur depuis un lien/bouton via `window.wrapNotify.notifyBrowser(...)`.
- FR — Ajout de l'auto-détection SweetAlert2 (toast) :
    - Remplacement du renderer SweetAlert2 par iziToast (`window.iziToast`) pour les notifications navigateur.
    - Ajout de `wrap_notificator.mercure.ui.renderer` pour choisir entre `auto`, `izitoast` et `bootstrap`.
- FR — Thématisation UI des toasts :
    - Ajout de `wrap_notificator.mercure.ui.force_theme` (`auto|dark|light`) pour forcer le thème dark/light.
    - Ajout de `wrap_notificator.mercure.ui.toast_theme` (`default|amazon|google|dark`) pour appliquer un skin de toast.
    - CSS séparé en `wrap_notificator.base.css` + CSS de thème optionnel (`amazon|google|dark`), auto-injectable via `wrap_notify_bootstrap()`.
    - Quand `toast_theme != default` et `renderer=auto`, le renderer Bootstrap est préféré pour garantir l'application du skin (iziToast ignore `toast_theme`).
    - Modernisation des flashes groupés (flash_group) : layout modernisé, suppression optionnelle de l'icône, liste avec point coloré par item.
    - Overrides dark d'iziToast scoppés uniquement aux toasts du bundle via `.wrap-notify-izi`.

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
