# CHANGELOG

All notable changes to this project will be documented in this file.

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
