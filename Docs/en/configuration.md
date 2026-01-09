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
  logging:
    enabled: false                    # Enable logging (defaults to Symfony / Monolog logs)
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
      asset_path: '@WrapNotificator/css/wrap_notificator.css'
      asset_fallback_prefix: '/bundles/wrapnotificator'
```

## Security & CORS

- By default, `withCredentials` is `false`. Enable it per listener (`options.withCredentials`) or globally (`wrap_notificator.mercure.with_credentials_default: true`) when private topics via cookie are required.
- If `withCredentials: true`:
  - `Access-Control-Allow-Credentials: true`, and `Access-Control-Allow-Origin` must be the exact origin (not `*`).
  - Cross‑domain requires JWT cookie `SameSite=None; Secure` + HTTPS.
- EventSource cannot send custom headers -> authentication relies on the hub cookie.
- **Web Push**: HTTPS required and browser/OS permissions.
