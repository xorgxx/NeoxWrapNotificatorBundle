# Configuration

## Minimal Configuration

### `.env` file
Supported environment variables (examples):

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

### Bundle Configuration
Create `config/packages/wrap_notificator.yaml`:

```yaml
wrap_notificator:
  site_name: 'My website'              # Optional: site name displayed in the contact email template
                                      # Fallbacks: Symfony parameter `name_projet` (when available), then request host.
  logging:
    enabled: false                    # Enable logging (defaults to Symfony / Monolog logs)
  live_flash:
    enabled: false
    consume: true
    group_messages: false
    default_topic_prefix: 'wrap_notificator/flash'
  mercure:
    enabled: true
    notify_status: false              # Automatically notify delivery status via Mercure
    turbo_enabled: false
    only_authenticated: false
    public_url: '%env(string:MERCURE_PUBLIC_URL)%'
    with_credentials_default: false   # default for EventSource; override per-listener via options.withCredentials
    default_topics: ['geo_notificator/stream']
    ui:
      external_css: true
      auto_link_css: true
      renderer: 'auto'                # 'auto' (default), 'izitoast' or 'bootstrap'
      force_theme: 'auto'             # 'auto' (default), 'dark' or 'light' (force UI theme)
      toast_theme: 'default'          # 'default' (default), 'amazon' (or 'amazone'), 'google', 'dark'
      asset_path: '@WrapNotificator/css/wrap_notificator.css'
      asset_fallback_prefix: '/bundles/wrapnotificator'
```

### Default contact email template variables

When `wrap_notificator.email_template.enabled: true`, the bundle uses `@WrapNotificator/email/contact_form.html.twig` and provides (among others):

- **`siteName`**:
  - `wrap_notificator.site_name` when set
  - else Symfony parameter `name_projet` (when available)
  - else `request.host`
- **`siteUrl`** (rendered as a clickable link in the header):
  - Symfony parameter `web_site` (when available)
  - else `request.schemeAndHttpHost`

## UI themes (force_theme vs toast_theme)

- **force_theme**: forces **dark/light** mode (colors)
- **toast_theme**: applies a **CSS skin** (amazon/google/dark) for Bootstrap toasts

When `toast_theme` is not `default` and `renderer: auto`, the bundle automatically prefers the **bootstrap** renderer so the skin CSS applies (iziToast ignores `toast_theme`).

## CSS auto-link (separated files)

If `external_css: true` and `auto_link_css: true`, `wrap_notify_bootstrap()` automatically injects:

- `wrap_notificator.base.css` (always)
- plus an optional theme file depending on `toast_theme`:
  - `wrap_notificator.amazon.css`
  - `wrap_notificator.google.css`
  - `wrap_notificator.dark.css`

## Security & CORS

- By default, `withCredentials` is `false`. Enable it per listener (`options.withCredentials`) or globally (`wrap_notificator.mercure.with_credentials_default: true`) when private topics via cookie are required.
- If `withCredentials: true`:
  - `Access-Control-Allow-Credentials: true`, and `Access-Control-Allow-Origin` must be the exact origin (not `*`).
  - Cross‑domain requires JWT cookie `SameSite=None; Secure` + HTTPS.
- EventSource cannot send custom headers -> authentication relies on the hub cookie.
- **Web Push**: HTTPS required and browser/OS permissions.
