# Usage

## Basic Use Cases (PHP)

### Email
```php
$ctx = null; // or DeliveryContext::create(...)
$facade->notifyEmail('Welcome', '<h1>Hello</h1>', 'user@example.com', true, [], [], $ctx);
```

#### New Rendering Options and Attachments
- `opts.template` (string): Twig template name, e.g., `emails/welcome.html.twig`
- `opts.vars` (array): variables passed to the template
- `opts.attachments` (array): attachments, mix of paths or binaries
- `opts.inline` (array): inline elements (images, etc.) via CID

**Rules:**
- If `template` is provided, `htmlOrText` is ignored, the content is rendered via Twig and sent as HTML (forces `isHtml = true`).
- Auto-detection: name, MIME type, and logical icon are determined if missing.
- Accepted formats for `attachments` / `inline`:
    - `string`: path to a file.
    - `array` path mode: `{ path: '/path/to/file', name?: 'custom.ext', mime?: 'type/subtype' }`
    - `array` binary mode: `{ bin: <bytes>, name?: 'file.ext', mime?: 'type/subtype' }`
    - optional `cid` for `inline`; if missing, a CID is auto-generated (based on name).

Full example:
```php
$status = $facade->notifyEmail(
  subject: 'Documents',
  htmlOrText: '',
  to: 'user@example.com',
  isHtml: true,
  opts: [
    'template' => 'emails/list.html.twig',
    'vars' => ['title' => 'Your documents'],
    'attachments' => [
      __DIR__.'/files/guide.pdf',
      ['bin' => $binaryPng, 'name' => 'preview.png'],
    ],
    'inline' => [
      __DIR__.'/assets/logo.png', // Auto-generated "logo" CID
    ]
  ]
);
```

### SMS
```php
$facade->notifySms('Your code: 123456', '+33600000000');
```

### Chat (Slack / Telegram)
```php
// Slack
$facade->notifyChat('slack', 'Deployment finished ✅', 'Release 1.2.3', ['channel' => 'ops', 'iconEmoji' => ':rocket:']);

// Telegram
$facade->notifyChat('telegram', '<b>Alert</b> Slow service', null, ['chatId' => 123456, 'parseMode' => 'HTML']);
```

#### Chat: non-string payloads and advanced transports (Discord, ...)

`notifyChat()` also accepts non-string payloads for `content` (array/object/Stringable): they are normalized to a string (JSON when applicable).

For advanced transports like **Discord** (embeds/options), it is recommended to build a `ChatMessage` yourself and pass it as `content`.

**Note:** deferred scheduling (`DeliveryContext::deferAt`) is not supported when `content` is a `ChatMessage`.

Discord example (embeds):

```php
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Bridge\Discord\DiscordOptions;
use Symfony\Component\Notifier\Bridge\Discord\Embeds\DiscordEmbed;

$chat = new ChatMessage('');
$chat->options(
    (new DiscordOptions())
        ->addEmbed(
            (new DiscordEmbed())
                ->title('Build OK')
                ->description('CI is green')
        )
);

// The actual transport is provided by symfony/discord-notifier and the DISCORD_DSN.
$facade->notifyChat('discord', $chat);
```

### Browser (Mercure)
```php
$facade->notifyBrowser('users:42', [
  'title' => 'Hello',
  'body' => 'Welcome 👋',
  'level' => 'success', // info|success|warning|danger
  'iconClass' => 'bi bi-info-circle',
  'duration' => 6000,
  'ui' => ['position' => 'top-right', 'density' => 'cozy']
]);
```

#### Turbo Streams
If enabled, you can send Turbo fragments:
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

## Controller usage

### Constructor injection
```php
final class NotifyController extends AbstractController
{
    public function __construct(private readonly NotifierFacade $notifier) {}

    #[Route('/notify/email', name: 'notify_email', methods: ['POST'])]
    public function email(): JsonResponse
    {
        $status = $this->notifier->notifyEmail('Welcome', '<h1>Hello</h1>', 'user@example.com');
        return $this->json($status->toArray());
    }
}
```

### Dynamic Form (Plug & Play)
You can use the `GenericNotificationType` to automatically generate a form based on a DTO.

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
        // Automatic validated send
        $status = $this->notifier->send($dto);
        // ... handle status
    }

    return $this->render('notify/sms.html.twig', ['form' => $form]);
}
```

## Twig functions (UI)

- `{{ wrap_notify_bootstrap() }}`: installs JS helpers and adds CSS.
- `{{ wrap_notify_browser(['topic']) }}`: shows browser toasts.
- `{{ wrap_notify_system(['topic']) }}`: uses Web Notifications API (OS).
- `{{ wrap_notify_form('channel') }}`: displays a complete form for the specified channel (`email`, `sms`, `chat`, `browser`, `push`).

Example in a layout:
```twig
{{ wrap_notify_bootstrap() }}
{{ wrap_notify_browser(['users:42']) }}
```

### Trigger a toast from a link or a button

The bundle exposes `window.wrapNotify.notifyBrowser(...)` (installed by `wrap_notify_bootstrap()`), which lets you trigger a browser notification (iziToast when available, otherwise Bootstrap toast).

Example with an `<a>` link:

```twig
<a href="#" onclick="event.preventDefault(); window.wrapNotify?.notifyBrowser?.({ payload: { title: 'Contact', message: 'Clicked Contact', level: 'info', delay: 5000 } })">
  Contact
</a>
```

Example with Stimulus:

```twig
<a href="#" data-action="click->alert#fireToast">Contact</a>
```

```js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  fireToast(event) {
    event.preventDefault();
    window.wrapNotify?.notifyBrowser?.({
      payload: { title: 'Contact', message: 'Clicked Contact', level: 'info', delay: 5000 }
    });
  }
}
```

## CLI Commands

### Bundle Configuration: `wrap:notificator:config`

This command displays all WrapNotificator bundle configuration.

```bash
# Full display with formatting
php bin/console wrap:notificator:config

# JSON format only (for scripts)
php bin/console wrap:notificator:config --json

# Specific section
php bin/console wrap:notificator:config --section=mercure
php bin/console wrap:notificator:config --section=ui
php bin/console wrap:notificator:config --section=logging
php bin/console wrap:notificator:config --section=live_flash
```

**Information displayed:**
- **Mercure**: enabled, public_url, topics, auto_inject, turbo_enabled, etc.
- **UI**: renderer (auto/izitoast/bootstrap), force_theme, toast_theme, CSS, etc.
- **Logging**: enabled
- **Live Flash**: enabled, consume, group_messages, topic_prefix

**Useful for:**
- Checking which renderer is being used (iziToast or Bootstrap 5)
- Debugging bundle configuration
- Exporting configuration as JSON for documentation

### Sending Notifications: `notify:send`

You can send notifications via the console. This command is useful for scripts or scheduled tasks.

#### Examples by channel

```bash
# Email
php bin/console notify:send --channel=email --to=user@example.com --subject="Test" --html="<h1>Hello</h1>"

# SMS
php bin/console notify:send --channel=sms --to=+33600000000 --text="Test SMS"

# Chat (Slack/Telegram)
php bin/console notify:send --channel=chat --transport=slack --text="Deployment ok" --subject="Release" --opt=channel:ops

# Browser (Mercure)
php bin/console notify:send --channel=browser --topic='users:42' --data=title:"Welcome" --data=level:info

# Web Push
php bin/console notify:send --channel=push --subscription-file=./sub.json --data=title:"Hello" --data=body:"World"
```

### Advanced Options (Async & Deduplication)

```bash
# Deferred delivery in 15 minutes
php bin/console notify:send --channel=sms --to=+33600000000 --text="Reminder" --in=15m

# Deduplication (prevents duplicates)
php bin/console notify:send --channel=sms --to=+33600000000 --text="Reminder" --dedupe-key="rem:42" --dedupe-ttl=900
```

### PowerShell

If you use PowerShell, use the backtick (`) for line breaks:

```powershell
php bin/console notify:send `
 --channel=push `
 --subscription-file=".\sub.json" `
 --data=title:"Hello" `
 --data=body:"Notification"
```

### Exit Codes
- `0`: Message sent or queued.
- `1`: Delivery failed.
