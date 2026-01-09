# Configuration

## Configuration minimale

### Fichier `.env`
Voici les variables d'environnement supportées par le bundle (exemples) :

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

### Configuration du bundle
Créez le fichier `config/packages/wrap_notificator.yaml` :

```yaml
wrap_notificator:
  logging:
    enabled: false                    # Activer le logging (par défaut vers les logs Symfony / Monolog)
  mercure:
    enabled: true
    notify_status: false              # Notifier automatiquement le statut de l'envoi via Mercure
    turbo_enabled: false
    only_authenticated: false
    public_url: '%env(string:MERCURE_PUBLIC_URL)%'
    with_credentials_default: false   # par défaut pour EventSource ; surcharge possible via options.withCredentials
    default_topics: ['geo_notificator/stream']
    ui:
      external_css: true
      auto_link_css: true
      asset_path: '@WrapNotificator/css/wrap_notificator.css'
      asset_fallback_prefix: '/bundles/wrapnotificator'
```

## Sécurité & CORS

- Par défaut, `withCredentials` est à `false`. Activez‑le par écouteur (`options.withCredentials`) ou globalement (`wrap_notificator.mercure.with_credentials_default: true`) si vous avez besoin d’accéder à des topics privés via cookie.
- Si `withCredentials: true` :
  - `Access-Control-Allow-Credentials: true`, et `Access-Control-Allow-Origin` doit être l’origine exacte (pas `*`).
  - En cross‑domain, cookie JWT en `SameSite=None; Secure` + HTTPS.
- EventSource n’accepte pas d’en‑têtes personnalisés -> l'authentification repose sur le cookie du hub.
- **Web Push** : HTTPS requis et permissions navigateur/OS.
