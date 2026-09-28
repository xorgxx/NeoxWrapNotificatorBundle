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
  site_name: 'Mon site'                # Optionnel : nom du site affiché dans le template email de contact
                                      # Fallbacks : paramètre Symfony `name_projet` (si disponible), puis host de la requête.
  default_recipients:
    email: 'contact@example.com'       # Destinataire par défaut (optionnel)
  default_senders:
    email: 'noreply@example.com'       # Expéditeur par défaut (utilisé si aucun `from` n'est passé à notifyEmail)
  bcc:
    alert: ['admin@example.com']       # BCC automatique quand le sujet contient [alert]
    commande: ['orders@example.com']   # BCC automatique quand le sujet contient [commande]
  logging:
    enabled: false                    # Activer le logging (par défaut vers les logs Symfony / Monolog)
  live_flash:
    enabled: false
    consume: true
    group_messages: false
    default_topic_prefix: 'wrap_notificator/flash'
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
      renderer: 'auto'                # 'auto' (défaut), 'izitoast' ou 'bootstrap'
      force_theme: 'auto'             # 'auto' (défaut), 'dark' ou 'light' (forçage du thème UI)
      toast_theme: 'default'          # 'default' (défaut), 'amazon' (ou 'amazone'), 'google', 'dark'
      asset_path: '@WrapNotificator/css/wrap_notificator.css'
      asset_fallback_prefix: '/bundles/wrapnotificator'
```

### Variables du template email de contact (par défaut)

Si `wrap_notificator.email_template.enabled: true`, le bundle utilise le template `@WrapNotificator/email/contact_form.html.twig` et fournit notamment :

- **`siteName`** :
  - `wrap_notificator.site_name` si défini
  - sinon paramètre Symfony `name_projet` (si présent)
  - sinon `request.host`
- **`siteUrl`** (lien affiché dans l'entête) :
  - paramètre Symfony `web_site` (si présent)
  - sinon `request.schemeAndHttpHost`

## Thèmes UI (force_theme vs toast_theme)

- **force_theme** : force le mode **dark/light** (couleurs)
- **toast_theme** : applique un **skin CSS** (amazon/google/dark) pour les toasts Bootstrap

Quand `toast_theme` est différent de `default` et que `renderer: auto`, le bundle préfère automatiquement le renderer **bootstrap** pour que le skin CSS s'applique (iziToast ignore `toast_theme`).

## CSS auto-link (fichiers séparés)

Si `external_css: true` et `auto_link_css: true`, `wrap_notify_bootstrap()` injecte automatiquement les CSS suivants :

- `wrap_notificator.base.css` (toujours)
- + un fichier optionnel selon `toast_theme` :
  - `wrap_notificator.amazon.css`
  - `wrap_notificator.google.css`
  - `wrap_notificator.dark.css`

## Expéditeur par défaut & BCC automatique

### Expéditeur par défaut (`default_senders.email`)

Quand `default_senders.email` est configuré, `MessageFactory` l'utilise automatiquement comme adresse `From` si aucun `from` n'est explicitement passé dans les `opts` de `notifyEmail()`. Cela évite l'erreur SMTP `550 5.7.1 Sender mismatch` quand le serveur SMTP exige que l'expéditeur corresponde au compte authentifié.

### BCC automatique par tag (`bcc`)

Le bundle supporte l'ajout automatique de destinataires en copie cachée (BCC) basé sur un **tag** dans le sujet de l'email.

**Configuration :**

```yaml
wrap_notificator:
  bcc:
    alert: ['admin@example.com', 'manager@example.com']
    commande: ['orders@example.com']
```

**Utilisation :** préfixez le sujet avec `[tag]` :

```php
// Le tag [alert] est détecté, le BCC est appliqué, puis le tag est supprimé du sujet
$notifier->notifyEmail('[alert] Alertes stock', $html, 'user@example.com');
// Sujet envoyé : « Alertes stock » (sans le tag)
// BCC : admin@example.com, manager@example.com

// Le tag [commande] est détecté, le BCC est appliqué, puis le tag est supprimé du sujet
$notifier->notifyEmail('[commande] Commande validée', $html, 'user@example.com');
// Sujet envoyé : « Commande validée » (sans le tag)
// BCC : orders@example.com
```

- Le tag `[xxx]` est **supprimé** du sujet avant l'envoi — il est invisible pour le destinataire.
- Si le tag n'a pas d'entrée dans la config `bcc`, aucun BCC n'est ajouté.
- Si aucun tag n'est présent dans le sujet, le comportement est inchangé.
- Un tag peut avoir plusieurs adresses BCC.

## Sécurité & CORS

- Par défaut, `withCredentials` est à `false`. Activez‑le par écouteur (`options.withCredentials`) ou globalement (`wrap_notificator.mercure.with_credentials_default: true`) si vous avez besoin d’accéder à des topics privés via cookie.
- Si `withCredentials: true` :
  - `Access-Control-Allow-Credentials: true`, et `Access-Control-Allow-Origin` doit être l’origine exacte (pas `*`).
  - En cross‑domain, cookie JWT en `SameSite=None; Secure` + HTTPS.
- EventSource n’accepte pas d’en‑têtes personnalisés -> l'authentification repose sur le cookie du hub.
- **Web Push** : HTTPS requis et permissions navigateur/OS.
