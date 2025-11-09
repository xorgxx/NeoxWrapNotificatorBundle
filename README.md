# WrapNotificatorBundle

Un bundle Symfony 7.3 / PHP 8.3 pour unifier et simplifier l’envoi de notifications via Mailer, Notifier (SMS/Chat), Mercure (browser) et Web Push, avec une UX front moderne (toasts) et des fonctionnalités avancées (idempotence, corrélation, envoi différé async).

A Symfony 7.3 / PHP 8.3 bundle to unify Mailer, Notifier (SMS/Chat), Mercure (browser) and Web Push with a modern front‑end UX (toasts) and advanced features (idempotency, correlation, deferred async send).

---

## Sommaire / Table of Contents
- [Présentation / Overview](#présentation--overview)
- [Prérequis / Requirements](#prérequis--requirements)
- [Installation rapide / Quick Start](#installation-rapide--quick-start)
- [Configuration minimale / Minimal Configuration](#configuration-minimale--minimal-configuration)
- [Cas d’usages / Use Cases](#cas-dusages--use-cases)
- [Cas d’usage dans les contrôleurs / Controller use cases](#cas-dusage-dans-les-contrôleurs--controller-use-cases)
- [Écouteurs Mercure côté Twig (UI) / Twig Mercure Listeners (UI)](#écouteurs-mercure-côté-twig-ui--twig-mercure-listeners-ui)
- [API de façade (PHP) / Facade API (PHP)](#api-de-façade-php--facade-api-php)
- [Envoi différé (async) / Deferred send (async)](#envoi-différé-async--deferred-send-async)
- [CLI `notify:send`](#cli-notifysend)
- [Corrélation & Idempotence / Correlation & Idempotency](#corrélation--idempotence--correlation--idempotency)
- [Diagnostic Mercure & Messenger](#diagnostic-mercure--messenger)
- [Sécurité & CORS / Security & CORS](#sécurité--cors--security--cors)
- [Dépannage rapide / Quick Troubleshooting](#dépannage-rapide--quick-troubleshooting)
- [Tests & Qualité / Tests & Quality](#tests--qualité--tests--quality)
- [Licence / License](#licence--license)

---

## Présentation / Overview
- FR — Fournit une façade `NotifierFacade`, un `MessageFactory` et un `TypedSender` pour adresser Email, SMS/Chat, Mercure (navigateur) et Web Push avec une API simple et des statuts normalisés (`DeliveryStatus`).
- EN — Provides `NotifierFacade`, a `MessageFactory` and a `TypedSender` to send Email, SMS/Chat, Mercure (browser) and Web Push with a simple API and normalized `DeliveryStatus`.

Fonctionnalités clés / Key features:
- FR — Écouteurs Mercure injectables en Twig avec UI moderne (toasts, pause au survol, icônes, thème clair/sombre, CSS externe par défaut).
- EN — Twig‑injectable Mercure listeners with a modern UI (toasts, hover‑to‑pause, icons, light/dark theme, external CSS by default).
- FR — Idempotence (déduplication) et corrélation via `DeliveryContext`.
- EN — Idempotency (deduplication) and correlation through `DeliveryContext`.
- FR — Envoi différé (date/heure) en mode asynchrone via Symfony Messenger.
- EN — Deferred (date/time) delivery in async mode using Symfony Messenger.

---

## Prérequis / Requirements
- PHP 8.3+, Symfony 7.3+
- Optionnels / Optional services:
  - Mailer, Notifier (Chatter/Texter), Mercure Hub, Web Push (minishlink/web-push)

---

## Installation rapide / Quick Start
```bash
composer require wrap/notificator-bundle
composer require symfony/mailer symfony/notifier symfony/mercure-bundle minishlink/web-push
```
Si l’auto‑découverte n’est pas active / If discovery is off:
```php
// config/bundles.php
return [ WrapNotificatorBundle\WrapNotificatorBundle::class => ['all' => true], ];
```
Publier les assets (CSS des toasts) / Publish assets:
```bash
php bin/console assets:install --symlink --relative
```

---

## Configuration minimale / Minimal Configuration
`.env` (exemples / examples):
```env
MAILER_DSN=smtp://localhost:1025
SLACK_DSN=slack://xoxb-***@default?channel=my-channel
TELEGRAM_DSN=telegram://bot-token@default?channel=@my_channel
TWILIO_DSN=twilio://SID:TOKEN@default?from=%2B33600000000
MERCURE_URL=http://localhost:3000/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_SECRET=!ChangeMe!
VAPID_PUBLIC_KEY=yourPublicKey
VAPID_PRIVATE_KEY=yourPrivateKey
VAPID_SUBJECT=mailto:you@example.com
```
`config/packages/wrap_notificator.yaml` (extrait / excerpt):
```yaml
wrap_notificator:
  mercure:
    enabled: true
    turbo_enabled: false
    only_authenticated: false
    public_url: '%env(string:MERCURE_PUBLIC_URL)%'
    with_credentials_default: false   # default for EventSource credentials; override per-listener via options.withCredentials
    default_topics: ['geo_notificator/stream']
    ui:
      external_css: true
      auto_link_css: true
      asset_path: '@WrapNotificator/css/wrap_notificator.css'
      asset_fallback_prefix: '/bundles/wrapnotificator'
```

---

## Cas d’usages / Use Cases

### Email
- PHP
```php
$ctx = null; // ou DeliveryContext::create(...)
$facade->notifyEmail('Bienvenue', '<h1>Bonjour</h1>', 'user@example.com', true, [], [], $ctx);
```
- CLI
```bash
php bin/console notify:send --channel=email --to=user@example.com --subject="Bienvenue" --html='<h1>Bonjour</h1>'
```

#### Nouvelles options de rendu et pièces jointes
- `opts.template` (string): nom du template Twig à rendre, ex: `emails/bienvenue.html.twig`
- `opts.vars` (array): variables passées au template
- `opts.attachments` (array): pièces jointes, mixte de chemins ou binaires
- `opts.inline` (array): éléments inline (images, etc.) intégrés via CID, mêmes formats que `attachments`

Règles:
- Si `template` est fourni et non vide, `htmlOrText` est ignoré, le contenu est rendu via Twig et envoyé en HTML (force `opts.html = true`).
- Détection automatique des pièces jointes: nom, type MIME et icône logique sont déterminés si absents.
- Formes acceptées pour `attachments`/`inline`:
  - `string`: chemin vers un fichier (absolu ou relatif)
  - `array` mode chemin: `{ path: '/path/to/file', name?: 'custom.ext', mime?: 'type/subtype' }`
  - `array` mode binaire: `{ bin: <bytes|string|resource>|content_base64: <base64>, name?: 'fichier.ext', mime?: 'type/subtype' }`
  - `cid` optionnel pour `inline`; s'il est absent, un CID est généré automatiquement (basé sur le nom sans extension ou un `uniqid`).

Exemples d’usage:

- Depuis un template Twig simple
```php
$status = $facade->notifyEmail(
  subject: 'Bienvenue',
  htmlOrText: '',
  to: 'user@example.com',
  isHtml: true,
  opts: [
    'template' => 'emails/bienvenue.html.twig',
    'vars' => ['prenom' => 'Alice'],
  ]
);
```

- Pièces jointes depuis chemins et binaire/base64
```php
$status = $facade->notifyEmail(
  subject: 'Documents',
  htmlOrText: '',
  to: 'user@example.com',
  isHtml: true,
  opts: [
    'template' => 'emails/list.html.twig',
    'vars' => ['title' => 'Vos documents'],
    'attachments' => [
      __DIR__.'/files/guide.pdf',
      ['bin' => base64_encode($pdfRaw), 'name' => 'contrat.pdf'],
      ['bin' => $binaryPng, 'name' => 'aperçu.png'],
    ],
  ]
);
```

- Image inline avec CID auto (utilisable dans `<img src="cid:...">`)
```php
$status = $facade->notifyEmail(
  subject: 'Logo inline',
  htmlOrText: '',
  to: 'user@example.com',
  isHtml: true,
  opts: [
    'template' => 'emails/logo.html.twig',
    'inline' => [
      __DIR__.'/assets/logo.png', // CID auto basé sur le nom
    ],
  ]
);
// Dans le template Twig: <img src="cid:logo">
```

- Envoi différé et transport forcé (inchangés)
```php
$ctx = (new \Neox\WrapNotificatorBundle\Notification\DeliveryContext())
  ->deferAt(new \DateTimeImmutable('+10 minutes'));
// ou $ctx->viaTransport('async');
$status = $facade->notifyEmail(
  subject: 'Rapport',
  htmlOrText: '',
  to: 'user@example.com',
  isHtml: true,
  opts: ['template' => 'emails/report.html.twig', 'vars' => ['date' => '2025-01-01']],
  metadata: [],
  ctx: $ctx
);
```

### SMS
- PHP
```php
$facade->notifySms('Votre code: 123456', '+33600000000');
```
- CLI
```bash
php bin/console notify:send --channel=sms --to=+33600000000 --text="Votre code: 123456"
```

### Chat (Slack / Telegram)
- PHP (Slack)
```php
$facade->notifyChat('slack', 'Déploiement terminé ✅', 'Release 1.2.3', ['channel' => 'ops', 'iconEmoji' => ':rocket:']);
```
- PHP (Telegram)
```php
$facade->notifyChat('telegram', '<b>Alerte</b> Service lent', null, ['chatId' => 123456, 'parseMode' => 'HTML']);
```
- CLI
```bash
php bin/console notify:send --channel=chat --transport=slack --text="Déploiement ok" --subject="Release" --opt=channel:ops --opt=iconEmoji::rocket:
php bin/console notify:send --channel=chat --transport=telegram --text="<b>Alerte</b>" --opt=chatId:123456 --opt=parseMode:HTML
```

### Navigateur (Mercure) — Toasts et Notifications
- Twig (ajouter dans le layout)
```twig
{{ wrap_notify_bootstrap() }}
{{ wrap_notify_browser(['users:42']) }}
{{ wrap_notify_system(['system:alerts']) }}
```
- Publication côté serveur (PHP)
```php
$facade->notifyBrowser('users:42', [
  'title' => 'Bonjour',
  'body' => 'Bienvenue 👋',
  'level' => 'info', // info|success|warning|danger
  'iconClass' => 'bi bi-info-circle',
  'duration' => 6000,
  'ui' => ['position' => 'top-right', 'density' => 'cozy', 'shadow' => 'md']
]);
```
- Variantes rapides (payloads)
```json
[
  {"title":"OK","body":"Sauvegarde faite","level":"success"},
  {"title":"Attention","body":"Quota à 90%","level":"warning"},
  {"title":"Erreur","body":"Échec sauvegarde","level":"danger","iconUrl":"/images/error.svg"}
]
```
- Notifications système (OS)
```php
$facade->notifyBrowser('system:alerts', ["title"=>"Maintenance","body"=>"à 23:00","icon"=>"/img/maintenance.png"]);
```
- Turbo Streams (si activé)
```php
$facade->notifyBrowser('users:42', [
  'title' => 'Mise à jour',
  'body' => 'Un élément a été ajouté',
  'turbo' => [ 'stream' => '<turbo-stream action="append" target="list"><template>...</template></turbo-stream>' ]
]);
```

### Web Push / Desktop
- PHP
```php
$subscription = json_decode(file_get_contents(__DIR__.'/sub.json'), true);
$facade->notifyPush($subscription, ['title' => 'Hello', 'body' => 'World'], 3600);
```
- CLI
```bash
php bin/console notify:send --channel=push --subscription-file=./sub.json --data=title:"Hello" --data=body:"World" --ttl=3600
```

### Envoi différé (async)
- PHP
```php
use Neox\WrapNotificatorBundle\Notification\DeliveryContext;
$ctx = DeliveryContext::create(deferAt: new \DateTimeImmutable('+15 minutes'));
$facade->notifySms('Rappel dans 15min', '+33600000000', [], $ctx);
```
- CLI
```bash
php bin/console notify:send --channel=browser --topic='users:42' --data=title:"Rappel" --data=level:info --in=15m
```

### Corrélation & Déduplication
- PHP
```php
$ctx = DeliveryContext::for('reminder:user:42:2025-12-01', ttlSeconds: 900);
$facade->notifyEmail('Rappel', 'Bonjour', 'user@example.com', true, [], [], $ctx);
```
- CLI
```bash
php bin/console notify:send --channel=sms --to=+33600000000 --text="Rappel" --dedupe-key="reminder:user:42:2025-12-01" --dedupe-ttl=900
```

> FR — Chaque extrait ci‑dessus illustre l’envoi Email/SMS/Chat, les toasts et notifications OS (Mercure) côté navigateur, le Push, la planification différée et la corrélation/déduplication. Voir les sections détaillées ci‑dessous.

> EN — Each snippet above shows Email/SMS/Chat sending, Browser (Mercure) toasts and OS notifications, Push, Deferred scheduling, and Correlation/Deduplication. See detailed sections below.

---

## Cas d’usage dans les contrôleurs / Controller use cases

> FR — Exemples prêts à copier/coller pour utiliser `NotifierFacade` dans vos contrôleurs Symfony (attributs PHP 8).  
> EN — Copy‑paste ready examples to use `NotifierFacade` in Symfony controllers (PHP 8 attributes).

### Injection via constructeur / Constructor injection
```php
<?php
namespace App\Controller;

use Neox\WrapNotificatorBundle\Service\NotifierFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class NotifyController extends AbstractController
{
    public function __construct(private readonly NotifierFacade $notifier) {}

    #[Route('/notify/email', name: 'notify_email', methods: ['POST'])]
    public function email(): JsonResponse
    {
        $status = $this->notifier->notifyEmail(
            subject: 'Bienvenue',
            htmlOrText: '<h1>Bonjour</h1>',
            to: 'user@example.com',
            isHtml: true,
        );

        return $this->json($status->toArray());
    }
}
```

### Injection par argument d’action / Action argument autowiring
```php
<?php
namespace App\Controller;

use Neox\WrapNotificatorBundle\Service\NotifierFacade;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class SmsController
{
    #[Route('/notify/sms', name: 'notify_sms', methods: ['POST'])]
    public function sms(NotifierFacade $notifier): Response
    {
        $status = $notifier->notifySms('Votre code: 123456', '+33600000000');
        return new Response(json_encode($status->toArray()), 200, ['Content-Type' => 'application/json']);
    }
}
```

### Chat (Slack/Telegram)
```php
use Neox\WrapNotificatorBundle\Service\NotifierFacade;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/notify/chat/slack', methods: ['POST'])]
public function chatSlack(NotifierFacade $notifier): Response
{
    $status = $notifier->notifyChat('slack', 'Déploiement terminé ✅', 'Release 1.2.3', [
        'channel' => 'ops',
        'iconEmoji' => ':rocket:',
    ]);
    return new JsonResponse($status->toArray());
}

#[Route('/notify/chat/telegram', methods: ['POST'])]
public function chatTelegram(NotifierFacade $notifier): Response
{
    $status = $notifier->notifyChat('telegram', '<b>Alerte</b> Service lent', null, [
        'chatId' => 123456,
        'parseMode' => 'HTML',
    ]);
    return new JsonResponse($status->toArray());
}
```

### Navigateur (Mercure) après une action métier / Browser (Mercure) after a domain action
```php
use Doctrine\ORM\EntityManagerInterface;
use Neox\WrapNotificatorBundle\Service\NotifierFacade;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/task', methods: ['POST'])]
public function createTask(NotifierFacade $notifier, EntityManagerInterface $em): Response
{
    // 1) Persister l’entité / Persist entity
    // $task = new Task(...); $em->persist($task); $em->flush();

    // 2) Notifier le navigateur de l’utilisateur concerné
    $status = $notifier->notifyBrowser('users:42', [
        'title' => 'Tâche créée',
        'body'  => 'Votre tâche a été enregistrée',
        'level' => 'success', // info|success|warning|danger
        'iconClass' => 'bi bi-check2-circle',
        'duration' => 6000,
    ]);

    return new JsonResponse($status->toArray());
}
```

### Envoi différé (async) avec Messenger / Deferred send (async)
```php
use Neox\WrapNotificatorBundle\Notification\DeliveryContext;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/notify/deferred', methods: ['POST'])]
public function deferred(NotifierFacade $notifier): Response
{
    // Planifier dans 15 minutes (requires messenger worker)
    $ctx = DeliveryContext::create(deferAt: new \DateTimeImmutable('+15 minutes'));

    $status = $notifier->notifySms('Rappel dans 15min', '+33600000000', [], $ctx);
    // Status sera "queued" avec metadata.deferAt
    return new JsonResponse($status->toArray());
}
```

### Corrélation & Déduplication depuis un contrôleur
```php
use Neox\WrapNotificatorBundle\Notification\DeliveryContext;

#[Route('/notify/dedup', methods: ['POST'])]
public function dedup(NotifierFacade $notifier): Response
{
    // Clef métier stable (empêche les doublons pendant 15min)
    $ctx = DeliveryContext::for('reminder:user:42:2025-12-01', ttlSeconds: 900);

    $status = $notifier->notifyEmail('Rappel', '<p>Bonjour</p>', 'user@example.com', true, [], [], $ctx);
    return new JsonResponse($status->toArray());
}
```

### Web Push (desktop)
```php
use Neox\WrapNotificatorBundle\Service\NotifierFacade;

#[Route('/notify/push', methods: ['POST'])]
public function push(NotifierFacade $notifier, Request $request): Response
{
    // subscription JSON (endpoint, keys[p256dh,auth]) issu de votre front
    $subscription = json_decode($request->getContent(), true);

    $status = $notifier->notifyPush($subscription, [
        'title' => 'Hello',
        'body' => 'World',
    ], ttl: 3600);

    return new JsonResponse($status->toArray());
}
```

### Gestion d’erreurs et logging / Error handling & logging
```php
$status = $notifier->notifySms('Ping', '+336...');
if ($status->status === 'failed') {
    $this->logger->error('Notify failed', $status->toArray());
    return new JsonResponse($status->toArray(), 500);
}
return new JsonResponse($status->toArray());
```

> Notes:
> - Assurez‑vous d’avoir configuré les DSN Mailer/Notifier/Mercure/WebPush nécessaires.
> - L’envoi différé nécessite un transport Messenger supportant `DelayStamp` et un worker actif (`bin/console messenger:consume -vv`).
> - Les topics Mercure doivent correspondre à ce que vos templates Twig écoutent via `wrap_notify_browser([...])`.

---

## Écouteurs Mercure côté Twig (UI) / Twig Mercure Listeners (UI)
Fonctions Twig / Twig functions:
- `wrap_notify_bootstrap()` — FR — installe `window.subscribeMercure()` + `window.wrapNotify` et ajoute le `<link>` CSS par défaut.
- `wrap_notify_bootstrap()` — EN — installs `window.subscribeMercure()` + `window.wrapNotify` and adds the default CSS `<link>`.
- `wrap_notify_browser(array $topics = [], array $options = [])` — FR — toasts navigateurs modernes (barre de progression, pause au survol, icône, variants info/success/warning/danger). Le 2ᵉ argument accepte des options (ex: `{ withCredentials: true }`).
- `wrap_notify_browser(array $topics = [], array $options = [])` — EN — modern browser toasts (progress bar, hover‑to‑pause, icon, variants info/success/warning/danger). The 2nd argument accepts options (e.g., `{ withCredentials: true }`).
- `wrap_notify_system(array $topics = [], array $options = [])` — FR — notifications système (Web Notifications API) avec fallback en toast; 2ᵉ argument identique.
- `wrap_notify_system(array $topics = [], array $options = [])` — EN — OS‑level notifications (Web Notifications API) with toast fallback; 2nd argument identical.
- `wrap_notify_styles()` — FR — facultatif si `auto_link_css=false`.
- `wrap_notify_styles()` — EN — optional if `auto_link_css=false`.

Exemples Twig:
```twig
{{ wrap_notify_bootstrap() }}
{# basique #}
{{ wrap_notify_browser(['/chat/flash-sales']) }}
{{ wrap_notify_system(['dede:system']) }}

{# avec options (ex.: activer l'envoi des cookies côté EventSource) #}
{{ wrap_notify_browser(['/chat/flash-sales'], {'withCredentials': true}) }}
{{ wrap_notify_system(['dede:system'], {'withCredentials': false}) }}
```

Variant (style) depuis le payload / Variant detection from payload:
- Priorité: `level` → `type` → `variant` → `status` → `severity` → `kind`.
- Mapping: info | success | warning | danger (défaut: info).

Options UI (payload): `delay|duration|ttl` (ms, 1500–15000), `position` (top-right|top-left|bottom-right|bottom-left), `density` (compact|cozy), `rounded`, `shadow` (sm|md|lg), `glass`, `opacity`, `iconHtml|iconClass|icon|iconUrl`.

Souscription côté navigateur / Browser subscription:
- `subscribeMercure(baseUrl, topics, onMessage?, options?)` ouvre un `EventSource`. Par défaut `withCredentials: false`; vous pouvez l'activer par écouteur via `options.withCredentials === true` ou globalement via `wrap_notificator.mercure.with_credentials_default: true`.
- Fermeture propre sur rafraîchissement/navigation (gestion `pagehide`/`beforeunload`) pour éviter les messages « connexion interrompue pendant le chargement ».
- CORS: si vous activez `withCredentials`, le hub doit autoriser `Access-Control-Allow-Credentials: true` et une origine explicite (pas `*`). En same‑origin, les cookies sont envoyés par le navigateur indépendamment du flag.

CSS externe / External CSS:
- Par défaut chargé via `@WrapNotificator/css/wrap_notificator.css` (assets installés). Si vous utilisez un pipeline qui fingerprint sous `/assets/...`, pointez `ui.asset_path` vers l'URL finale ou désactivez `auto_link_css` et utilisez `{{ asset() }}`.

---

## API de façade (PHP) / Facade API (PHP)
FR — Toutes renvoient `DeliveryStatus` (sent|queued|failed) et acceptent `?DeliveryContext $ctx`.
EN — All methods return `DeliveryStatus` (sent|queued|failed) and accept `?DeliveryContext $ctx`.
- `notifyEmail(subject, htmlOrText, to, isHtml=true, opts=[], metadata=[], ?DeliveryContext $ctx=null)`
- `notifySms(content, to, metadata=[], ?DeliveryContext $ctx=null)`
- `notifyChat(transport, content, subject=null, opts=[], metadata=[], ?DeliveryContext $ctx=null)`
- `notifyBrowser(topic, data, metadata=[], ?DeliveryContext $ctx=null)`
- `notifyPush(subscription, data, ttl=null, metadata=[], ?DeliveryContext $ctx=null)`
- `notifyDesktop(subscription, data, ttl=null, metadata=[], ?DeliveryContext $ctx=null)`

`MessageFactory` helpers: `email`, `sms`, `chat`, `browser`, `push`.

---

## Envoi différé (async) / Deferred send (async)
FR — Planifier un envoi à une date/heure future via Symfony Messenger (worker requis). Si le bus est absent et que `deferAt` est demandé → `failed` explicite.
EN — Schedule delivery at a future date/time using Symfony Messenger (worker required). If bus is missing and `deferAt` is set → explicit `failed`.

API (PHP):
```php
use Neox\WrapNotificatorBundle\Notification\DeliveryContext;
$ctx = DeliveryContext::create(deferAt: new \DateTimeImmutable('2025-12-01 10:30:00+01:00'));
$facade->notifyEmail('Rappel', '<p>Bonjour</p>', 'user@example.com', true, [], [], $ctx);
```
CLI:
```bash
php bin/console notify:send --channel=email --to=user@example.com --subject="Rappel" --html='<p>Bonjour</p>' --in=15m
php bin/console notify:send --channel=sms --to=+33600000000 --text="RDV" --send-at=2025-12-01T10:30:00+01:00
```
Formats acceptés / Accepted formats: `--send-at` (ISO 8601 ou `Y-m-d H:i`), `--in` (ISO 8601 `PT10M`, ou `15m`, `2h`, `1d`, `1h30m`).

---

## CLI `notify:send`
Options par canal / Options by channel (synthèse):
- `--channel=email|sms|chat|browser|push|desktop`
- Email: `--to --subject --html|--text`
- SMS: `--to --text`
- Chat: `--transport=slack|telegram --text [--subject] --opt=key:value`
- Browser: `--topic --data=key:value`
- Push/Desktop: `--subscription-file=./sub.json --data=key:value [--ttl=3600]`
- Corrélation/Idempotence: `--correlation-id` `--dedupe-key` `--dedupe-ttl`
- Différé (async): `--send-at` | `--in`

Exemples rapides / Quick examples:
```bash
php bin/console notify:send --channel=email --to="user@example.com" --subject="Bienvenue" --html='<h1>Bonjour</h1>'
php bin/console notify:send --channel=browser --topic='users:42' --data=title:"Bienvenue" --data=level:info
```
PowerShell:
```powershell
php bin/console notify:send ` --channel=push ` --subscription-file=".\sub.json" ` --data=title:"Hello" ` --data=body:"Notification"
```
Codes de retour / Exit codes: `0` (sent|queued), `1` (failed).

---

## Corrélation & Idempotence / Correlation & Idempotency
`DeliveryContext` — `correlationId` (UUID auto si omis), `dedupeKey` (clé stable métier), `ttlSeconds`.
- Déduplication: sur hit, statut `queued` (`reason=dedup-hit`, `message=noop`).
- Exemples CLI / PHP fournis dans les sections ci‑dessus.

---

## Diagnostic Mercure & Messenger
FR — Diagnostiquer rapidement la connectivité Mercure et la disponibilité de Messenger. La commande peut publier un message d’essai Mercure et/ou dispatcher un ping Messenger, puis renvoie un rapport JSON et un code retour 0/1.
EN — Quickly diagnose Mercure connectivity and Messenger availability. The command can publish a test Mercure update and/or dispatch a Messenger ping, then prints a JSON report and exits with 0/1.

Commande / Command:
```bash
php bin/console wrap:notificator:diagnose
```
Options clefs / Key options: `--topic`, `--mercure-only`, `--messenger-only`, `--async`, `--delay=SECONDS`.

---

## Sécurité & CORS / Security & CORS
- FR — Par défaut, `withCredentials` est à `false`. Activez‑le par écouteur (options.withCredentials) ou globalement (`wrap_notificator.mercure.with_credentials_default: true`) si vous avez besoin d’accéder à des topics privés via cookie.
- EN — By default, `withCredentials` is `false`. Enable it per listener (options.withCredentials) or globally (`wrap_notificator.mercure.with_credentials_default: true`) when private topics via cookie are required.
- FR — Si `withCredentials: true` :
  - `Access-Control-Allow-Credentials: true`, et `Access-Control-Allow-Origin` doit être l’origine exacte (pas `*`).
  - En cross‑domain, cookie JWT en `SameSite=None; Secure` + HTTPS.
- EN — If `withCredentials: true`:
  - `Access-Control-Allow-Credentials: true`, and `Access-Control-Allow-Origin` must be the exact origin (not `*`).
  - Cross‑domain requires JWT cookie `SameSite=None; Secure` + HTTPS.
- FR — EventSource n’accepte pas d’en‑têtes personnalisés → authentification via cookie du hub.
- EN — EventSource cannot send custom headers → authentication relies on the hub cookie.
- FR — Web Push : HTTPS requis et permissions navigateur/OS.
- EN — Web Push: HTTPS required and browser/OS permissions.

---

## Dépannage rapide / Quick Troubleshooting
- FR — Aucun toast ? Vérifiez `wrap_notify_bootstrap()` et `wrap_notificator.mercure.enabled=true`, ainsi que l’URL publique Mercure.  
- EN — No toast showing? Ensure `wrap_notify_bootstrap()` is included, `wrap_notificator.mercure.enabled=true`, and the Mercure public URL is correct.
- FR — Pas de styles ? Vérifiez la présence du `<link rel="stylesheet" href="@WrapNotificator/css/wrap_notificator.css">` et que les assets sont installés.  
- EN — No styles? Check the `<link rel="stylesheet" href="@WrapNotificator/css/wrap_notificator.css">` is present and assets are installed.
- FR — Différé (async) ? Assurez‑vous qu’un worker Messenger tourne et que le transport supporte `DelayStamp`.  
- EN — Deferred (async)? Make sure a Messenger worker is running and your transport supports `DelayStamp`.

---

## Tests & Qualité / Tests & Quality
- FR — PHPUnit: `make test`  
  - EN — PHPUnit: `make test`
- FR — phpstan niveau 8, PSR‑12 (voir `.php-cs-fixer.php`).  
- EN — phpstan level 8, PSR‑12 (see `.php-cs-fixer.php`).

---

## Licence / License
FR — MIT  
EN — MIT


---

## Forcer le mode d'envoi (async ou sync) par notification / Per-notification transport override

FR — Vous pouvez forcer le transport Messenger (ex: `asyncRabbitMq` ou `sync`) pour UNE notification, sans changer la configuration globale. Il suffit de passer `DeliveryContext::create(viaTransport: '...')`.

EN — You can force the Messenger transport (e.g., `asyncRabbitMq` or `sync`) for a SINGLE notification, without changing global config. Just pass `DeliveryContext::create(viaTransport: '...')`.

Exemples / Examples:

```php
use Neox\WrapNotificatorBundle\Notification\DeliveryContext;

// 1) Forcer l'ASYNC (file) sur RabbitMQ, même si la conf par défaut est sync
$ctx = DeliveryContext::create(viaTransport: 'asyncRabbitMq');
$facade->notifySms('Ping async', '+33600000000', [], $ctx); // status: queued

// 2) Forcer le SYNCHRONE (immédiat), même si la conf par défaut route en async
$ctx = DeliveryContext::create(viaTransport: 'sync');
$facade->notifyChat('slack', 'Immédiat', 'Urgent', ['channel' => 'ops'], [], $ctx); // status: sent

// 3) Combinable avec un envoi différé (planification) → restera traité par le transport ciblé
$ctx = DeliveryContext::create(deferAt: new \DateTimeImmutable('+10 minutes'), viaTransport: 'asyncRabbitMq');
$facade->notifyBrowser('users:42', ['title' => 'Rappel', 'level' => 'info'], [], $ctx); // status: queued (scheduled)

// 4) Emails
$ctx = DeliveryContext::create(viaTransport: 'asyncRabbitMq');
$facade->notifyEmail('Bienvenue', '<h1>Bonjour</h1>', 'user@example.com', true, [], [], $ctx);
```

Notes:
- Requiert Messenger (`MessageBusInterface`) pour forcer un transport; si le bus est indisponible → `failed` explicite.
- `viaTransport: 'sync'` exécute dans le processus courant; pas de retry automatique.
- Pour les canaux sans intégration Messenger native (ex: Web Push), le mode forcé est ignoré, sauf lorsqu'un envoi est planifié (Deferred), qui passe par Messenger.
- Le routage global (`config/packages/messenger.yaml`) reste la valeur par défaut quand `viaTransport` n'est pas fourni.
