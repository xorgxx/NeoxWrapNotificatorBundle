# Utilisation

## Cas d’usages de base (PHP)

### Email
```php
$ctx = null; // ou DeliveryContext::create(...)
$facade->notifyEmail('Bienvenue', '<h1>Bonjour</h1>', 'user@example.com', true, [], [], $ctx);
```

#### Nouvelles options de rendu et pièces jointes
- `opts.template` (string) : nom du template Twig à rendre, ex: `emails/bienvenue.html.twig`
- `opts.vars` (array) : variables passées au template
- `opts.attachments` (array) : pièces jointes, mixte de chemins ou binaires
- `opts.inline` (array) : éléments inline (images, etc.) intégrés via CID

**Règles :**
- Si `template` est fourni, `htmlOrText` est ignoré, le contenu est rendu via Twig et envoyé en HTML (force `isHtml = true`).
- Détection automatique : le nom, le type MIME et l'icône logique sont déterminés si absents.
- Formes acceptées pour `attachments` / `inline` :
    - `string` : chemin vers un fichier.
    - `array` mode chemin : `{ path: '/path/to/file', name?: 'custom.ext', mime?: 'type/subtype' }`
    - `array` mode binaire : `{ bin: <bytes>, name?: 'fichier.ext', mime?: 'type/subtype' }`
    - `cid` optionnel pour `inline` ; s'il est absent, un CID est généré automatiquement (basé sur le nom).

Exemple complet :
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
      ['bin' => $binaryPng, 'name' => 'aperçu.png'],
    ],
    'inline' => [
      __DIR__.'/assets/logo.png', // CID "logo" auto-généré
    ]
  ]
);
```

### SMS
```php
$facade->notifySms('Votre code: 123456', '+33600000000');
```

### Chat (Slack / Telegram)
```php
// Slack
$facade->notifyChat('slack', 'Déploiement terminé ✅', 'Release 1.2.3', ['channel' => 'ops', 'iconEmoji' => ':rocket:']);

// Telegram
$facade->notifyChat('telegram', '<b>Alerte</b> Service lent', null, ['chatId' => 123456, 'parseMode' => 'HTML']);
```

### Navigateur (Mercure)
```php
$facade->notifyBrowser('users:42', [
  'title' => 'Bonjour',
  'body' => 'Bienvenue 👋',
  'level' => 'success', // info|success|warning|danger
  'iconClass' => 'bi bi-info-circle',
  'duration' => 6000,
  'ui' => ['position' => 'top-right', 'density' => 'cozy']
]);
```

#### Turbo Streams
Si activé, vous pouvez envoyer des fragments Turbo :
```php
$facade->notifyBrowser('users:42', [
  'turbo' => [ 'stream' => '<turbo-stream action="append" target="list"><template>...</template></turbo-stream>' ]
]);
```

### Web Push
```php
$subscription = json_decode(file_get_contents('sub.json'), true);
$facade->notifyPush($subscription, ['title' => 'Hello', 'body' => 'World'], 3600);
```

## Utilisation dans les contrôleurs

### Injection via constructeur
```php
final class NotifyController extends AbstractController
{
    public function __construct(private readonly NotifierFacade $notifier) {}

    #[Route('/notify/email', name: 'notify_email', methods: ['POST'])]
    public function email(): JsonResponse
    {
        $status = $this->notifier->notifyEmail('Bienvenue', '<h1>Bonjour</h1>', 'user@example.com');
        return $this->json($status->toArray());
    }
}
```

### Formulaire dynamique (Plug & Play)
Vous pouvez utiliser le `GenericNotificationType` pour générer automatiquement un formulaire basé sur un DTO.

```php
use Neox\WrapNotificatorBundle\Form\GenericNotificationType;
use Neox\WrapNotificatorBundle\Notification\Dto\SmsNotificationDto;

#[Route('/notify/sms', name: 'app_sms_form')]
public function sms(Request $request): Response
{
    $dto = new SmsNotificationDto();
    $form = $this->createForm(GenericNotificationType::class, $dto);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        // Envoi automatique validé
        $status = $this->notifier->send($dto);
        // ... gestion du statut
    }

    return $this->render('notify/sms.html.twig', ['form' => $form]);
}
```

## Fonctions Twig (UI)

- `{{ wrap_notify_bootstrap() }}` : installe les helpers JS et ajoute le CSS.
- `{{ wrap_notify_browser(['topic']) }}` : affiche les toasts navigateurs.
- `{{ wrap_notify_system(['topic']) }}` : utilise la Web Notifications API (OS).
- `{{ wrap_notify_form('channel') }}` : affiche un formulaire complet pour le canal spécifié (`email`, `sms`, `chat`, `browser`, `push`).

Exemple dans un layout :
```twig
{{ wrap_notify_bootstrap() }}
{{ wrap_notify_browser(['users:42']) }}
```

## Commande CLI

Vous pouvez envoyer des notifications via la console. Cette commande est utile pour les scripts ou les tâches planifiées.

### Exemples par canal

```bash
# Email
php bin/console notify:send --channel=email --to=user@example.com --subject="Test" --html="<h1>Hello</h1>"

# SMS
php bin/console notify:send --channel=sms --to=+33600000000 --text="Test SMS"

# Chat (Slack/Telegram)
php bin/console notify:send --channel=chat --transport=slack --text="Déploiement ok" --subject="Release" --opt=channel:ops

# Browser (Mercure)
php bin/console notify:send --channel=browser --topic='users:42' --data=title:"Bienvenue" --data=level:info

# Web Push
php bin/console notify:send --channel=push --subscription-file=./sub.json --data=title:"Hello" --data=body:"World"
```

### Options avancées (Async & Déduplication)

```bash
# Envoi différé dans 15 minutes
php bin/console notify:send --channel=sms --to=+33600000000 --text="Rappel" --in=15m

# Déduplication (évite les doublons)
php bin/console notify:send --channel=sms --to=+33600000000 --text="Rappel" --dedupe-key="rem:42" --dedupe-ttl=900
```

### PowerShell

Si vous utilisez PowerShell, utilisez l'accent grave (`) pour les sauts de ligne :

```powershell
php bin/console notify:send `
 --channel=push `
 --subscription-file=".\sub.json" `
 --data=title:"Hello" `
 --data=body:"Notification"
```

### Codes de retour
- `0` : Message envoyé ou mis en file d'attente (queued).
- `1` : Échec de l'envoi.
